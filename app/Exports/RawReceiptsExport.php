<?php

namespace App\Exports;

use App\Models\RawReceipt;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RawReceiptsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly int $tenantId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
        private readonly ?string $status = null,
        private readonly ?string $qualityResult = null,
    ) {
    }

    public function query()
    {
        return RawReceipt::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenantId)
            ->with(['contact', 'rawMaterialType'])
            ->whereBetween('created_at', [
                $this->dateFrom.' 00:00:00',
                $this->dateTo.' 23:59:59',
            ])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->qualityResult, fn ($q) => $q->where('quality_result', $this->qualityResult))
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'رقم الاستلامة',
            'التاريخ',
            'المورد / العميل',
            'نوع الخام',
            'الكمية (كجم)',
            'الكمية المتاحة (كجم)',
            'عدد الصناديق',
            'الجودة',
            'الحالة',
            'سعر الكيلو',
            'الإجمالي',
            'تكلفة النقل',
            'ملاحظات الجودة',
        ];
    }

    public function map($receipt): array
    {
        $qualityMap = [
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'low' => 'منخفض',
            'rejected' => 'مرفوض',
        ];

        $statusMap = [
            'pending' => 'معلق',
            'in_stock' => 'في المخزون',
            'reserved' => 'محجوز',
            'dispatched' => 'مصروف',
            'consumed' => 'مستهلك',
            'rejected' => 'مرفوض',
            'priced' => 'مسعر',
            'approved' => 'معتمد',
        ];

        return [
            $receipt->reference_no,
            optional($receipt->created_at)?->format('Y-m-d H:i'),
            $receipt->contact?->name,
            $receipt->rawMaterialType?->name,
            $receipt->quantity_kg,
            $receipt->availableQty(),
            $receipt->boxes_count,
            $qualityMap[$receipt->quality_result] ?? $receipt->quality_result,
            $statusMap[$receipt->status] ?? $receipt->status,
            $receipt->price_per_kg,
            $receipt->total_price,
            $receipt->transport_cost,
            $receipt->quality_notes,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1D9E75']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'استلامات الخام';
    }
}
