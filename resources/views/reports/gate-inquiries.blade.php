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
        .summary { margin-bottom: 16px; display: flex; gap: 12px; }
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
    <h1>تقرير استعلامات البوابة</h1>
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
        <div class="val">{{ $count }}</div>
        <div class="lbl">عدد الاستعلامات</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>رقم الاستعلام</th>
        <th>تاريخ الدخول</th>
        <th>المورد/العميل</th>
        <th>رقم السيارة</th>
        <th>السائق</th>
        <th>نوع السيارة</th>
        <th>الكمية المتوقعة</th>
        <th>الحالة</th>
        <th>وصف دخول</th>
        <th>وصف خروج</th>
    </tr>
    </thead>
    <tbody>
    @foreach($inquiries as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row->reference_no }}</td>
            <td>{{ optional($row->entry_date)?->format('Y-m-d') }}</td>
            <td>{{ $row->contact?->name }}</td>
            <td>{{ $row->vehicle_number }}</td>
            <td>{{ $row->driver_name }}</td>
            <td>{{ $row->vehicle_type }}</td>
            <td>{{ $row->expected_qty ?? $row->quantity }}</td>
            <td>{{ $row->inquiry_status ?? $row->status }}</td>
            <td>{{ $row->cargo_desc_entry }}</td>
            <td>{{ $row->cargo_desc_exit }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    نظام إدارة محطات التعبئة — تم إنشاء هذا التقرير تلقائياً
</div>

</body>
</html>
