<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ScaleNote;
use Illuminate\Http\Request;

class ScaleNoteController extends BaseApiController
{
    private function query()
    {
        return ScaleNote::query()->with(['contact', 'packhouse', 'gateInquiry']);
    }

    public function index(Request $request)
    {
        $paginator = $this->query()
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('note_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('note_date', '<=', $request->date('date_to')))
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
            'note_date' => ['required', 'date'],
            'note_time' => ['nullable', 'date_format:H:i'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'raw_material_type_id' => ['nullable', 'exists:raw_material_types,id'],
            'raw_type' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'note_type' => ['nullable', 'string', 'max:255'],
            'entry_count' => ['nullable', 'numeric', 'min:0'],
            'exit_count' => ['nullable', 'numeric', 'min:0'],
            'box_weight' => ['nullable', 'numeric', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'farm_code' => ['nullable', 'string', 'max:255'],
            'season' => ['nullable', 'string', 'max:255'],
            'full_weight' => ['nullable', 'numeric', 'min:0'],
            'empty_weight' => ['nullable', 'numeric', 'min:0'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'tare_weight' => ['nullable', 'numeric', 'min:0'],
            'is_manual' => ['sometimes', 'boolean'],
            'manual_reason' => ['nullable', 'required_if:is_manual,true', 'string'],
            'discount_1_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_2' => ['nullable', 'numeric', 'min:0'],
            'discount_3' => ['nullable', 'numeric', 'min:0'],
            'broken_boxes' => ['nullable', 'integer', 'min:0'],
            'partial_boxes' => ['nullable', 'integer', 'min:0'],
            'harvest_contractor' => ['nullable', 'string', 'max:255'],
            'harvest_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'inspection_report' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'gate_inquiry_id' => ['nullable', 'exists:gate_inquiries,id'],
        ]);

        $note = ScaleNote::query()->create($data);

        return $this->success($this->query()->findOrFail($note->id), 'created', 201);
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
            'note_date' => ['sometimes', 'date'],
            'note_time' => ['nullable', 'date_format:H:i'],
            'contact_id' => ['sometimes', 'exists:contacts,id'],
            'raw_material_type_id' => ['nullable', 'exists:raw_material_types,id'],
            'raw_type' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'note_type' => ['nullable', 'string', 'max:255'],
            'entry_count' => ['nullable', 'numeric', 'min:0'],
            'exit_count' => ['nullable', 'numeric', 'min:0'],
            'box_weight' => ['nullable', 'numeric', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['nullable', 'string', 'max:255'],
            'farm_code' => ['nullable', 'string', 'max:255'],
            'season' => ['nullable', 'string', 'max:255'],
            'full_weight' => ['nullable', 'numeric', 'min:0'],
            'empty_weight' => ['nullable', 'numeric', 'min:0'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'tare_weight' => ['nullable', 'numeric', 'min:0'],
            'is_manual' => ['sometimes', 'boolean'],
            'manual_reason' => ['nullable', 'required_if:is_manual,true', 'string'],
            'discount_1_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_2' => ['nullable', 'numeric', 'min:0'],
            'discount_3' => ['nullable', 'numeric', 'min:0'],
            'broken_boxes' => ['nullable', 'integer', 'min:0'],
            'partial_boxes' => ['nullable', 'integer', 'min:0'],
            'harvest_contractor' => ['nullable', 'string', 'max:255'],
            'harvest_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'inspection_report' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'gate_inquiry_id' => ['nullable', 'exists:gate_inquiries,id'],
        ]);

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

