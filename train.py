#!/usr/bin/env python
"""Train weekly demand quantile models from a historical CSV file."""

from __future__ import annotations

import argparse
import warnings
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
import xgboost
from sklearn.compose import ColumnTransformer
from sklearn.metrics import mean_absolute_percentage_error, mean_squared_error
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder
from xgboost import XGBRegressor


REQUIRED_COLUMNS = (
    "location_id",
    "week_of_year",
    "year",
    "past_sales",
    "unmet_demand_flag",
    "temperature",
    "rainfall",
    "inflation_rate",
)
FEATURE_COLUMNS = (
    "location_id",
    "week_of_year",
    "year",
    "temperature",
    "rainfall",
    "inflation_rate",
)
NUMERIC_FEATURES = (
    "week_of_year",
    "year",
    "temperature",
    "rainfall",
    "inflation_rate",
)
QUANTILES = {
    "low": 0.10,
    "expected": 0.50,
    "high": 0.90,
}


def parse_boolean_flag(value: Any) -> bool:
    """Parse common CSV boolean representations without treating 'False' as true."""
    if isinstance(value, (bool, np.bool_)):
        return bool(value)
    if pd.isna(value):
        raise ValueError("unmet_demand_flag contains a missing value.")
    if isinstance(value, (int, np.integer)) and value in (0, 1):
        return bool(value)
    if isinstance(value, (float, np.floating)) and value in (0.0, 1.0):
        return bool(value)
    if isinstance(value, str):
        normalized = value.strip().lower()
        if normalized in {"true", "1", "yes", "y"}:
            return True
        if normalized in {"false", "0", "no", "n"}:
            return False
    raise ValueError(
        "unmet_demand_flag values must be booleans or one of "
        "true/false, 1/0, yes/no, or y/n."
    )


def load_training_data(csv_path: Path) -> tuple[pd.DataFrame, pd.Series, int]:
    """Read and validate data, excluding rows with censored (stockout) sales."""
    data = pd.read_csv(csv_path)
    missing_columns = sorted(set(REQUIRED_COLUMNS) - set(data.columns))
    if missing_columns:
        raise ValueError(
            "Input CSV is missing required columns: " + ", ".join(missing_columns)
        )
    if data.empty:
        raise ValueError("Input CSV contains no rows.")

    for column in NUMERIC_FEATURES + ("past_sales",):
        original_values = data[column]
        converted_values = pd.to_numeric(original_values, errors="coerce")
        invalid_values = original_values.notna() & converted_values.isna()
        if invalid_values.any():
            raise ValueError(f"Column '{column}' contains non-numeric values.")
        if np.isinf(converted_values.dropna()).any():
            raise ValueError(f"Column '{column}' contains infinite values.")
        data[column] = converted_values

    if data["year"].isna().any() or data["week_of_year"].isna().any():
        raise ValueError("year and week_of_year must not contain missing values.")
    if not data["year"].mod(1).eq(0).all():
        raise ValueError("year values must be whole numbers.")
    if not data["week_of_year"].between(1, 53).all():
        raise ValueError("week_of_year values must be between 1 and 53.")
    if data["past_sales"].isna().any():
        raise ValueError("past_sales must not contain missing values.")
    if (data["past_sales"] < 0).any():
        raise ValueError("past_sales cannot contain negative values.")

    data["unmet_demand_flag"] = data["unmet_demand_flag"].map(parse_boolean_flag)
    if data["location_id"].isna().any():
        raise ValueError("location_id must not contain missing values.")
    data["location_id"] = data["location_id"].astype(str)
    data["year"] = data["year"].astype(int)

    censored_count = int(data["unmet_demand_flag"].sum())
    eligible_data = data.loc[~data["unmet_demand_flag"]].copy()
    if eligible_data.empty:
        raise ValueError(
            "No uncensored observations remain. Stockout rows contain only a "
            "lower bound on demand and cannot be used as observed-demand targets."
        )

    features = eligible_data.loc[:, list(FEATURE_COLUMNS)].reset_index(drop=True)
    target = eligible_data["past_sales"].astype(float).reset_index(drop=True)
    return features, target, censored_count


def chronological_split(
    features: pd.DataFrame, target: pd.Series, validation_fraction: float
) -> tuple[pd.DataFrame, pd.DataFrame, pd.Series, pd.Series]:
    """Hold out the latest weeks so validation does not train on future data."""
    period_ids = features["year"] * 53 + features["week_of_year"]
    periods = np.sort(period_ids.unique())
    if len(periods) < 2:
        raise ValueError(
            "At least two distinct year/week periods are required for "
            "chronological train/validation splitting."
        )

    split_index = int(np.floor(len(periods) * (1.0 - validation_fraction)))
    split_index = min(max(split_index, 1), len(periods) - 1)
    validation_start = periods[split_index]
    training_mask = period_ids < validation_start
    validation_mask = ~training_mask

    if not training_mask.any() or not validation_mask.any():
        raise ValueError("Unable to create non-empty chronological data splits.")
    return (
        features.loc[training_mask].reset_index(drop=True),
        features.loc[validation_mask].reset_index(drop=True),
        target.loc[training_mask].reset_index(drop=True),
        target.loc[validation_mask].reset_index(drop=True),
    )


def make_quantile_pipeline(quantile: float, random_state: int) -> Pipeline:
    """Create preprocessing and an XGBoost quantile regression model."""
    preprocessor = ColumnTransformer(
        transformers=[
            ("location", OneHotEncoder(handle_unknown="ignore"), ["location_id"]),
            ("numeric", "passthrough", list(NUMERIC_FEATURES)),
        ],
        remainder="drop",
    )
    regressor = XGBRegressor(
        objective="reg:quantileerror",
        quantile_alpha=quantile,
        n_estimators=500,
        learning_rate=0.05,
        max_depth=6,
        min_child_weight=1,
        subsample=0.8,
        colsample_bytree=0.8,
        tree_method="hist",
        random_state=random_state,
        n_jobs=-1,
    )
    return Pipeline(
        steps=[
            ("preprocessor", preprocessor),
            ("regressor", regressor),
        ]
    )


def predict_bands(
    models: dict[str, Pipeline], features: pd.DataFrame
) -> pd.DataFrame:
    """Return ordered low, expected, and high demand predictions."""
    predictions = np.column_stack(
        [models[name].predict(features) for name in ("low", "expected", "high")]
    )
    predictions.sort(axis=1)
    return pd.DataFrame(
        predictions,
        columns=("low_band", "expected_demand", "high_band"),
        index=features.index,
    )


def calculate_metrics(actual: pd.Series, predicted: np.ndarray) -> dict[str, float]:
    """Compute RMSE and MAPE; MAPE excludes zero actuals where it is undefined."""
    actual_values = actual.to_numpy(dtype=float)
    rmse = float(np.sqrt(mean_squared_error(actual_values, predicted)))
    nonzero_mask = actual_values != 0
    if nonzero_mask.any():
        mape = float(
            mean_absolute_percentage_error(
                actual_values[nonzero_mask], predicted[nonzero_mask]
            )
        )
    else:
        mape = float("nan")
        warnings.warn(
            "MAPE is undefined because every validation target is zero.",
            RuntimeWarning,
            stacklevel=2,
        )
    return {"mape": mape, "rmse": rmse}


def train(
    csv_path: Path,
    model_path: Path,
    validation_fraction: float,
    random_state: int,
) -> dict[str, Any]:
    """Train, evaluate, and save the weekly demand forecasting artifact."""
    if xgboost.__version__.split(".")[0].isdigit() and int(
        xgboost.__version__.split(".")[0]
    ) < 2:
        raise RuntimeError(
            "Quantile regression requires XGBoost 2.0 or newer. "
            f"Installed version: {xgboost.__version__}."
        )

    features, target, censored_count = load_training_data(csv_path)
    x_train, x_validation, y_train, y_validation = chronological_split(
        features, target, validation_fraction
    )

    validation_models: dict[str, Pipeline] = {}
    for name, quantile in QUANTILES.items():
        model = make_quantile_pipeline(quantile, random_state)
        model.fit(x_train, y_train)
        validation_models[name] = model

    validation_bands = predict_bands(validation_models, x_validation)
    metrics = calculate_metrics(
        y_validation, validation_bands["expected_demand"].to_numpy()
    )
    print(f"Unfulfilled-capacity rows excluded: {censored_count}")
    print(f"Uncensored observations: {len(features)}")
    print(f"Training observations: {len(x_train)}")
    print(f"Validation observations: {len(x_validation)}")
    if np.isnan(metrics["mape"]):
        print("Validation MAPE: undefined (all actual demand values are zero)")
    else:
        print(f"Validation MAPE: {metrics['mape']:.4%}")
    print(f"Validation RMSE: {metrics['rmse']:.4f}")

    final_models: dict[str, Pipeline] = {}
    for name, quantile in QUANTILES.items():
        model = make_quantile_pipeline(quantile, random_state)
        model.fit(features, target)
        final_models[name] = model

    artifact: dict[str, Any] = {
        "models": final_models,
        "feature_columns": list(FEATURE_COLUMNS),
        "quantiles": QUANTILES.copy(),
        "validation_metrics": metrics,
        "training_rows": len(features),
        "excluded_unfulfilled_capacity_rows": censored_count,
    }
    model_path.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(artifact, model_path)
    print(f"Saved demand model to: {model_path}")
    return artifact


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Train weekly low, expected, and high demand forecasts."
    )
    parser.add_argument(
        "input_csv",
        type=Path,
        help="CSV file containing the required historical weekly data.",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("demand_model.pkl"),
        help="Output joblib artifact path (default: demand_model.pkl).",
    )
    parser.add_argument(
        "--validation-fraction",
        type=float,
        default=0.2,
        help="Fraction of latest distinct weeks held out for evaluation (default: 0.2).",
    )
    parser.add_argument(
        "--random-state",
        type=int,
        default=42,
        help="Random seed used by XGBoost (default: 42).",
    )
    args = parser.parse_args()
    if not 0 < args.validation_fraction < 1:
        parser.error("--validation-fraction must be strictly between 0 and 1.")
    if not args.input_csv.is_file():
        parser.error(f"Input CSV does not exist: {args.input_csv}")
    return args


if __name__ == "__main__":
    arguments = parse_args()
    train(
        csv_path=arguments.input_csv,
        model_path=arguments.output,
        validation_fraction=arguments.validation_fraction,
        random_state=arguments.random_state,
    )
