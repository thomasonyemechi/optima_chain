"""FastAPI service for weekly demand bands and forecast drift monitoring."""

from __future__ import annotations

import logging
import os
from contextlib import asynccontextmanager
from datetime import date, datetime, timedelta, timezone
from pathlib import Path
from typing import Any, Literal

import httpx
import joblib
import numpy as np
import pandas as pd
from fastapi import FastAPI, HTTPException, Request
from pydantic import BaseModel, ConfigDict, Field


LOGGER = logging.getLogger(__name__)
OPEN_METEO_URL = "https://api.open-meteo.com/v1/forecast"
DRIFT_THRESHOLD = 0.15
WEATHER_TIMEOUT_SECONDS = 10.0
DEFAULT_MODEL_PATH = Path(__file__).resolve().with_name("demand_model.pkl")


class WeatherForecastUnavailable(Exception):
    """Raised when Open-Meteo cannot provide the requested week's forecast."""


class WeatherServiceError(Exception):
    """Raised when Open-Meteo cannot be reached or returns invalid data."""


class PredictionRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")

    location_id: str | int
    latitude: float = Field(ge=-90, le=90, allow_inf_nan=False)
    longitude: float = Field(ge=-180, le=180, allow_inf_nan=False)
    week_number: int = Field(ge=1, le=53)
    year: int = Field(ge=1, le=9999)
    inflation_rate: float = Field(allow_inf_nan=False)
    past_sales_avg: float = Field(
        ge=0,
        allow_inf_nan=False,
        description=(
            "Available to models trained with this feature; the supplied "
            "train.py artifact does not currently use it."
        ),
    )


class PredictionResponse(BaseModel):
    low_band: float
    expected_band: float
    high_band: float
    weather_summary: str


class DriftRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")

    actual_sales: list[float] = Field(min_length=1, max_length=10000)
    predicted_sales: list[float] = Field(min_length=1, max_length=10000)


class DriftResponse(BaseModel):
    status: Literal["ok", "alert"]
    message: str


def load_demand_model(model_path: Path) -> dict[str, Any]:
    """Load and validate the quantile-model artifact created by train.py."""
    if not model_path.is_file():
        raise FileNotFoundError(f"Demand model artifact not found: {model_path}")

    artifact = joblib.load(model_path)
    if not isinstance(artifact, dict):
        raise ValueError("Demand model artifact must be a dictionary.")

    models = artifact.get("models")
    if not isinstance(models, dict) or not {"low", "expected", "high"}.issubset(
        models
    ):
        raise ValueError(
            "Demand model artifact must contain low, expected, and high models."
        )
    if any(not callable(getattr(models[name], "predict", None)) for name in models):
        raise ValueError("Every demand model must provide a predict method.")

    feature_columns = artifact.get(
        "feature_columns",
        [
            "location_id",
            "week_of_year",
            "year",
            "temperature",
            "rainfall",
            "inflation_rate",
        ],
    )
    if (
        not isinstance(feature_columns, list)
        or not feature_columns
        or not all(isinstance(column, str) for column in feature_columns)
    ):
        raise ValueError("Demand model artifact has invalid feature_columns.")

    artifact["feature_columns"] = feature_columns
    return artifact


async def get_weather_forecast(
    latitude: float,
    longitude: float,
    week_number: int,
    year: int | None = None,
    client: httpx.AsyncClient | None = None,
) -> dict[str, float | str]:
    """Fetch weekly mean temperature and total precipitation from Open-Meteo.

    ``year`` defaults to the current UTC ISO week-year. The optional client
    allows the FastAPI lifespan to reuse connections and tests to inject a
    mock transport.
    """
    forecast_year = year or datetime.now(timezone.utc).isocalendar().year
    try:
        week_start = date.fromisocalendar(forecast_year, week_number, 1)
        week_end = date.fromisocalendar(forecast_year, week_number, 7)
    except ValueError as error:
        raise ValueError(
            f"Week {week_number} is not a valid ISO week in {forecast_year}."
        ) from error

    params = {
        "latitude": latitude,
        "longitude": longitude,
        "daily": "temperature_2m_mean,precipitation_sum",
        "timezone": "auto",
        "start_date": week_start.isoformat(),
        "end_date": week_end.isoformat(),
    }
    owns_client = client is None

    try:
        if owns_client:
            client = httpx.AsyncClient(timeout=WEATHER_TIMEOUT_SECONDS)
        if client is None:
            raise WeatherServiceError("Open-Meteo HTTP client is unavailable.")
        response = await client.get(OPEN_METEO_URL, params=params)
        if response.status_code == 400:
            try:
                error_payload = response.json()
                detail = (
                    error_payload.get("reason", "forecast unavailable")
                    if isinstance(error_payload, dict)
                    else "forecast unavailable"
                )
            except ValueError:
                detail = "forecast unavailable"
            raise WeatherForecastUnavailable(str(detail))
        response.raise_for_status()
        payload = response.json()
    except WeatherForecastUnavailable:
        raise
    except httpx.TimeoutException:
        raise
    except (httpx.HTTPError, ValueError) as error:
        raise WeatherServiceError("Open-Meteo returned an invalid response.") from error
    finally:
        if owns_client and client is not None:
            await client.aclose()

    daily = payload.get("daily") if isinstance(payload, dict) else None
    if not isinstance(daily, dict):
        raise WeatherServiceError("Open-Meteo response is missing daily data.")
    dates = daily.get("time")
    temperatures = daily.get("temperature_2m_mean")
    precipitation = daily.get("precipitation_sum")
    if not all(
        isinstance(values, list) for values in (dates, temperatures, precipitation)
    ):
        raise WeatherServiceError("Open-Meteo response has incomplete daily data.")
    if not (len(dates) == len(temperatures) == len(precipitation)):
        raise WeatherServiceError("Open-Meteo returned mismatched daily data.")

    requested_dates = {
        (week_start + timedelta(days=offset)).isoformat() for offset in range(7)
    }
    selected = [
        index
        for index, forecast_date in enumerate(dates)
        if forecast_date in requested_dates
    ]
    if (
        len(selected) != 7
        or {dates[index] for index in selected} != requested_dates
    ):
        raise WeatherForecastUnavailable(
            f"A complete forecast is not available for ISO week "
            f"{week_number} of {forecast_year}."
        )

    try:
        weekly_temperatures = np.asarray(
            [temperatures[index] for index in selected], dtype=float
        )
        weekly_precipitation = np.asarray(
            [precipitation[index] for index in selected], dtype=float
        )
    except (TypeError, ValueError, IndexError) as error:
        raise WeatherServiceError("Open-Meteo returned invalid weather values.") from error
    if (
        not np.isfinite(weekly_temperatures).all()
        or not np.isfinite(weekly_precipitation).all()
    ):
        raise WeatherForecastUnavailable(
            f"Weather values are not available for ISO week "
            f"{week_number} of {forecast_year}."
        )

    weekly_rainfall = float(weekly_precipitation.sum())
    return {
        "temperature": float(weekly_temperatures.mean()),
        "rainfall": weekly_rainfall,
        "weather_summary": "Rainy" if weekly_rainfall >= 1.0 else "Dry",
    }


@asynccontextmanager
async def lifespan(app: FastAPI):
    model_path = Path(os.environ.get("DEMAND_MODEL_PATH", DEFAULT_MODEL_PATH))
    try:
        app.state.demand_model = load_demand_model(model_path)
    except Exception:
        LOGGER.exception("Unable to load demand model artifact at %s", model_path)
        raise

    async with httpx.AsyncClient(timeout=WEATHER_TIMEOUT_SECONDS) as weather_client:
        app.state.weather_client = weather_client
        yield


app = FastAPI(
    title="Weekly Demand Forecast API",
    version="1.0.0",
    description=(
        "Serves weekly XGBoost demand quantiles and monitors forecast drift. "
        "Weather forecasts are provided by the free Open-Meteo API."
    ),
    lifespan=lifespan,
)


@app.post("/api/v1/predict-band", response_model=PredictionResponse)
async def predict_band(
    payload: PredictionRequest, request: Request
) -> PredictionResponse:
    """Return low, expected, and high weekly demand predictions."""
    try:
        week_start = date.fromisocalendar(payload.year, payload.week_number, 1)
    except ValueError as error:
        raise HTTPException(
            status_code=422,
            detail=f"Week {payload.week_number} is not valid in {payload.year}.",
        ) from error

    try:
        weather = await get_weather_forecast(
            latitude=payload.latitude,
            longitude=payload.longitude,
            week_number=payload.week_number,
            year=payload.year,
            client=request.app.state.weather_client,
        )
    except WeatherForecastUnavailable as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except WeatherServiceError as error:
        LOGGER.warning("Weather service request failed: %s", error)
        raise HTTPException(
            status_code=502,
            detail="Unable to retrieve a valid weather forecast.",
        ) from error
    except httpx.TimeoutException as error:
        LOGGER.warning("Weather service request timed out.")
        raise HTTPException(
            status_code=504,
            detail="The weather service request timed out.",
        ) from error

    model_artifact = request.app.state.demand_model
    feature_values: dict[str, Any] = {
        "location_id": str(payload.location_id),
        "week_of_year": payload.week_number,
        "year": payload.year,
        "temperature": weather["temperature"],
        "rainfall": weather["rainfall"],
        "inflation_rate": payload.inflation_rate,
        "past_sales_avg": payload.past_sales_avg,
    }
    feature_columns = model_artifact["feature_columns"]
    missing_features = sorted(set(feature_columns) - set(feature_values))
    if missing_features:
        raise HTTPException(
            status_code=500,
            detail="The demand model requires unsupported input features.",
        )
    features = pd.DataFrame(
        [{column: feature_values[column] for column in feature_columns}],
        columns=feature_columns,
    )

    try:
        models = model_artifact["models"]
        predictions = np.asarray(
            [
                float(np.asarray(models[name].predict(features)).reshape(-1)[0])
                for name in ("low", "expected", "high")
            ],
            dtype=float,
        )
        if not np.isfinite(predictions).all():
            raise ValueError("Demand model returned non-finite predictions.")
        predictions = np.sort(np.maximum(predictions, 0.0))
    except Exception as error:
        LOGGER.exception(
            "Demand prediction failed for location %s and week starting %s.",
            payload.location_id,
            week_start,
        )
        raise HTTPException(
            status_code=500,
            detail="Unable to generate a demand forecast.",
        ) from error

    return PredictionResponse(
        low_band=float(predictions[0]),
        expected_band=float(predictions[1]),
        high_band=float(predictions[2]),
        weather_summary=str(weather["weather_summary"]),
    )


@app.post("/api/v1/evaluate-drift", response_model=DriftResponse)
async def evaluate_drift(payload: DriftRequest) -> DriftResponse:
    """Compare a month's actual sales to its expected-demand predictions."""
    if len(payload.actual_sales) != len(payload.predicted_sales):
        raise HTTPException(
            status_code=422,
            detail="actual_sales and predicted_sales must have the same length.",
        )

    actual = np.asarray(payload.actual_sales, dtype=float)
    predicted = np.asarray(payload.predicted_sales, dtype=float)
    if not np.isfinite(actual).all() or not np.isfinite(predicted).all():
        raise HTTPException(
            status_code=422,
            detail="Sales values must be finite numbers.",
        )
    if (actual < 0).any() or (predicted < 0).any():
        raise HTTPException(
            status_code=422,
            detail="Sales values cannot be negative.",
        )

    nonzero_actual = actual != 0
    if not nonzero_actual.any():
        raise HTTPException(
            status_code=422,
            detail="MAPE is undefined when all actual sales values are zero.",
        )

    mape = float(
        np.mean(
            np.abs(
                (actual[nonzero_actual] - predicted[nonzero_actual])
                / actual[nonzero_actual]
            )
        )
    )
    if mape > DRIFT_THRESHOLD:
        return DriftResponse(
            status="alert",
            message="Forecast drift detected. Retraining required.",
        )

    return DriftResponse(
        status="ok",
        message="Forecast is within the 15% drift threshold.",
    )
