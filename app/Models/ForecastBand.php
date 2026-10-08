<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastBand extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'week_number',
        'year',
        'low_band',
        'expected_band',
        'high_band',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'year' => 'integer',
            'low_band' => 'integer',
            'expected_band' => 'integer',
            'high_band' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
