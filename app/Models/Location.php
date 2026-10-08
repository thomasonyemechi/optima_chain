<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'distributor_id',
        'name',
        'code',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function forecastBands(): HasMany
    {
        return $this->hasMany(ForecastBand::class);
    }

    public function demandRequests(): HasMany
    {
        return $this->hasMany(DemandRequest::class);
    }

    public function marketContexts(): HasMany
    {
        return $this->hasMany(MarketContext::class);
    }
}
