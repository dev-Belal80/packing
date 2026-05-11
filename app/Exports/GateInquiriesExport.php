<?php

namespace App\Exports;

use App\Models\GateInquiry;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GateInquiriesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly int $tenantId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
    ) {
    }

    public function query()
    {
        return GateInquiry::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenantId)
            ->with('contact')
            ->whereBetween('entry_date', [$this->dateFrom, $this->dateTo])
            ->orderByDesc('entry_date');
    }

    public function headings(): array
    {
        return [
            'رقم الاستعلام',
            'تاريخ الدخول',
            'المورد/العميل',
            'رقم السيارة',
            'السائق',
            'نوع السيارة',
            'الكمية المتوقعة',
            'الحالة',
            'وصف الحمولة دخول',
            'وصف الحمولة خروج',
        ];
    }

    public function map($row): array
    {
        return [
            $row->reference_no,
            optional($row->entry_date)?->format('Y-m-d'),
            $row->contact?->name,
            $row->vehicle_number,
            $row->driver_name,
            $row->vehicle_type,
            $row->expected_qty ?? $row->quantity,
            $row->inquiry_status ?? $row->status,
            $row->cargo_desc_entry,
            $row->cargo_desc_exit,
        ];
    }

    public function title(): string
    {
        return 'استعلامات البوابة';
    }
}
