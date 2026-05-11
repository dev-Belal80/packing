<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportCost extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['total_cost', 'distribution_method', 'distribution_date', 'notes', 'created_by'];

    protected $casts = ['total_cost' => 'decimal:2', 'distribution_date' => 'date'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(\App\Models\TransportCostReceipt::class);
    }
}



