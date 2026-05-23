<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pallet extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'packhouse_id', 'reference_no', 'wooden_pallet_no', 'order_number', 'final_order_no',
        'branch', 'pallet_date', 'pallet_time', 'end_date', 'end_time',
        'production_line_id', 'pallet_type_id', 'client_contact_id', 'client_code',
        'product_id', 'supplier_contact_id', 'lot_no', 'fridge_id', 'brand_id', 'punnet_sticker_id',
        'raw_type', 'package_type', 'size', 'grade', 'storage_location',
        'actual_weight', 'net_weight', 'weight_diff', 'total_weight_kg',
        'cooling_start', 'cooling_end', 'stickers', 'customer_lot_no',
        'has_carton', 'has_punnet', 'no_label', 'original_pallet_ref', 'special_specs',
        'sort_record_id', 'production_order_id', 'cartons_count',
        'status', 'is_shipped', 'receipt_confirmed', 'confirmed_at', 'confirmed_by', 'created_by',
    ];

    protected $casts = [
        'pallet_date' => 'date',
        'end_date' => 'date',
        'cooling_start' => 'datetime',
        'cooling_end' => 'datetime',
        'actual_weight' => 'decimal:3',
        'net_weight' => 'decimal:3',
        'weight_diff' => 'decimal:3',
        'total_weight_kg' => 'decimal:3',
        'has_carton' => 'boolean',
        'has_punnet' => 'boolean',
        'no_label' => 'boolean',
        'receipt_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'is_shipped' => 'boolean',
    ];

    protected $appends = ['is_ready_to_ship'];

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function palletType(): BelongsTo
    {
        return $this->belongsTo(PalletType::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_contact_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_contact_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fridge(): BelongsTo
    {
        return $this->belongsTo(Fridge::class);
    }

    public function sortRecord(): BelongsTo
    {
        return $this->belongsTo(SortRecord::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coolings(): HasMany
    {
        return $this->hasMany(PalletCooling::class);
    }

    public function latestCooling(): HasOne
    {
        return $this->hasOne(PalletCooling::class)->latestOfMany();
    }

    public function shippingPolicies(): BelongsToMany
    {
        return $this->belongsToMany(ShippingPolicy::class, 'shipping_policy_pallets')
            ->withPivot(['tenant_id', 'deleted_at'])
            ->withTimestamps();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->reference_no)) {
                $tenantId = (int) ($model->tenant_id ?? app('current_tenant_id'));
                if ($tenantId <= 0 && app()->has('current_tenant_id')) {
                    $tenantId = (int) app('current_tenant_id');
                }

                $model->reference_no = self::generateReference($tenantId);
            }

            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::saving(function (self $model): void {
            $actualWeight = (float) ($model->actual_weight ?? $model->total_weight_kg ?? 0);
            $model->actual_weight = $actualWeight;
            $model->total_weight_kg = $actualWeight;
            $model->weight_diff = round($actualWeight - (float) ($model->net_weight ?? 0), 3);
        });
    }

    public static function generateReference(int $tenantId): string
    {
        $lastReference = self::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference_no', 'like', 'DS%')
            ->orderByDesc('id')
            ->value('reference_no');

        $nextNumber = 1;

        if (is_string($lastReference) && preg_match('/^DS(\d+)$/', $lastReference, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = self::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('reference_no', 'like', 'DS%')
                ->count() + 1;
        }

        return 'DS'.$nextNumber;
    }

    public function isReadyToShip(): bool
    {
        if (! $this->receipt_confirmed || $this->is_shipped) {
            return false;
        }

        $cooling = $this->latestCooling;
        if (! $cooling) {
            return $this->status === 'cooled' && ($this->cooling_end ? now()->gte($this->cooling_end) : true);
        }

        return $cooling->ready_at && now()->gte($cooling->ready_at);
    }

    public function getIsReadyToShipAttribute(): bool
    {
        return $this->isReadyToShip();
    }

    public function scopeReadyToShip($query)
    {
        return $query->where('receipt_confirmed', true)
            ->where('status', 'cooled')
            ->where('is_shipped', false);
    }

    public function scopeBuilding($query)
    {
        return $query->where('status', 'building');
    }

    public function scopeCooled($query)
    {
        return $query->where('status', 'cooled');
    }

    public function scopeShipped($query)
    {
        return $query->where(function ($nested) {
            $nested->where('is_shipped', true)
                ->orWhere('status', 'shipped');
        });
    }
}


