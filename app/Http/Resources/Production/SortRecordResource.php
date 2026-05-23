<?php

namespace App\Http\Resources\Production;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SortRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'accounting_period' => $this->accounting_period,
            'branch' => $this->branch,
            'sort_date' => $this->sort_date?->format('Y-m-d'),
            'sort_time' => $this->sort_time,
            'packhouse' => $this->packhouse ? [
                'id' => $this->packhouse->id,
                'name' => $this->packhouse->name,
            ] : null,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'notes' => $this->notes,
            'total_grade_a' => $this->total_grade_a,
            'total_grade_b' => $this->total_grade_b,
            'total_grade_c' => $this->total_grade_c,
            'total_waste' => $this->total_waste,
            'total_returned' => $this->total_returned,
            'total_sort' => $this->total_sort,
            'has_waste_alert' => $this->has_waste_alert,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'draft' => 'مسودة',
                'posted' => 'مرحّل',
                'cancelled' => 'ملغي',
                default => $this->status,
            },
            'posted_by' => $this->postedBy?->name,
            'posted_at' => $this->posted_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'lines' => $this->whenLoaded('lines', function () {
                return $this->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'raw_type' => $line->raw_type,
                        'lot_no' => $line->lot_no,
                        'production_line_id' => $line->production_line_id,
                        'production_line_name' => $line->productionLine?->name,
                        'production_order_id' => $line->production_order_id,
                        'grade_a_kg' => $line->grade_a_kg,
                        'grade_b_kg' => $line->grade_b_kg,
                        'grade_c_kg' => $line->grade_c_kg,
                        'waste_kg' => $line->waste_kg,
                        'returned_kg' => $line->returned_kg,
                        'line_total' => $line->line_total,
                        'sort_order' => $line->sort_order,
                    ];
                });
            }),
        ];
    }
}
