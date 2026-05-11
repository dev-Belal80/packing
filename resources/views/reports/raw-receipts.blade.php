<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1A1F2E; direction: rtl; }
        .header { background: #1D9E75; color: white; padding: 16px 20px; margin-bottom: 16px; border-radius: 6px; }
        .header h1 { font-size: 16px; margin-bottom: 4px; }
        .header .meta { font-size: 9px; opacity: 0.85; }
        .summary { display: flex; gap: 12px; margin-bottom: 16px; }
        .summary-card { background: #F0FBF6; border: 1px solid #1D9E75; border-radius: 4px; padding: 8px 12px; flex: 1; text-align: center; }
        .summary-card .val { font-size: 14px; font-weight: bold; color: #1D9E75; }
        .summary-card .lbl { font-size: 8px; color: #4B5563; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1D9E75; color: white; padding: 6px 8px; text-align: right; font-size: 9px; }
        td { padding: 5px 8px; border-bottom: 1px solid #E5E7EB; font-size: 9px; }
        tr:nth-child(even) td { background: #F8FAFC; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 8px; }
        .badge-green { background: #E1F5EE; color: #0F6E56; }
        .badge-blue  { background: #E6F1FB; color: #185FA5; }
        .badge-amber { background: #FAEEDA; color: #854F0B; }
        .badge-red   { background: #FCEBEB; color: #A32D2D; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 8px; }
        .total-row td { font-weight: bold; background: #F0FBF6; border-top: 2px solid #1D9E75; }
    </style>
</head>
<body>

<div class="header">
    <h1>تقرير استلامات الخام</h1>
    <div class="meta">
        الفترة: {{ $dateFrom }} — {{ $dateTo }}
        &nbsp;|&nbsp;
        المحطة: {{ $tenant }}
        &nbsp;|&nbsp;
        تاريخ الطباعة: {{ $printDate }}
    </div>
</div>

<div class="summary">
    <div class="summary-card">
        <div class="val">{{ number_format($totalQty, 2) }}</div>
        <div class="lbl">إجمالي الكمية (كجم)</div>
    </div>
    <div class="summary-card">
        <div class="val">{{ $count }}</div>
        <div class="lbl">عدد الاستلامات</div>
    </div>
    <div class="summary-card">
        <div class="val">{{ number_format($totalAmount, 2) }}</div>
        <div class="lbl">إجمالي المبالغ (ج.م)</div>
    </div>
    <div class="summary-card">
        <div class="val">{{ number_format($avgPrice, 4) }}</div>
        <div class="lbl">متوسط سعر الكيلو</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>رقم الاستلامة</th>
        <th>التاريخ</th>
        <th>المورد/العميل</th>
        <th>الصنف</th>
        <th>الكمية (كجم)</th>
        <th>الجودة</th>
        <th>السعر</th>
        <th>الإجمالي</th>
        <th>الحالة</th>
    </tr>
    </thead>
    <tbody>
    @foreach($receipts as $i => $r)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $r->reference_no }}</td>
            <td>{{ optional($r->created_at)?->format('Y-m-d') }}</td>
            <td>{{ $r->contact?->name }}</td>
            <td>{{ $r->rawMaterialType?->name }}</td>
            <td>{{ number_format((float) $r->quantity_kg, 3) }}</td>
            <td>
                <span class="badge {{ match($r->quality_result) {
                    'excellent' => 'badge-green',
                    'good'      => 'badge-blue',
                    'low'       => 'badge-amber',
                    'rejected'  => 'badge-red',
                    default     => ''
                } }}">
                    {{ match($r->quality_result) {
                        'excellent' => 'ممتاز',
                        'good'      => 'جيد',
                        'low'       => 'منخفض',
                        'rejected'  => 'مرفوض',
                        default     => $r->quality_result
                    } }}
                </span>
            </td>
            <td>{{ $r->price_per_kg }}</td>
            <td>{{ number_format((float) $r->total_price, 2) }}</td>
            <td>{{ $r->status === 'in_stock' ? 'في المخزون' : ($r->status === 'rejected' ? 'مرفوض' : 'معلق') }}</td>
        </tr>
    @endforeach

    <tr class="total-row">
        <td colspan="5" style="text-align:center">الإجمالي</td>
        <td>{{ number_format((float) $totalQty, 3) }}</td>
        <td colspan="2"></td>
        <td>{{ number_format((float) $totalAmount, 2) }}</td>
        <td></td>
    </tr>
    </tbody>
</table>

<div class="footer">
    نظام إدارة محطات التعبئة — تم إنشاء هذا التقرير تلقائياً
</div>

</body>
</html>
