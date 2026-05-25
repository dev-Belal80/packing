<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Validation\ValidationException;

class RawReceipt extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    use LogsActivity;

    protected $fillable = [
        'accounting_period', 'receipt_permission_number', 'balance_record_number', 'receipt_date',
        'branch', 'packing_station', 'farm', 'item_type', 'discount',
        'reception_unit', 'collection_unit', 'boxes_count', 'quantity_per_box', 'pallet_type',
        'pallets_count', 'produced_quantity', 'sorting_and_loss', 'used_quantity',
        'inspector', 'status',
        'packhouse_id', 'reference_no', 'contact_id', 'contact_role',
        'raw_material_type_id', 'gate_inquiry_id', 'scale_note_id',
        'vehicle_number', 'driver_name', 'transport_type', 'vehicle_type',
        'transport_contractor', 'entry_weight', 'exit_weight', 'exit_date_time',
        'quantity_kg', 'quality_result', 'quality_notes',
        'is_partial', 'has_weight_dispute', 'weight_dispute_notes',
        'price_per_kg', 'total_price', 'transport_cost',
        'reserved_qty', 'dispatched_qty', 'consumed_qty',
        'raw_delivery_order_id',
        'approval_status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'is_partial' => 'boolean',
        'has_weight_dispute' => 'boolean',
        'approved_at' => 'datetime',
        'exited_at' => 'datetime',
        'receipt_date' => 'date',
        'entry_weight' => 'decimal:3',
        'exit_weight' => 'decimal:3',
        'exit_date_time' => 'datetime',
        'discount' => 'decimal:2',
        'quantity_per_box' => 'decimal:3',
        'produced_quantity' => 'decimal:3',
        'sorting_and_loss' => 'decimal:3',
        'used_quantity' => 'decimal:3',
        'reserved_qty' => 'decimal:3',
        'dispatched_qty' => 'decimal:3',
        'consumed_qty' => 'decimal:3',
        'quantity_kg' => 'decimal:3',
        'price_per_kg' => 'decimal:4',
        'total_price' => 'decimal:2',
        'transport_cost' => 'decimal:2',
    ];

    protected $appends = ['available_qty'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function rawMaterialType(): BelongsTo
    {
        return $this->belongsTo(RawMaterialType::class);
    }

    public function gateInquiry(): BelongsTo
    {
        return $this->belongsTo(GateInquiry::class);
    }

    public function scaleNote(): BelongsTo
    {
        return $this->belongsTo(ScaleNote::class);
    }

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function productionOrderPickings(): HasMany
    {
        return $this->hasMany(ProductionOrderPicking::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function rawDeliveryOrder(): BelongsTo
    {
        return $this->belongsTo(RawDeliveryOrder::class, 'raw_delivery_order_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        // BR-003: Auto generate reference_no
        static::creating(function (self $model): void {
            if (! empty($model->reference_no)) {
                return;
            }

            $tenantId = (int) $model->tenant_id;
            if ($tenantId <= 0 && app()->has('current_tenant_id')) {
                $tenantId = (int) app('current_tenant_id');
            }
            $model->reference_no = self::generateReference('RR', $tenantId);
        });
    }

    public static function generateReference(string $prefix, int $tenantId): string
    {
        $date = now()->format('Ymd');
        $count = self::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', today())
                ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    public function isPriced(): bool
    {
        return $this->status === 'priced' || $this->status === 'in_stock';
    }

    public function isInStock(): bool
    {
        return $this->status === 'in_stock';
    }

    public function availableQty(): float
    {
        // الكمية المتاحة = الإجمالي - المحجوز - المصروف - المستهلك
        $reserved = (float) ($this->reserved_qty ?? 0);
        $dispatched = (float) ($this->dispatched_qty ?? 0);
        $consumed = (float) ($this->consumed_qty ?? 0);

        return max(0.0, (float) $this->quantity_kg - $reserved - $dispatched - $consumed);
    }

    public function reservedQty(): float
    {
        return (float) ($this->reserved_qty ?? 0);
    }

    public function dispatchedQty(): float
    {
        return (float) ($this->dispatched_qty ?? 0);
    }

    public function consumedQty(): float
    {
        return (float) ($this->consumed_qty ?? 0);
    }

    public function reserve(float $qty): void
    {
        if ($qty > $this->availableQty()) {
            throw ValidationException::withMessages([
                'raw_receipt_id' => "الكمية المطلوبة ({$qty}) أكبر من المتاح ({$this->availableQty()})",
            ]);
        }

        $this->increment('reserved_qty', $qty);
    }

    public function releaseReservation(float $qty): void
    {
        $this->decrement('reserved_qty', min($qty, (float) ($this->reserved_qty ?? 0)));
    }

    public function dispatch(float $qty): void
    {
        // decrement reserved, increment dispatched
        $this->decrement('reserved_qty', min($qty, (float) ($this->reserved_qty ?? 0)));
        $this->increment('dispatched_qty', $qty);
    }

    public function consume(float $qty): void
    {
        $this->decrement('dispatched_qty', min($qty, (float) ($this->dispatched_qty ?? 0)));
        $this->increment('consumed_qty', $qty);

        $total = (float) ($this->reserved_qty ?? 0) + (float) ($this->dispatched_qty ?? 0);
        if ($total <= 0 && (float) ($this->consumed_qty ?? 0) >= (float) ($this->quantity_kg ?? 0) * 0.99) {
            $this->update(['status' => 'consumed']);
        }
    }

    public function activeProductionOrderQty(): float
    {
        return (float) $this->productionOrders()
            ->whereIn('status', ['draft', 'reserved', 'dispatched', 'paused'])
            ->sum('target_qty_kg');
    }

    public function getAvailableQtyAttribute(): float
    {
        return $this->availableQty();
    }

    public function getUsedQuantityAttribute(): float
    {
        // backward compatibility: previous code relied on used_quantity
        // interpret as dispatched quantity (quantity already taken out for production)
        return (float) ($this->dispatched_qty ?? 0);
    }
}


