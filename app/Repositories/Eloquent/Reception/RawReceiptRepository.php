<?php

namespace App\Repositories\Eloquent\Reception;

use App\Models\RawReceipt;
use App\Models\RawDeliveryOrder;
use App\Repositories\Interfaces\Reception\RawReceiptRepositoryInterface;

class RawReceiptRepository implements RawReceiptRepositoryInterface
{
    private function query(array $filters = [])
    {
        $query = RawReceipt::query()->with([
            'contact',
            'rawMaterialType',
            'gateInquiry',
            'scaleNote',
            'packhouse',
            'approvedBy',
        ]);

        if (! empty($filters['available_only'])) {
            $query->whereRaw('COALESCE(used_quantity, 0) + COALESCE((
                select sum(target_qty_kg)
                from production_orders
                where production_orders.raw_receipt_id = raw_receipts.id
                  and production_orders.deleted_at is null
                  and production_orders.status in (\'draft\', \'reserved\', \'dispatched\', \'paused\')
            ), 0) < quantity_kg');
        }

        if (! empty($filters['packhouse_id'])) {
            $query->where('packhouse_id', $filters['packhouse_id']);
        }

        if (! empty($filters['raw_material_type_id'])) {
            $query->where('raw_material_type_id', $filters['raw_material_type_id']);
        }

        return $query;
    }

    public function paginate(int $perPage = 15, array $filters = [])
    {
        return $this->query($filters)->latest('id')->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data)
    {
        $model = RawReceipt::query()->create($data);

        // If this receipt is linked to a delivery order, update the supplied_qty on the order
        if (! empty($data['raw_delivery_order_id'])) {
            try {
                $order = RawDeliveryOrder::withoutGlobalScopes()->find($data['raw_delivery_order_id']);
                if ($order) {
                    $order->supplied_qty = ($order->supplied_qty ?? 0) + (float) ($model->quantity_kg ?? 0);
                    $order->save();
                }
            } catch (\Throwable $e) {
                // swallow - don't block receipt creation if update fails
            }
        }

        return $this->query()->findOrFail($model->id);
    }

    public function update(int $id, array $data)
    {
        $model = $this->findOrFail($id);
        $model->fill($data);
        $model->save();

        return $this->query()->findOrFail($model->id);
    }

    public function delete(int $id): void
    {
        RawReceipt::query()->whereKey($id)->delete();
    }
}

