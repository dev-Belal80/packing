<?php

namespace App\Http\Resources\Export;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PalletDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,

            'packhouse_id' => $this->packhouse_id,
            'sort_record_id' => $this->sort_record_id,
            'production_line_id' => $this->production_line_id,
            'pallet_type_id' => $this->pallet_type_id,
            'product_id' => $this->product_id,
            'production_order_id' => $this->production_order_id,

            'reference_no' => $this->reference_no,
            'wooden_pallet_no' => $this->wooden_pallet_no,
            'order_number' => $this->order_number,
            'final_order_no' => $this->final_order_no,
            'branch' => $this->branch,

            'pallet_date' => $this->pallet_date?->format('Y-m-d'),
            'pallet_time' => $this->pallet_time,
            'end_date' => $this->end_date?->format('Y-m-d'),
            'end_time' => $this->end_time,

            'client_contact_id' => $this->client_contact_id,
            'client_code' => $this->client_code,
            'supplier_contact_id' => $this->supplier_contact_id,

            'lot_no' => $this->lot_no,
            'fridge_id' => $this->fridge_id,
            'brand_id' => $this->brand_id,
            'punnet_sticker_id' => $this->punnet_sticker_id,

            'raw_type' => $this->raw_type,
            'package_type' => $this->package_type,
            'size' => $this->size,
            'grade' => $this->grade,
            'storage_location' => $this->storage_location,

            'cartons_count' => $this->cartons_count,
            'actual_weight' => $this->actual_weight,
            'net_weight' => $this->net_weight,
            'weight_diff' => $this->weight_diff,
            'total_weight_kg' => $this->total_weight_kg,

            'cooling_start' => $this->cooling_start?->format('Y-m-d\TH:i:s'),
            'cooling_end' => $this->cooling_end?->format('Y-m-d\TH:i:s'),

            'stickers' => $this->stickers,
            'customer_lot_no' => $this->customer_lot_no,
            'has_carton' => $this->has_carton,
            'has_punnet' => $this->has_punnet,
            'no_label' => $this->no_label,
            'original_pallet_ref' => $this->original_pallet_ref,
            'special_specs' => $this->special_specs,

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

            'receipt_confirmed' => $this->receipt_confirmed,
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d\TH:i:s'),
            'confirmed_by' => $this->confirmed_by,

            'is_shipped' => $this->is_shipped,
            'is_ready_to_ship' => $this->is_ready_to_ship,

            'created_by' => $this->created_by,

            'packhouse' => $this->whenLoaded('packhouse', fn () => [
                'id' => $this->packhouse?->id,
                'name' => $this->packhouse?->name,
            ]),
            'production_line' => $this->whenLoaded('productionLine', fn () => [
                'id' => $this->productionLine?->id,
                'name' => $this->productionLine?->name,
            ]),
            'pallet_type' => $this->whenLoaded('palletType', fn () => [
                'id' => $this->palletType?->id,
                'name' => $this->palletType?->name,
            ]),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client?->id,
                'name' => $this->client?->name,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
            ]),
            'fridge' => $this->whenLoaded('fridge', fn () => [
                'id' => $this->fridge?->id,
                'name' => $this->fridge?->name,
            ]),
            'sort_record' => $this->whenLoaded('sortRecord', fn () => [
                'id' => $this->sortRecord?->id,
                'reference_no' => $this->sortRecord?->reference_no,
                'status' => $this->sortRecord?->status,
                'sort_date' => $this->sortRecord?->sort_date?->format('Y-m-d'),
            ]),
            'production_order' => $this->whenLoaded('productionOrder', fn () => [
                'id' => $this->productionOrder?->id,
                'reference_no' => $this->productionOrder?->reference_no,
            ]),
            'latest_cooling' => $this->whenLoaded('latestCooling', fn () => [
                'id' => $this->latestCooling?->id,
                'fridge_id' => $this->latestCooling?->fridge_id,
                'entry_temp' => $this->latestCooling?->entry_temp,
                'entered_at' => $this->latestCooling?->entered_at?->format('Y-m-d\TH:i:s'),
                'ready_at' => $this->latestCooling?->ready_at?->format('Y-m-d\TH:i:s'),
                'has_temp_alert' => $this->latestCooling?->has_temp_alert,
            ]),
            'shipping_policies' => $this->whenLoaded('shippingPolicies', fn () => $this->shippingPolicies
                ->map(fn ($policy) => [
                    'id' => $policy->id,
                    'reference_no' => $policy->reference_no,
                    'status' => $policy->status,
                ])->values()),

            'confirmed_by_user' => $this->whenLoaded('confirmedBy', fn () => [
                'id' => $this->confirmedBy?->id,
                'name' => $this->confirmedBy?->name,
                'email' => $this->confirmedBy?->email,
            ]),
            'created_by_user' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
                'email' => $this->createdBy?->email,
            ]),

            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d\TH:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d\TH:i:s'),
        ];
    }
}
