<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasReferenceNo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GateInquiry extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    use HasReferenceNo;

    protected $fillable = [
        'packhouse_id', 'reference_no', 'accounting_period', 'branch', 'entry_date',
        'entry_time', 'code', 'contact_id', 'raw_material_type_id', 'raw_type',
        'color', 'quantity', 'reason', 'vehicle_type', 'vehicle_number', 'driver_name',
        'vehicle_size', 'counter_on_entry', 'counter_on_exit', 'responsible_employee',
        'department', 'status', 'cargo_desc_entry', 'cargo_desc_exit', 'exit_date',
        'exit_time', 'inquiry_status', 'expected_qty', 'delivery_order_id', 'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
        'quantity' => 'decimal:3',
        'counter_on_entry' => 'decimal:2',
        'counter_on_exit' => 'decimal:2',
        'expected_qty' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if (blank($model->inquiry_status) && filled($model->status)) {
                $model->inquiry_status = $model->status;
            }

            if (blank($model->status) && filled($model->inquiry_status)) {
                $model->status = $model->inquiry_status;
            }
        });
    }

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function rawMaterialType(): BelongsTo
    {
        return $this->belongsTo(RawMaterialType::class);
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(RawDeliveryOrder::class, 'delivery_order_id');
    }

    public function scaleNotes(): HasMany
    {
        return $this->hasMany(ScaleNote::class);
    }

    public function rawReceipts(): HasMany
    {
        return $this->hasMany(RawReceipt::class);
    }

    protected function referencePrefix(): string
    {
        return 'GI';
    }
}


