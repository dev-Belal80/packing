<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\RawReceipt;
use App\Models\StockTransaction;

class StockReportController extends BaseApiController
{
    public function stockBalance(int $receiptId)
    {
        $receipt = RawReceipt::findOrFail($receiptId);
        $txns = StockTransaction::where('raw_receipt_id', $receiptId)->get();

        return $this->success([
            'receipt' => [
                'reference_no' => $receipt->reference_no,
                'quantity_kg' => $receipt->quantity_kg,
                'price_per_kg' => $receipt->price_per_kg,
                'status' => $receipt->status,
            ],
            'quantities' => [
                'reserved_qty' => $receipt->reserved_qty ?? 0,
                'dispatched_qty' => $receipt->dispatched_qty ?? 0,
                'consumed_qty' => $receipt->consumed_qty ?? 0,
                'available_qty' => $receipt->availableQty(),
            ],
            'transactions' => [
                'dispatch_out' => $txns->where('reason', 'production_dispatch')->sum('quantity_kg'),
                'product_in' => $txns->where('reason', 'product_in')->sum('quantity_kg'),
                'waste_out' => $txns->where('reason', 'waste')->sum('quantity_kg'),
                'return_in' => $txns->where('reason', 'return')->sum('quantity_kg'),
            ],
            'balance_check' => [
                'is_balanced' => abs(
                    $txns->where('reason', 'production_dispatch')->sum('quantity_kg') -
                    $txns->where('reason', 'product_in')->sum('quantity_kg') -
                    $txns->where('reason', 'waste')->sum('quantity_kg') +
                    $txns->where('reason', 'return')->sum('quantity_kg')
                   ) < 0.01,
                'waste_percentage' => ($receipt->consumed_qty > 0)
                    ? round($txns->where('reason', 'waste')->sum('quantity_kg') / $receipt->consumed_qty * 100, 2)
                    : 0,
                'conversion_rate' => ($receipt->consumed_qty > 0)
                    ? round($txns->where('reason', 'product_in')->sum('quantity_kg') / $receipt->consumed_qty * 100, 2)
                    : 0,
            ],
        ]);
    }
}
