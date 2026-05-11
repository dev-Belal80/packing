<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShippingPolicy extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    use LogsActivity;

    protected $fillable = [
        'packhouse_id', 'reference_no', 'importer_contact_id',
        'destination_country', 'container_number', 'vessel_name',
        'shipping_date', 'total_pallets', 'total_cartons', 'total_weight_kg',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = ['shipping_date' => 'date', 'approved_at' => 'datetime'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'importer_contact_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pallets(): BelongsToMany
    {
        return $this->belongsToMany(Pallet::class, 'shipping_policy_pallets')
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
            $model->reference_no = self::generateReference('SP', $tenantId);
        });

        static::saved(function (self $policy): void {
            $policy->recalculateTotals();
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

    public function recalculateTotals(): void
    {
        $this->updateQuietly([
            'total_pallets' => $this->pallets()->count(),
            'total_cartons' => $this->pallets()->sum('cartons_count'),
            'total_weight_kg' => $this->pallets()->sum('total_weight_kg'),
        ]);
    }
}




