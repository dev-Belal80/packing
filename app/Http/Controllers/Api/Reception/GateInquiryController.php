<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\GateInquiry;
use Illuminate\Http\Request;

class GateInquiryController extends BaseApiController
{
    private function query()
    {
        return GateInquiry::query()->with(['contact', 'packhouse', 'rawMaterialType', 'deliveryOrder']);
    }

    public function index(Request $request)
    {
        $paginator = $this->query()
            ->when($request->filled('inquiry_status'), fn ($query) => $query->where('inquiry_status', $request->string('inquiry_status')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('entry_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('entry_date', '<=', $request->date('date_to')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($paginator);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'packhouse_id' => ['required', 'exists:packhouses,id'],
            'accounting_period' => ['nullable', 'string', 'max:20'],
            'branch' => ['nullable', 'string', 'max:255'],
            'entry_date' => ['required', 'date'],
            'entry_time' => ['nullable', 'date_format:H:i'],
            'code' => ['nullable', 'string', 'max:50'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'raw_material_type_id' => ['nullable', 'exists:raw_material_types,id'],
            'raw_type' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_size' => ['nullable', 'string', 'max:255'],
            'counter_on_entry' => ['nullable', 'numeric', 'min:0'],
            'counter_on_exit' => ['nullable', 'numeric', 'min:0'],
            'responsible_employee' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'cargo_desc_entry' => ['nullable', 'string'],
            'cargo_desc_exit' => ['nullable', 'string'],
            'exit_date' => ['nullable', 'date'],
            'exit_time' => ['nullable', 'date_format:H:i'],
            'inquiry_status' => ['nullable', 'string', 'in:pending,received,rejected'],
            'expected_qty' => ['nullable', 'numeric', 'min:0'],
            'delivery_order_id' => ['nullable', 'exists:raw_delivery_orders,id'],
        ]);

        $data['status'] ??= $data['inquiry_status'] ?? 'pending';
        $data['inquiry_status'] ??= $data['status'];

        $model = GateInquiry::query()->create($data);

        return $this->success($this->query()->findOrFail($model->id), 'created', 201);
    }

    public function show(int $id)
    {
        return $this->success($this->query()->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'packhouse_id' => ['sometimes', 'exists:packhouses,id'],
            'accounting_period' => ['nullable', 'string', 'max:20'],
            'branch' => ['nullable', 'string', 'max:255'],
            'entry_date' => ['sometimes', 'date'],
            'entry_time' => ['nullable', 'date_format:H:i'],
            'code' => ['nullable', 'string', 'max:50'],
            'contact_id' => ['sometimes', 'exists:contacts,id'],
            'raw_material_type_id' => ['nullable', 'exists:raw_material_types,id'],
            'raw_type' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_size' => ['nullable', 'string', 'max:255'],
            'counter_on_entry' => ['nullable', 'numeric', 'min:0'],
            'counter_on_exit' => ['nullable', 'numeric', 'min:0'],
            'responsible_employee' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'cargo_desc_entry' => ['nullable', 'string'],
            'cargo_desc_exit' => ['nullable', 'string'],
            'exit_date' => ['nullable', 'date'],
            'exit_time' => ['nullable', 'date_format:H:i'],
            'inquiry_status' => ['nullable', 'string', 'in:pending,received,rejected'],
            'expected_qty' => ['nullable', 'numeric', 'min:0'],
            'delivery_order_id' => ['nullable', 'exists:raw_delivery_orders,id'],
        ]);

        if (array_key_exists('inquiry_status', $data) && ! array_key_exists('status', $data)) {
            $data['status'] = $data['inquiry_status'];
        }

        if (array_key_exists('status', $data) && ! array_key_exists('inquiry_status', $data)) {
            $data['inquiry_status'] = $data['status'];
        }

        $model = $this->query()->findOrFail($id);
        $model->fill($data)->save();

        return $this->success($this->query()->findOrFail($id), 'updated');
    }

    public function destroy(int $id)
    {
        $this->query()->whereKey($id)->delete();
        return $this->success(['ok' => true], 'deleted');
    }
}

