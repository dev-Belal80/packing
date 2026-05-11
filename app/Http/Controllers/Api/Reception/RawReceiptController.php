<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Reception\RawReceiptResource;
use App\Services\Reception\RawReceiptService;
use Illuminate\Http\Request;

class RawReceiptController extends BaseApiController
{
    public function __construct(private readonly RawReceiptService $service)
    {
    }

    private function normalizePayload(array $data): array
    {
        // Ensure transport_cost is never null
        if (!isset($data['transport_cost']) || $data['transport_cost'] === null) {
            $data['transport_cost'] = 0;
        } else {
            $data['transport_cost'] = max(0, floatval($data['transport_cost']));
        }

        $map = [
            'accountingPeriod' => 'accounting_period',
            'receiptPermissionNumber' => 'receipt_permission_number',
            'balanceRecordNumber' => 'balance_record_number',
            'date' => 'receipt_date',
            'branch' => 'branch',
            'packingStation' => 'packing_station',
            'farm' => 'farm',
            'itemType' => 'item_type',
            'discount' => 'discount',
            'receptionUnit' => 'reception_unit',
            'collectionUnit' => 'collection_unit',
            'boxesCount' => 'boxes_count',
            'quantity' => 'quantity_kg',
            'quantityPerBox' => 'quantity_per_box',
            'palletType' => 'pallet_type',
            'palletsCount' => 'pallets_count',
            'producedQuantity' => 'produced_quantity',
            'sortingAndLoss' => 'sorting_and_loss',
            'usedQuantity' => 'used_quantity',
            'inspector' => 'inspector',
            'vehicleNumber' => 'vehicle_number',
            'driverName' => 'driver_name',
            'entryWeight' => 'entry_weight',
            'transportType' => 'transport_type',
            'exitDateTime' => 'exit_date_time',
            'vehicleType' => 'vehicle_type',
            'exitWeight' => 'exit_weight',
            'transportContractor' => 'transport_contractor',
        ];

        foreach ($map as $inputKey => $storageKey) {
            if (array_key_exists($inputKey, $data) && ! array_key_exists($storageKey, $data)) {
                $data[$storageKey] = $data[$inputKey];
            }
        }

        return $data;
    }

    private function rawReceiptRules(): array
    {
        return [
            'accounting_period' => ['nullable', 'string', 'max:20'],
            'receipt_permission_number' => ['nullable', 'string', 'max:50'],
            'balance_record_number' => ['nullable', 'string', 'max:50'],
            'receipt_date' => ['nullable', 'date'],
            'branch' => ['nullable', 'string', 'max:255'],
            'packing_station' => ['nullable', 'string', 'max:255'],
            'farm' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'reception_unit' => ['nullable', 'string', 'max:50'],
            'collection_unit' => ['nullable', 'string', 'max:50'],
            'boxes_count' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'quantity_per_box' => ['nullable', 'numeric', 'min:0'],
            'pallet_type' => ['nullable', 'string', 'max:255'],
            'pallets_count' => ['nullable', 'integer', 'min:0'],
            'produced_quantity' => ['nullable', 'numeric', 'min:0'],
            'sorting_and_loss' => ['nullable', 'numeric', 'min:0'],
            'used_quantity' => ['nullable', 'numeric', 'min:0'],
            'inspector' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'packhouse_id' => ['nullable', 'integer', 'exists:packhouses,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'contact_role' => ['nullable', 'string', 'max:50'],
            'raw_material_type_id' => ['nullable', 'integer', 'exists:raw_material_types,id'],
            'gate_inquiry_id' => ['nullable', 'integer', 'exists:gate_inquiries,id'],
            'scale_note_id' => ['nullable', 'integer', 'exists:scale_notes,id'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'transport_type' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'transport_contractor' => ['nullable', 'string', 'max:255'],
            'entry_weight' => ['nullable', 'numeric', 'min:0'],
            'exit_weight' => ['nullable', 'numeric', 'min:0'],
            'exit_date_time' => ['nullable', 'date'],
            'quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'quality_result' => ['nullable', 'string', 'max:50'],
            'quality_notes' => ['nullable', 'string'],
            'is_partial' => ['sometimes', 'boolean'],
            'has_weight_dispute' => ['sometimes', 'boolean'],
            'weight_dispute_notes' => ['nullable', 'string'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'transport_cost' => ['required', 'numeric', 'min:0'],
            'approval_status' => ['nullable', 'string', 'max:50'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }

    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->integer('per_page', 15));
        return $this->paginated($paginator);
    }

    public function store(Request $request)
    {
        $payload = $this->normalizePayload($request->all());
        $data = validator($payload, $this->rawReceiptRules())->validate();

        $model = $this->service->create($data);
        return $this->success(new RawReceiptResource($model), 'created', 201);
    }

    public function show(int $id)
    {
        $model = $this->service->findOrFail($id);
        return $this->success(new RawReceiptResource($model));
    }

    public function update(Request $request, int $id)
    {
        $payload = $this->normalizePayload($request->all());
        $data = validator($payload, $this->rawReceiptRules())->validate();

        $model = $this->service->update($id, $data);
        return $this->success(new RawReceiptResource($model), 'updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
        return $this->success(['ok' => true], 'deleted');
    }

    public function approve(int $id)
    {
        $model = $this->service->approve($id);
        return $this->success(new RawReceiptResource($model), 'approved');
    }
}

