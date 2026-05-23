<?php

namespace App\Http\Resources\Production;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sortLines = $this->sortRecordLines;
        $rawReceipt = $this->rawReceipt;

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'reference_no' => $this->reference_no,
            'accounting_period' => $this->accounting_period,
            'branch' => $this->branch,
            'order_date' => $this->order_date,
            'special_code' => $this->special_code,
            'packhouse_id' => $this->packhouse_id,
            'raw_receipt_id' => $this->raw_receipt_id,
            'product_id' => $this->product_id,
            'pallet_type_id' => $this->pallet_type_id,
            'production_line_id' => $this->production_line_id,
            'production_stage_id' => $this->production_stage_id,
            'supplier_contact_id' => $this->supplier_contact_id,
            'client_contact_id' => $this->client_contact_id,
            'supervisor_id' => $this->supervisor_id,
            'other_supervisor_ids' => $this->other_supervisor_ids,
            'target_qty_kg' => $this->target_qty_kg,
            'actual_input_kg' => $this->actual_input_kg,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'pause_reason' => $this->pause_reason,
            'cancel_reason' => $this->cancel_reason,
            'cancelled_by' => $this->cancelled_by,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'raw_remaining_qty_kg' => $rawReceipt?->available_qty,
            'raw_price_per_kg' => $rawReceipt?->price_per_kg,
            'sorted_qty_kg' => $sortLines?->sum('line_total'),
            'waste_qty_kg' => $sortLines?->sum('waste_kg'),
            'packhouse' => $this->whenLoaded('packhouse'),
            'raw_receipt' => $this->whenLoaded('rawReceipt'),
            'product' => $this->whenLoaded('product'),
            'pallet_type' => $this->whenLoaded('palletType'),
            'production_line' => $this->whenLoaded('productionLine'),
            'production_stage' => $this->whenLoaded('productionStage'),
            'supervisor' => $this->whenLoaded('supervisor'),
            'supplier_contact' => $this->whenLoaded('supplierContact'),
            'client_contact' => $this->whenLoaded('clientContact'),
            'sort_record_lines' => $this->whenLoaded('sortRecordLines'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
