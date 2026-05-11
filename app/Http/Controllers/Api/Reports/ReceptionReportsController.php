<?php

namespace App\Http\Controllers\Api\Reports;

use App\Exports\DeliveryOrdersExport;
use App\Exports\GateInquiriesExport;
use App\Exports\RawReceiptsExport;
use App\Exports\ReceptionDailyExport;
use App\Exports\ScaleNotesExport;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\GateInquiry;
use App\Models\RawDeliveryOrder;
use App\Models\RawReceipt;
use App\Models\ScaleNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReceptionReportsController extends BaseApiController
{
    // ─── Raw Receipts ────────────────────────────────────────────────

    public function rawReceiptsPdf(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'status' => ['nullable', 'string'],
            'quality_result' => ['nullable', 'string'],
        ]);

        $tenantId = (int) $user->tenant_id;

        $receipts = RawReceipt::query()
            ->where('tenant_id', $tenantId)
            ->with(['contact', 'rawMaterialType'])
            ->whereBetween('created_at', [
                $validated['date_from'].' 00:00:00',
                $validated['date_to'].' 23:59:59',
            ])
            ->when($validated['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($validated['quality_result'] ?? null, fn ($q, $v) => $q->where('quality_result', $v))
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.raw-receipts', [
            'receipts' => $receipts,
            'dateFrom' => $validated['date_from'],
            'dateTo' => $validated['date_to'],
            'tenant' => $user->tenant?->name,
            'printDate' => now()->format('Y-m-d H:i'),
            'totalQty' => $receipts->sum('quantity_kg'),
            'totalAmount' => $receipts->sum('total_price'),
            'count' => $receipts->count(),
            'avgPrice' => $receipts->avg('price_per_kg') ?? 0,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('raw-receipts-'.$validated['date_from'].'.pdf');
    }

    public function rawReceiptsExcel(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'status' => ['nullable', 'string'],
            'quality_result' => ['nullable', 'string'],
        ]);

        $filename = 'استلامات-الخام-'.$validated['date_from'].'.xlsx';

        return Excel::download(
            new RawReceiptsExport(
                (int) $user->tenant_id,
                $validated['date_from'],
                $validated['date_to'],
                $validated['status'] ?? null,
                $validated['quality_result'] ?? null,
            ),
            $filename
        );
    }

    // ─── Gate Inquiries ──────────────────────────────────────────────

    public function gateInquiriesPdf(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        $tenantId = (int) $user->tenant_id;

        $inquiries = GateInquiry::query()
            ->where('tenant_id', $tenantId)
            ->with('contact')
            ->whereBetween('entry_date', [$validated['date_from'], $validated['date_to']])
            ->orderByDesc('entry_date')
            ->get();

        $pdf = Pdf::loadView('reports.gate-inquiries', [
            'inquiries' => $inquiries,
            'dateFrom' => $validated['date_from'],
            'dateTo' => $validated['date_to'],
            'tenant' => $user->tenant?->name,
            'printDate' => now()->format('Y-m-d H:i'),
            'count' => $inquiries->count(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('gate-inquiries-'.$validated['date_from'].'.pdf');
    }

    public function gateInquiriesExcel(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        return Excel::download(
            new GateInquiriesExport(
                (int) $user->tenant_id,
                $validated['date_from'],
                $validated['date_to'],
            ),
            'استعلامات-البوابة-'.$validated['date_from'].'.xlsx'
        );
    }

    // ─── Scale Notes ─────────────────────────────────────────────────

    public function scaleNotesPdf(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        $tenantId = (int) $user->tenant_id;

        $notes = ScaleNote::query()
            ->where('tenant_id', $tenantId)
            ->with('contact')
            ->whereBetween('note_date', [$validated['date_from'], $validated['date_to']])
            ->orderByDesc('note_date')
            ->get();

        $pdf = Pdf::loadView('reports.scale-notes', [
            'notes' => $notes,
            'dateFrom' => $validated['date_from'],
            'dateTo' => $validated['date_to'],
            'tenant' => $user->tenant?->name,
            'printDate' => now()->format('Y-m-d H:i'),
            'totalNet' => $notes->sum('net_weight'),
            'totalFinal' => $notes->sum('final_weight'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('scale-notes-'.$validated['date_from'].'.pdf');
    }

    public function scaleNotesExcel(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        return Excel::download(
            new ScaleNotesExport(
                (int) $user->tenant_id,
                $validated['date_from'],
                $validated['date_to'],
            ),
            'علامات-الوزن-'.$validated['date_from'].'.xlsx'
        );
    }

    // ─── Delivery Orders ─────────────────────────────────────────────

    public function deliveryOrdersPdf(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $tenantId = (int) $user->tenant_id;

        $orders = RawDeliveryOrder::query()
            ->where('tenant_id', $tenantId)
            ->with(['client', 'supplier'])
            ->whereBetween('order_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('order_date')
            ->get();

        $pdf = Pdf::loadView('reports.delivery-orders', [
            'orders' => $orders,
            'dateFrom' => $validated['date_from'],
            'dateTo' => $validated['date_to'],
            'tenant' => $user->tenant?->name,
            'printDate' => now()->format('Y-m-d H:i'),
            'totalQty' => $orders->sum('received_qty'),
            'totalNet' => $orders->sum('net_qty'),
            'totalAmount' => $orders->sum('total_amount'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('delivery-orders-'.$validated['date_from'].'.pdf');
    }

    public function deliveryOrdersExcel(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        return Excel::download(
            new DeliveryOrdersExport(
                (int) $user->tenant_id,
                $validated['date_from'],
                $validated['date_to'],
                $validated['status'] ?? null,
            ),
            'أوامر-التوريد-'.$validated['date_from'].'.xlsx'
        );
    }

    // ─── Daily Full Report (All in one Excel) ────────────────────────

    public function dailyFullExcel(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        return Excel::download(
            new ReceptionDailyExport(
                (int) $user->tenant_id,
                $validated['date_from'],
                $validated['date_to'],
            ),
            'تقرير-الاستقبال-اليومي-'.$validated['date_from'].'.xlsx'
        );
    }
}
