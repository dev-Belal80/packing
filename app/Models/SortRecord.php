<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SortRecord extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'production_order_id', 'grade_a_kg', 'grade_b_kg', 'grade_c_kg',
        'normal_waste_kg', 'damaged_kg', 'damage_reason',
        'started_at', 'ended_at', 'has_waste_alert', 'recorded_by',
    ];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'has_waste_alert' => 'boolean'];

    protected $appends = ['total_output', 'waste_pct'];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }

    public function getTotalOutputAttribute(): float
    {
        return (float) $this->grade_a_kg
            + (float) $this->grade_b_kg
            + (float) $this->grade_c_kg
            + (float) $this->normal_waste_kg
            + (float) $this->damaged_kg;
    }

    public function getWastePctAttribute(): float
    {
        $actualInput = (float) ($this->productionOrder?->actual_input_kg ?? 0);
        if ($actualInput <= 0) {
            return 0.0;
        }

        return (((float) $this->normal_waste_kg + (float) $this->damaged_kg) / $actualInput) * 100;
    }
}


