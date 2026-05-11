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
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>تقرير علامات الوزن</h1>
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
        <div class="val">{{ number_format((float) $totalNet, 3) }}</div>
        <div class="lbl">إجمالي الوزن الصافي</div>
    </div>
    <div class="summary-card">
        <div class="val">{{ number_format((float) $totalFinal, 3) }}</div>
        <div class="lbl">إجمالي الوزن النهائي</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>رقم علم الوزن</th>
        <th>التاريخ</th>
        <th>المورد/العميل</th>
        <th>الوزن الصافي</th>
        <th>حسم 1%</th>
        <th>قيمة حسم 1</th>
        <th>حسم 2</th>
        <th>حسم 3</th>
        <th>الوزن النهائي</th>
        <th>السائق</th>
        <th>رقم السيارة</th>
    </tr>
    </thead>
    <tbody>
    @foreach($notes as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row->reference_no }}</td>
            <td>{{ optional($row->note_date)?->format('Y-m-d') }}</td>
            <td>{{ $row->contact?->name }}</td>
            <td>{{ $row->net_weight }}</td>
            <td>{{ $row->discount_1_pct }}</td>
            <td>{{ $row->discount_1_val }}</td>
            <td>{{ $row->discount_2 }}</td>
            <td>{{ $row->discount_3 }}</td>
            <td>{{ $row->final_weight }}</td>
            <td>{{ $row->driver_name }}</td>
            <td>{{ $row->vehicle_number }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    نظام إدارة محطات التعبئة — تم إنشاء هذا التقرير تلقائياً
</div>

</body>
</html>
