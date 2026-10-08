<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DemandRequest extends Model
{
    protected $fillable = [
        'distributor_id',
        'location_id',
        'week_number',
        'year',
        'requested_qty',
        'approved_qty',
        'dispatched_qty',
        'confirmed_qty',
        'sales_qty',
        'status',
        'company_short_supply',
        'flag_reason',
        'confirmation_code',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'year' => 'integer',
            'requested_qty' => 'integer',
            'approved_qty' => 'integer',
            'dispatched_qty' => 'integer',
            'confirmed_qty' => 'integer',
            'sales_qty' => 'integer',
            'company_short_supply' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function complaint(): HasOne
    {
        return $this->hasOne(Complaint::class);
    }
}
