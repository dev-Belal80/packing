<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SortRecordLine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'sort_record_id',
        'raw_type',
        'production_line_id',
        'lot_no',
        'production_order_id',
        'grade_a_kg',
        'grade_b_kg',
        'grade_c_kg',
        'waste_kg',
        'returned_kg',
        'line_total',
        'sort_order',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $line): void {
            $line->line_total = (float) ($line->grade_a_kg ?? 0)
                + (float) ($line->grade_b_kg ?? 0)
                + (float) ($line->grade_c_kg ?? 0)
                + (float) ($line->waste_kg ?? 0)
                + (float) ($line->returned_kg ?? 0);
        });

        static::saved(function (self $line): void {
            $line->sortRecord?->recalculateTotals();
        });

        static::deleted(function (self $line): void {
            $line->sortRecord?->recalculateTotals();
        });
    }

    public function sortRecord(): BelongsTo
    {
        return $this->belongsTo(SortRecord::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}
