<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\RawDeliveryOrder;
use App\Models\TransportCostDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportCostDistributionController extends BaseApiController
{
    public function getReceipts(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'transport_contractor' => ['nullable', 'string', 'max:255'],
            'include_previously_costed' => ['sometimes', 'boolean'],
            'include_farms' => ['sometimes', 'boolean'],
            'branch' => ['nullable', 'string', 'max:255'],
            'packhouse_id' => ['nullable', 'exists:packhouses,id'],
        ]);

        $receipts = RawDeliveryOrder::query()
            ->with('contact')
            ->when(isset($data['packhouse_id']), fn ($query) => $query->where('packhouse_id', $data['packhouse_id']))
            ->when(isset($data['branch']), fn ($query) => $query->where('branch', $data['branch']))
            ->when(isset($data['transport_contractor']), fn ($query) => $query->where('transport_contractor', $data['transport_contractor']))
            ->whereBetween('order_date', [$data['date_from'], $data['date_to']])
            ->when(! ($data['include_previously_costed'] ?? false), fn ($query) => $query->where('transport_cost', 0))
            ->latest('id')
            ->get();

        return $this->success($receipts);
    }

    public function distribute(Request $request)
    {
        $data = $request->validate([
            'packhouse_id' => ['required', 'exists:packhouses,id'],
            'branch' => ['nullable', 'string', 'max:255'],
            'transport_contractor' => ['nullable', 'string', 'max:255'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'include_previously_costed' => ['sometimes', 'boolean'],
            'include_farms' => ['sometimes', 'boolean'],
            'transport_cost' => ['required', 'numeric', 'min:0.01'],
            'receipt_ids' => ['required', 'array', 'min:1'],
            'receipt_ids.*' => ['integer', 'exists:raw_delivery_orders,id'],
            'distribution_method' => ['nullable', 'in:weight,equal'],
        ]);

        $receiptIds = array_values(array_unique($data['receipt_ids']));

        $receipts = RawDeliveryOrder::query()
            ->whereKey($receiptIds)
            ->get();

        if ($receipts->isEmpty()) {
            return $this->error('لا توجد استلامات صالحة للتوزيع', 422);
        }

        $method = $data['distribution_method'] ?? 'weight';
        $totalCost = (float) $data['transport_cost'];
        $totalQty = (float) $receipts->sum('ordered_qty');
        $allocated = [];

        DB::transaction(function () use ($receipts, $method, $totalCost, $totalQty, &$allocated): void {
            $count = max(1, $receipts->count());

            foreach ($receipts as $receipt) {
                if ($method === 'equal') {
                    $share = $totalCost / $count;
                } elseif ($totalQty > 0) {
                    $share = ((float) $receipt->ordered_qty / $totalQty) * $totalCost;
                } else {
                    $share = $totalCost / $count;
                }

                $share = round($share, 2);

                $receipt->transport_cost = (float) $receipt->transport_cost + $share;
                $receipt->save();

                $allocated[$receipt->id] = $share;
            }
        });

        $distribution = TransportCostDistribution::query()->create([
            'tenant_id' => app('current_tenant_id'),
            'packhouse_id' => $data['packhouse_id'],
            'branch' => $data['branch'] ?? null,
            'transport_contractor' => $data['transport_contractor'] ?? null,
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'include_previously_costed' => (bool) ($data['include_previously_costed'] ?? false),
            'include_farms' => (bool) ($data['include_farms'] ?? false),
            'transport_cost' => $totalCost,
            'distribution_method' => $method,
            'receipt_ids' => array_values(array_unique($data['receipt_ids'])),
            'allocated_costs' => $allocated,
            'status' => 'distributed',
            'created_by' => $request->user()?->id,
        ]);

        return $this->success([
            'distribution' => $distribution,
            'allocated' => $allocated,
            'total_receipts' => $receipts->count(),
        ], 'تم توزيع تكلفة النقل بنجاح');
    }
}
