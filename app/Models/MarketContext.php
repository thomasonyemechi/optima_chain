<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketContext extends Model
{
    protected $fillable = [
        'location_id',
        'observed_on',
        'temperature_celsius',
        'weather_summary',
        'inflation_rate',
        'inflation_region',
    ];

    protected function casts(): array
    {
        return [
            'observed_on' => 'date',
            'temperature_celsius' => 'decimal:2',
            'inflation_rate' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
