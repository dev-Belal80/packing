<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrderPicking extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'production_order_id', 'raw_receipt_id', 'dispatched_qty_kg',
        'dispatched_by', 'dispatched_at',
    ];

    protected $casts = ['dispatched_qty_kg' => 'decimal:3', 'dispatched_at' => 'datetime'];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function rawReceipt(): BelongsTo
    {
        return $this->belongsTo(RawReceipt::class);
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }
}

