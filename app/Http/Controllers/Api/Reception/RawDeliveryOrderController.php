<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\RawDeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RawDeliveryOrderController extends BaseApiController
{
    private function query()
    {
        return RawDeliveryOrder::withoutGlobalScopes()->with(['client', 'supplier', 'packhouse', 'createdBy']);
    }

    private function reloadOrder(RawDeliveryOrder $rawDeliveryOrder): RawDeliveryOrder
    {
        return $this->query()->findOrFail($rawDeliveryOrder->id);
    }

    public function index(Request $request)
    {
        $orders = RawDeliveryOrder::with(['client', 'supplier', 'packhouse'])
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('order_date', '<=', $request->date_to))
            ->when($request->search,    fn($q) => $q->where('reference_no', 'like', "%{$request->search}%"))
            ->latest()->paginate(20);

        return $this->paginated($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Header
            'packhouse_id'           => 'required|exists:packhouses,id',
            'branch'                 => 'nullable|string',
            'order_date'             => 'required|date',
            'agricultural_season'    => 'nullable|string',
            'work_order'             => 'nullable|string',
            'cost_center'            => 'nullable|string',
            'raw_type'               => 'nullable|string',
            // Locations
            'loading_warehouse'      => 'nullable|string',
            'destination_warehouse'  => 'nullable|string',
            'supplying_station'      => 'nullable|string',
            'delivery_station'       => 'nullable|string',
            'loading_warehouse_season' => 'nullable|string',
            'supply_warehouse_season'  => 'nullable|string',
            // Description
            'description_ar'         => 'nullable|string',
            'description_en'         => 'nullable|string',
            'reference_number'       => 'nullable|string',
            // Weight
            'weight_on_entry'        => 'nullable|numeric|min:0',
            'weight_on_exit'         => 'nullable|numeric|min:0',
            // Sale
            'client_contact_id'      => 'nullable|exists:contacts,id',
            'sale_order'             => 'nullable|string',
            'received_qty'           => 'required|numeric|min:0',
            'discount_pct'           => 'nullable|numeric|min:0|max:100',
            'extra_discount_pct'     => 'nullable|numeric|min:0|max:100',
            'invoice_number'         => 'nullable|string',
            'price_per_unit'         => 'nullable|numeric|min:0',
            'units_count'            => 'nullable|integer|min:0',
            'sorting_cost'           => 'nullable|numeric|min:0',
            'other_expenses'         => 'nullable|numeric|min:0',
            'supply_expenses'        => 'nullable|numeric|min:0',
            // Supply
            'supplier_contact_id'    => 'nullable|exists:contacts,id',
            'supply_order'           => 'nullable|string',
            'supply_season'          => 'nullable|string',
            'supply_qty'             => 'nullable|numeric|min:0',
            'supply_discount_pct'    => 'nullable|numeric|min:0|max:100',
            'supplied_qty'           => 'nullable|numeric|min:0',
            'cost_price'             => 'nullable|numeric|min:0',
            'supply_units_count'     => 'nullable|integer|min:0',
            // Transport
            'transport_contractor'   => 'nullable|string',
            'transport_units'        => 'nullable|integer|min:0',
            'transport_unit_cost'    => 'nullable|numeric|min:0',
            'transport_discount_qty' => 'nullable|numeric|min:0',
            'transport_price'        => 'nullable|numeric|min:0',
        ]);

        // Run all auto-calculations
        $validated = RawDeliveryOrder::calculate($validated);

        $order = RawDeliveryOrder::create($validated);

        return $this->success(
            $order->load(['client', 'supplier', 'packhouse']),
            'تم إنشاء أمر التوريد بنجاح',
            201
        );
    }

    public function show(RawDeliveryOrder $rawDeliveryOrder)
    {
        return $this->success(
            $rawDeliveryOrder->load(['client', 'supplier', 'packhouse', 'createdBy'])
        );
    }

    public function update(Request $request, RawDeliveryOrder $rawDeliveryOrder)
    {
        if ($rawDeliveryOrder->status === 'confirmed') {
            return $this->error('لا يمكن تعديل أمر مؤكد', 403);
        }

        $data = RawDeliveryOrder::calculate(array_merge($rawDeliveryOrder->toArray(), $request->all()));
        $rawDeliveryOrder->fill($data)->save();

        return $this->success(
            $this->reloadOrder($rawDeliveryOrder),
            'تم التحديث'
        );
    }

    public function destroy(RawDeliveryOrder $rawDeliveryOrder)
    {
        if ($rawDeliveryOrder->status === 'confirmed') {
            return $this->error('لا يمكن حذف أمر مؤكد', 403);
        }

        $deletedId = $rawDeliveryOrder->id;
        // Use the model instance delete so SoftDeletes and tenant behaviour are respected
        $rawDeliveryOrder->delete();

        return $this->success([
            'id' => $deletedId,
            'deleted' => true,
        ], 'تم الحذف');
    }

    public function confirm(RawDeliveryOrder $rawDeliveryOrder)
    {
        $rawDeliveryOrder->update([
            'status'       => 'confirmed',
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        return $this->success(
            $this->reloadOrder($rawDeliveryOrder),
            'تم تأكيد أمر التوريد'
        );
    }

    public function respond(Request $request, RawDeliveryOrder $rawDeliveryOrder)
    {
        $data = $request->validate([
            'response' => ['required', 'in:accepted,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $rawDeliveryOrder->update([
            'supplier_response' => $data['response'],
            'supplier_responded_by' => Auth::id(),
            'supplier_responded_at' => now(),
            'supplier_notes' => $data['notes'] ?? null,
        ]);

        return $this->success(
            $this->reloadOrder($rawDeliveryOrder),
            'تم تسجيل رد المورد'
        );
    }
}
