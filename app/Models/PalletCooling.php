<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PalletCooling extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['pallet_id', 'fridge_id', 'entry_temp', 'entered_at', 'ready_at', 'has_temp_alert', 'recorded_by'];

    protected $casts = [
        'entry_temp' => 'decimal:2',
        'entered_at' => 'datetime',
        'ready_at' => 'datetime',
        'has_temp_alert' => 'boolean',
    ];

    public function pallet(): BelongsTo
    {
        return $this->belongsTo(Pallet::class);
    }

    public function fridge(): BelongsTo
    {
        return $this->belongsTo(Fridge::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}


