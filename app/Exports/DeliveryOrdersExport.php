<?php

namespace App\Exports;

use App\Models\RawDeliveryOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DeliveryOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly int $tenantId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
        private readonly ?string $status = null,
    ) {
    }

    public function query()
    {
        return RawDeliveryOrder::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenantId)
            ->with(['client', 'supplier'])
            ->whereBetween('order_date', [$this->dateFrom, $this->dateTo])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('order_date');
    }

    public function headings(): array
    {
        return [
            'رقم الأذن',
            'التاريخ',
            'العميل',
            'المورد',
            'الصنف',
            'الكمية المستلمة',
            'الكمية الصافية',
            'السعر',
            'الإجمالي',
            'تكلفة النقل',
            'الحالة',
        ];
    }

    public function map($row): array
    {
        return [
            $row->reference_no,
            optional($row->order_date)?->format('Y-m-d'),
            $row->client?->name,
            $row->supplier?->name,
            $row->raw_type,
            $row->received_qty,
            $row->net_qty,
            $row->price_per_unit,
            $row->total_amount,
            $row->transport_total,
            match ($row->status) {
                'draft' => 'مسودة',
                'confirmed' => 'مؤكد',
                'cancelled' => 'ملغي',
                default => $row->status,
            },
        ];
    }

    public function title(): string
    {
        return 'أوامر التوريد';
    }
}
