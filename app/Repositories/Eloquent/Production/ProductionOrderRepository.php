<?php

namespace App\Repositories\Eloquent\Production;

use App\Models\ProductionOrder;
use App\Models\RawReceipt;
use App\Models\StockTransaction;
use App\Repositories\Interfaces\Production\ProductionOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionOrderRepository implements ProductionOrderRepositoryInterface
{
    private function syncRawReceiptStatus(?int $rawReceiptId): void
    {
        if (empty($rawReceiptId)) {
            return;
        }
        $receipt = RawReceipt::query()->whereKey($rawReceiptId)->lockForUpdate()->first();
        if (! $receipt) {
            throw ValidationException::withMessages([
                'raw_receipt_id' => 'اختيار الخام غير صالح.',
            ]);
        }

        $reserved = (float) ($receipt->reserved_qty ?? 0);
        $dispatched = (float) ($receipt->dispatched_qty ?? 0);
        $consumed = (float) ($receipt->consumed_qty ?? 0);

        if ($consumed > 0.0001 && $consumed >= ((float) $receipt->quantity_kg) * 0.99) {
            $receipt->status = 'consumed';
        } elseif ($dispatched > 0.0001) {
            $receipt->status = 'dispatched';
        } elseif ($reserved > 0.0001) {
            $receipt->status = 'reserved';
        } else {
            $receipt->status = 'in_stock';
        }

        $receipt->save();
    }

    private function activeOrderStatusPayload(string $status): array
    {
        return ['status' => $status];
    }

    public function paginate(int $perPage = 15, array $filters = [])
    {
        return ProductionOrder::query()
            ->with([
                'packhouse',
                'rawReceipt',
                'product',
                'palletType',
                'productionLine',
                'productionStage',
                'supervisor',
                'supplierContact',
                'clientContact',
                'sortRecordLines',
            ])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['packhouse_id']), fn ($query) => $query->where('packhouse_id', $filters['packhouse_id']))
            ->when(! empty($filters['production_line_id']), fn ($query) => $query->where('production_line_id', $filters['production_line_id']))
            ->when(! empty($filters['production_stage_id']), fn ($query) => $query->where('production_stage_id', $filters['production_stage_id']))
            ->when(! empty($filters['supervisor_id']), fn ($query) => $query->where('supervisor_id', $filters['supervisor_id']))
            ->when(! empty($filters['product_id']), fn ($query) => $query->where('product_id', $filters['product_id']))
            ->when(! empty($filters['raw_receipt_id']), fn ($query) => $query->where('raw_receipt_id', $filters['raw_receipt_id']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('order_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('order_date', '<=', $filters['date_to']))
            ->when(! empty($filters['search']), fn ($query) => $query->where('reference_no', 'like', '%' . $filters['search'] . '%'))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return ProductionOrder::query()
            ->with([
                'packhouse',
                'rawReceipt',
                'product',
                'palletType',
                'productionLine',
                'productionStage',
                'supervisor',
                'supplierContact',
                'clientContact',
                'sortRecordLines',
            ])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = in_array(($data['status'] ?? null), ['draft', 'reserved'], true)
                ? 'reserved'
                : ($data['status'] ?? 'reserved');

            $model = ProductionOrder::query()->create($data);
            // Reserve the quantity on the raw receipt
            $receipt = RawReceipt::query()->whereKey($model->raw_receipt_id)->lockForUpdate()->first();
            if ($receipt) {
                $receipt->reserve((float) $model->target_qty_kg);
            }

            $this->syncRawReceiptStatus($model->raw_receipt_id);

            return $model;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $model = $this->findOrFail($id);
            $originalRawReceiptId = $model->raw_receipt_id;
            $originalStatus = (string) $model->status;
            $originalTargetQty = (float) $model->target_qty_kg;

            $model->fill($data);
            $model->save();

            if ((int) $originalRawReceiptId !== (int) $model->raw_receipt_id || $originalTargetQty !== (float) $model->target_qty_kg || $originalStatus !== (string) $model->status) {
                $this->syncRawReceiptStatus($originalRawReceiptId);
                $this->syncRawReceiptStatus($model->raw_receipt_id);
            }

            return $model;
        });
    }

    public function dispatch(int $id)
    {
        return DB::transaction(function () use ($id) {
            // Lock the production order row first to avoid concurrent transitions
            $model = ProductionOrder::query()->whereKey($id)->lockForUpdate()->first();
            if (! $model) {
                throw ValidationException::withMessages(['id' => 'أمر التشغيل غير موجود.']);
            }

            if ($model->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن صرف أمر ملغي.',
                ]);
            }

            if ($model->status === 'dispatched') {
                // idempotent: already dispatched
                return $this->findOrFail($model->id);
            }

            // Lock the related raw receipt for capacity check and update
            $receipt = RawReceipt::query()->whereKey($model->raw_receipt_id)->lockForUpdate()->first();
            if (! $receipt) {
                throw ValidationException::withMessages([
                    'raw_receipt_id' => 'اختيار الخام غير صالح.',
                ]);
            }
            $currentDispatched = (float) ($receipt->dispatched_qty ?? 0);
            $nextDispatched = $currentDispatched + (float) $model->target_qty_kg;

            if ($nextDispatched - (float) $receipt->quantity_kg > 0.0001) {
                throw ValidationException::withMessages([
                    'raw_receipt_id' => 'الخام لا يحتوي على كمية كافية للصرف.',
                ]);
            }

            // Persist receipt update first, then mark order dispatched
            $receipt->dispatch((float) $model->target_qty_kg);
            $receipt->save();

            // create an outbound stock transaction for this dispatch
            StockTransaction::create([
                'type' => 'out',
                'reason' => 'production_dispatch',
                'raw_receipt_id' => $receipt->id,
                'production_order_id' => $model->id,
                'quantity_kg' => (float) $model->target_qty_kg,
                'created_by' => auth()->id(),
            ]);

            // create a picking record for traceability
            \App\Models\ProductionOrderPicking::create([
                'production_order_id' => $model->id,
                'raw_receipt_id' => $receipt->id,
                'dispatched_qty_kg' => (float) $model->target_qty_kg,
                'dispatched_by' => auth()->id(),
                'dispatched_at' => now(),
            ]);

            $model->status = 'dispatched';
            $model->save();

            $this->syncRawReceiptStatus($model->raw_receipt_id);

            return $this->findOrFail($model->id);
        });
    }

    public function cancel(int $id)
    {
        return DB::transaction(function () use ($id) {
            // Lock the production order row
            $model = ProductionOrder::query()->whereKey($id)->lockForUpdate()->first();
            if (! $model) {
                throw ValidationException::withMessages(['id' => 'أمر التشغيل غير موجود.']);
            }

            // If previously dispatched, reverse dispatch on the raw receipt and create return txn
            if ($model->status === 'dispatched') {
                $receipt = RawReceipt::query()->whereKey($model->raw_receipt_id)->lockForUpdate()->first();
                if ($receipt) {
                    $decrement = min((float) ($receipt->dispatched_qty ?? 0), (float) $model->target_qty_kg);
                    $receipt->decrement('dispatched_qty', $decrement);
                    $receipt->save();

                    StockTransaction::create([
                        'type' => 'in',
                        'reason' => 'return',
                        'raw_receipt_id' => $receipt->id,
                        'production_order_id' => $model->id,
                        'quantity_kg' => $decrement,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // If order was reserved (or draft), release the reservation before deleting
            if (in_array($model->status, ['reserved', 'draft'], true)) {
                $receipt = RawReceipt::query()->whereKey($model->raw_receipt_id)->lockForUpdate()->first();
                if ($receipt) {
                    $releaseQty = min((float) ($receipt->reserved_qty ?? 0), (float) $model->target_qty_kg);
                    if ($releaseQty > 0) {
                        $receipt->releaseReservation($releaseQty);
                        $receipt->save();
                    }
                }
            }

            // If order was reserved (or draft), release the reservation
            if (in_array($model->status, ['reserved', 'draft'], true)) {
                $receipt = RawReceipt::query()->whereKey($model->raw_receipt_id)->lockForUpdate()->first();
                if ($receipt) {
                    $releaseQty = min((float) ($receipt->reserved_qty ?? 0), (float) $model->target_qty_kg);
                    if ($releaseQty > 0) {
                        $receipt->releaseReservation($releaseQty);
                        $receipt->save();
                    }
                }
            }

            $model->status = 'cancelled';
            $model->save();

            $this->syncRawReceiptStatus($model->raw_receipt_id);

            return $this->findOrFail($model->id);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Lock the production order before deletion
            $model = ProductionOrder::query()->whereKey($id)->lockForUpdate()->first();
            if (! $model) {
                return;
            }

            if ($model->status === 'dispatched') {
                $receipt = RawReceipt::query()->whereKey($model->raw_receipt_id)->lockForUpdate()->first();
                if ($receipt) {
                    $decrement = min((float) ($receipt->dispatched_qty ?? 0), (float) $model->target_qty_kg);
                    $receipt->decrement('dispatched_qty', $decrement);
                    $receipt->save();

                    StockTransaction::create([
                        'type' => 'in',
                        'reason' => 'return',
                        'raw_receipt_id' => $receipt->id,
                        'production_order_id' => $model->id,
                        'quantity_kg' => $decrement,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $this->syncRawReceiptStatus($model->raw_receipt_id);
            ProductionOrder::query()->whereKey($model->getKey())->delete();
            $this->syncRawReceiptStatus($model->raw_receipt_id);
        });
    }
}

