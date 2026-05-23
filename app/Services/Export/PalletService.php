<?php

namespace App\Services\Export;

use App\Models\Pallet;
use App\Models\PalletCooling;
use App\Models\SortRecordLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PalletService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Pallet::query()
            ->with([
                'packhouse',
                'productionLine',
                'palletType',
                'client',
                'supplier',
                'product',
                'fridge',
                'sortRecord',
                'productionOrder',
                'latestCooling',
            ])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('pallet_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('pallet_date', '<=', $date))
            ->when($filters['packhouse_id'] ?? null, fn ($query, $id) => $query->where('packhouse_id', $id))
            ->when($filters['client_contact_id'] ?? null, fn ($query, $id) => $query->where('client_contact_id', $id))
            ->when($filters['product_id'] ?? null, fn ($query, $id) => $query->where('product_id', $id))
            ->when($filters['production_line_id'] ?? null, fn ($query, $id) => $query->where('production_line_id', $id))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('wooden_pallet_no', 'like', "%{$search}%")
                        ->orWhere('order_number', 'like', "%{$search}%")
                        ->orWhere('final_order_no', 'like', "%{$search}%")
                        ->orWhere('client_code', 'like', "%{$search}%")
                        ->orWhere('lot_no', 'like', "%{$search}%");
                });
            })
            ->latest('pallet_date')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrFail(Pallet $pallet): Pallet
    {
        return $pallet->load([
            'packhouse',
            'productionLine',
            'palletType',
            'client',
            'supplier',
            'product',
            'fridge',
            'sortRecord',
            'productionOrder',
            'latestCooling',
            'shippingPolicies',
            'confirmedBy',
            'createdBy',
        ]);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $pallet = new Pallet($data);
            $pallet->save();

            return $this->findOrFail($pallet);
        });
    }

    public function update(Pallet $pallet, array $data)
    {
        return DB::transaction(function () use ($pallet, $data) {
            $pallet->fill($data);
            $pallet->save();

            return $this->findOrFail($pallet);
        });
    }

    public function delete(Pallet $pallet): void
    {
        $pallet->delete();
    }

    public function cooling(Pallet $pallet, array $data = [])
    {
        return DB::transaction(function () use ($pallet, $data) {
            $pallet->fill([
                'fridge_id' => $data['fridge_id'] ?? $pallet->fridge_id,
                'cooling_start' => $data['cooling_start'] ?? now(),
                'cooling_end' => $data['cooling_end'] ?? null,
                'status' => 'cooling',
            ]);
            $pallet->save();

            PalletCooling::query()->create([
                'pallet_id' => $pallet->id,
                'fridge_id' => $data['fridge_id'] ?? $pallet->fridge_id,
                'entry_temp' => $data['entry_temp'] ?? null,
                'entered_at' => $data['cooling_start'] ?? now(),
                'ready_at' => $data['cooling_end'] ?? null,
                'has_temp_alert' => $data['has_temp_alert'] ?? false,
                'recorded_by' => $data['recorded_by'] ?? auth()->id(),
            ]);

            return $this->findOrFail($pallet);
        });
    }

    public function confirmReceipt(Pallet $pallet)
    {
        return DB::transaction(function () use ($pallet) {
            $pallet->fill([
                'receipt_confirmed' => true,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
                'status' => 'confirmed',
            ]);
            $pallet->save();

            return $this->findOrFail($pallet);
        });
    }

    public function availableOrders(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return SortRecordLine::query()
            ->with([
                'sortRecord.packhouse',
                'productionLine',
                'productionOrder.product',
            ])
            ->whereHas('sortRecord', function ($query) {
                $query->where('status', 'posted');
            })
            ->when($filters['packhouse_id'] ?? null, function ($query, $id) {
                $query->whereHas('sortRecord', fn ($sortRecord) => $sortRecord->where('packhouse_id', $id));
            })
            ->when($filters['lot_only'] ?? false, fn ($query) => $query->whereNotNull('lot_no'))
            ->when($filters['product_id'] ?? null, fn ($query, $id) => $query->whereHas('productionOrder', fn ($order) => $order->where('product_id', $id)))
            ->when($filters['production_line_id'] ?? null, fn ($query, $id) => $query->where('production_line_id', $id))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('lot_no', 'like', "%{$search}%")
                        ->orWhereHas('sortRecord', fn ($sortRecord) => $sortRecord->where('reference_no', 'like', "%{$search}%"))
                        ->orWhereHas('productionOrder', fn ($order) => $order->where('reference_no', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}

