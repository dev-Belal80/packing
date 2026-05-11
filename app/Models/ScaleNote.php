<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasReferenceNo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScaleNote extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    use HasReferenceNo;

    protected $fillable = [
        'packhouse_id', 'reference_no', 'accounting_period', 'branch', 'note_date',
        'note_time', 'contact_id', 'raw_type', 'cost_center', 'note_type', 'entry_count',
        'exit_count', 'box_weight', 'driver_name', 'vehicle_number', 'vehicle_type',
        'farm_code', 'season', 'full_weight', 'empty_weight', 'gross_weight', 'tare_weight',
        'net_weight', 'is_manual', 'manual_reason', 'discount_1_pct', 'discount_1_val',
        'discount_2', 'discount_3', 'final_weight', 'broken_boxes', 'partial_boxes',
        'harvest_contractor', 'harvest_pct', 'inspection_report', 'status', 'gate_inquiry_id',
        'created_by',
    ];

    protected $casts = [
        'is_manual' => 'bool',
        'note_date' => 'date',
        'entry_count' => 'decimal:2',
        'exit_count' => 'decimal:2',
        'box_weight' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'tare_weight' => 'decimal:3',
        'full_weight' => 'decimal:3',
        'empty_weight' => 'decimal:3',
        'net_weight' => 'decimal:3',
        'discount_1_pct' => 'decimal:2',
        'discount_1_val' => 'decimal:3',
        'discount_2' => 'decimal:3',
        'discount_3' => 'decimal:3',
        'final_weight' => 'decimal:3',
        'broken_boxes' => 'integer',
        'partial_boxes' => 'integer',
        'harvest_pct' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $fullWeight = $model->full_weight ?? $model->gross_weight ?? 0;
            $emptyWeight = $model->empty_weight ?? $model->tare_weight ?? 0;

            $model->full_weight = $fullWeight;
            $model->empty_weight = $emptyWeight;
            $model->gross_weight = $fullWeight;
            $model->tare_weight = $emptyWeight;

            $model->net_weight = max(0, (float) $fullWeight - (float) $emptyWeight);
            $model->discount_1_val = $model->net_weight * ((float) ($model->discount_1_pct ?? 0) / 100);
            $model->final_weight = max(
                0,
                (float) $model->net_weight
                - (float) ($model->discount_1_val ?? 0)
                - (float) ($model->discount_2 ?? 0)
                - (float) ($model->discount_3 ?? 0)
            );
        });
    }

    public function gateInquiry(): BelongsTo
    {
        return $this->belongsTo(GateInquiry::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function rawReceipts(): HasMany
    {
        return $this->hasMany(RawReceipt::class);
    }

    protected function referencePrefix(): string
    {
        return 'SN';
    }
}


