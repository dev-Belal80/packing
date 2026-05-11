<?php

namespace App\Http\Controllers\Api\Production;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Production\SortRecordResource;
use App\Services\Production\SortRecordService;
use Illuminate\Http\Request;

class SortRecordController extends BaseApiController
{
    public function __construct(private readonly SortRecordService $service)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->integer('per_page', 15));
        return $this->paginated($paginator);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'production_order_id' => ['nullable', 'integer'],
            'grade_a' => ['nullable', 'numeric', 'min:0'],
            'grade_b' => ['nullable', 'numeric', 'min:0'],
            'grade_c' => ['nullable', 'numeric', 'min:0'],
            'waste' => ['nullable', 'numeric', 'min:0'],
        ]);

        $model = $this->service->create($data);
        return $this->success(new SortRecordResource($model), 'created', 201);
    }

    public function show(int $id)
    {
        $model = $this->service->findOrFail($id);
        return $this->success(new SortRecordResource($model));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'grade_a' => ['nullable', 'numeric', 'min:0'],
            'grade_b' => ['nullable', 'numeric', 'min:0'],
            'grade_c' => ['nullable', 'numeric', 'min:0'],
            'waste' => ['nullable', 'numeric', 'min:0'],
        ]);

        $model = $this->service->update($id, $data);
        return $this->success(new SortRecordResource($model), 'updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
        return $this->success(['ok' => true], 'deleted');
    }
}

