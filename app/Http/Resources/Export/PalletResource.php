<?php

namespace App\Http\Resources\Export;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'packhouse_id' => $this->packhouse_id,
            'packhouse' => $this->whenLoaded('packhouse', fn () => [
                'id' => $this->packhouse?->id,
                'name' => $this->packhouse?->name,
            ]),
            'pallet_date' => $this->pallet_date?->format('Y-m-d'),
            'pallet_time' => $this->pallet_time,
            'raw_type' => $this->raw_type,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
            ]),
            'lot_no' => $this->lot_no,
            'size' => $this->size,
            'grade' => $this->grade,
            'order_number' => $this->order_number,
            'cooling_start' => $this->cooling_start?->format('Y-m-d\TH:i:s'),
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'building' => 'قيد التجهيز',
                'built' => 'مكتملة',
                'cooling' => 'في التبريد',
                'cooled' => 'مبردة',
                'confirmed' => 'مؤكدة',
                'shipped' => 'مشحونة',
                default => $this->status,
            },
            'is_shipped' => $this->is_shipped,
            'is_ready_to_ship' => $this->is_ready_to_ship,
            'actual_weight' => $this->actual_weight === null ? null : (float) $this->actual_weight,
            'net_weight' => $this->net_weight === null ? null : (float) $this->net_weight,
            'cartons_count' => $this->cartons_count,
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s'),
        ];
    }
}
