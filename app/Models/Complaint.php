<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'demand_request_id',
        'type',
        'description',
        'status',
    ];

    public function demandRequest(): BelongsTo
    {
        return $this->belongsTo(DemandRequest::class);
    }
}
