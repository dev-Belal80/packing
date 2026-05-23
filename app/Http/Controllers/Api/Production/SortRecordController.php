<?php

namespace App\Http\Controllers\Api\Production;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Production\SortRecordResource;
use App\Models\SortRecord;
use App\Services\Production\SortRecordService;
use Illuminate\Http\Request;

class SortRecordController extends BaseApiController
{
    public function __construct(private readonly SortRecordService $service)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->only(['status', 'date_from', 'date_to', 'search']), $request->integer('per_page', 20));
        return $this->paginated($paginator);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'packhouse_id' => ['required', 'exists:packhouses,id'],
            'sort_date' => ['required', 'date'],
            'sort_time' => ['nullable', 'date_format:H:i'],
            'accounting_period' => ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.raw_type' => ['required', 'string'],
            'lines.*.lot_no' => ['nullable', 'string'],
            'lines.*.production_line_id' => ['nullable', 'exists:production_lines,id'],
            'lines.*.production_order_id' => ['nullable', 'exists:production_orders,id'],
            'lines.*.grade_a_kg' => ['required', 'numeric', 'min:0'],
            'lines.*.grade_b_kg' => ['required', 'numeric', 'min:0'],
            'lines.*.grade_c_kg' => ['required', 'numeric', 'min:0'],
            'lines.*.waste_kg' => ['required', 'numeric', 'min:0'],
            'lines.*.returned_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payload = $data;
        $payload['branch'] = $payload['branch'] ?? 'الرئيسي';
        unset($payload['lines']);

        $model = $this->service->createWithLines($payload, $data['lines']);
        return $this->success(new SortRecordResource($model), 'created', 201);
    }

    public function show(SortRecord $sortRecord)
    {
        $model = $this->service->findOrFail($sortRecord->id);
        return $this->success(new SortRecordResource($model));
    }

    public function update(Request $request, SortRecord $sortRecord)
    {
        if ($sortRecord->status === 'posted') {
            return $this->error('Forbidden', 403, null, 'sort-record.update');
        }

        $data = $request->validate([
            'packhouse_id' => ['sometimes', 'exists:packhouses,id'],
            'sort_date' => ['sometimes', 'date'],
            'sort_time' => ['nullable', 'date_format:H:i'],
            'accounting_period' => ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.raw_type' => ['required_with:lines', 'string'],
            'lines.*.lot_no' => ['nullable', 'string'],
            'lines.*.production_line_id' => ['nullable', 'exists:production_lines,id'],
            'lines.*.production_order_id' => ['nullable', 'exists:production_orders,id'],
            'lines.*.grade_a_kg' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.grade_b_kg' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.grade_c_kg' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.waste_kg' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.returned_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payload = $data;
        $lines = $payload['lines'] ?? null;
        unset($payload['lines']);

        $model = $this->service->updateWithLines($sortRecord, $payload, $lines);
        return $this->success(new SortRecordResource($model), 'updated');
    }

    public function destroy(SortRecord $sortRecord)
    {
        if ($sortRecord->status === 'posted') {
            return $this->error('Forbidden', 403, null, 'sort-record.delete');
        }

        $this->service->delete($sortRecord);
        return $this->success(['ok' => true], 'deleted');
    }

    public function post(SortRecord $sortRecord)
    {
        if ($sortRecord->status !== 'draft') {
            return $this->error('الفرزة مش في حالة مسودة', 422);
        }

        if ($sortRecord->lines()->count() === 0) {
            return $this->error('لازم يكون في أصناف قبل الترحيل', 422);
        }

        if ((float) $sortRecord->total_sort <= 0) {
            return $this->error('إجمالي الفرزة لازم يكون أكبر من صفر', 422);
        }

        $model = $this->service->post($sortRecord, (int) auth()->id());
        return $this->success(new SortRecordResource($model), 'posted');
    }
}

