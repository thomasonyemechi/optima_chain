<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PerformanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'distributor_id',
        'month',
        'year',
        'accuracy_score',
        'target_score',
        'timeliness_score',
        'payment_score',
        'total_score',
        'verification_code',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'accuracy_score' => 'decimal:2',
            'target_score' => 'decimal:2',
            'timeliness_score' => 'decimal:2',
            'payment_score' => 'decimal:2',
            'total_score' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PerformanceRecord $record): void {
            $record->verification_code ??= (string) Str::uuid();
        });
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }
}
