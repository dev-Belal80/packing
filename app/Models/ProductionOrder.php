<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    use LogsActivity;

    protected $fillable = [
        'packhouse_id', 'reference_no', 'raw_receipt_id', 'product_id',
        'production_line_id', 'production_stage_id', 'supervisor_id',
        'target_qty_kg', 'actual_input_kg', 'order_type', 'client_contact_id',
        'status', 'pause_reason', 'cancel_reason', 'cancelled_by', 'started_at', 'completed_at',
    ];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function rawReceipt(): BelongsTo
    {
        return $this->belongsTo(RawReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function productionStage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function clientContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_contact_id');
    }

    public function pickings(): HasMany
    {
        return $this->hasMany(ProductionOrderPicking::class);
    }

    public function sortRecord(): HasOne
    {
        return $this->hasOne(SortRecord::class);
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
            $model->reference_no = self::generateReference('PO', $tenantId);
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

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}


