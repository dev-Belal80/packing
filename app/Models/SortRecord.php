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
        'packhouse_id',
        'reference_no',
        'accounting_period',
        'branch',
        'sort_date',
        'sort_time',
        'description_ar',
        'description_en',
        'notes',
        'total_grade_a',
        'total_grade_b',
        'total_grade_c',
        'total_waste',
        'total_returned',
        'total_sort',
        'status',
        'has_waste_alert',
        'posted_by',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'sort_date' => 'date',
        'posted_at' => 'datetime',
        'has_waste_alert' => 'boolean',
    ];

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SortRecordLine::class)->orderBy('sort_order');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->reference_no)) {
                $tenantId = (int) ($model->tenant_id ?? app('current_tenant_id'));
                $date = now()->format('Ymd');
                $count = self::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('created_at', today())
                    ->count() + 1;
                $model->reference_no = sprintf('SR-%s-%04d', $date, $count);
            }

            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function recalculateTotals(): void
    {
        $totalGradeA = (float) $this->lines()->sum('grade_a_kg');
        $totalGradeB = (float) $this->lines()->sum('grade_b_kg');
        $totalGradeC = (float) $this->lines()->sum('grade_c_kg');
        $totalWaste = (float) $this->lines()->sum('waste_kg');
        $totalReturned = (float) $this->lines()->sum('returned_kg');
        $totalSort = $totalGradeA + $totalGradeB + $totalGradeC + $totalWaste + $totalReturned;

        $wastePct = $totalSort > 0 ? ($totalWaste / $totalSort) * 100 : 0.0;

        $this->updateQuietly([
            'total_grade_a' => $totalGradeA,
            'total_grade_b' => $totalGradeB,
            'total_grade_c' => $totalGradeC,
            'total_waste' => $totalWaste,
            'total_returned' => $totalReturned,
            'total_sort' => $totalSort,
            'has_waste_alert' => $wastePct > 10,
        ]);
    }
}


