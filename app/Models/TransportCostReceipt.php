<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportCostReceipt extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['transport_cost_id', 'raw_receipt_id', 'allocated_cost'];

    protected $casts = ['allocated_cost' => 'decimal:2'];

    public function transportCost(): BelongsTo
    {
        return $this->belongsTo(TransportCost::class);
    }

    public function rawReceipt(): BelongsTo
    {
        return $this->belongsTo(RawReceipt::class);
    }
}


