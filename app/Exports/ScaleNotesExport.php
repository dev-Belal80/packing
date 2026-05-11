<?php

namespace App\Exports;

use App\Models\ScaleNote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ScaleNotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly int $tenantId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
    ) {
    }

    public function query()
    {
        return ScaleNote::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenantId)
            ->with('contact')
            ->whereBetween('note_date', [$this->dateFrom, $this->dateTo])
            ->orderByDesc('note_date');
    }

    public function headings(): array
    {
        return [
            'رقم علم الوزن',
            'التاريخ',
            'المورد/العميل',
            'الوزن قائم',
            'الوزن فارغ',
            'الوزن الصافي',
            'حسم 1%',
            'قيمة حسم 1',
            'حسم 2',
            'حسم 3',
            'الوزن النهائي',
            'صناديق كسر',
            'صناديق عجز',
            'السائق',
            'رقم السيارة',
        ];
    }

    public function map($row): array
    {
        return [
            $row->reference_no,
            optional($row->note_date)?->format('Y-m-d'),
            $row->contact?->name,
            $row->full_weight,
            $row->empty_weight,
            $row->net_weight,
            $row->discount_1_pct,
            $row->discount_1_val,
            $row->discount_2,
            $row->discount_3,
            $row->final_weight,
            $row->broken_boxes,
            $row->partial_boxes,
            $row->driver_name,
            $row->vehicle_number,
        ];
    }

    public function title(): string
    {
        return 'علامات الوزن';
    }
}
