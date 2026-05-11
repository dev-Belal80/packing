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
        'packhouse_id', 'reference_no', 'pallet_type_id', 'product_id',
        'sort_record_id', 'grade', 'cartons_count', 'total_weight_kg',
        'status', 'receipt_confirmed', 'confirmed_at', 'confirmed_by',
    ];

    protected $casts = ['receipt_confirmed' => 'boolean', 'confirmed_at' => 'datetime'];

    protected $appends = ['is_ready_to_ship'];

    public function palletType(): BelongsTo
    {
        return $this->belongsTo(PalletType::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sortRecord(): BelongsTo
    {
        return $this->belongsTo(SortRecord::class);
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
            if (! empty($model->reference_no)) {
                return;
            }
            $tenantId = (int) $model->tenant_id;
            if ($tenantId <= 0 && app()->has('current_tenant_id')) {
                $tenantId = (int) app('current_tenant_id');
            }
            $model->reference_no = self::generateReference('PAL', $tenantId);
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

    public function isReadyToShip(): bool
    {
        if (! $this->receipt_confirmed) {
            return false;
        }

        $cooling = $this->latestCooling;
        if (! $cooling) {
            return false;
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
            ->where('status', 'cooled');
    }
}


