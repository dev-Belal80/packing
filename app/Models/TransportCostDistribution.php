<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportCostDistribution extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'packhouse_id', 'branch', 'transport_contractor', 'date_from', 'date_to',
        'include_previously_costed', 'include_farms', 'transport_cost',
        'distribution_method', 'receipt_ids', 'allocated_costs', 'status', 'created_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'include_previously_costed' => 'boolean',
        'include_farms' => 'boolean',
        'transport_cost' => 'decimal:2',
        'receipt_ids' => 'array',
        'allocated_costs' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }
}