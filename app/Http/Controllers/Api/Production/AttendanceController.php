<?php

namespace App\Http\Controllers\Api\Production;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Production\AttendanceResource;
use App\Services\Production\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends BaseApiController
{
    public function __construct(private readonly AttendanceService $service)
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
            'packhouse_id' => ['required', 'integer', 'exists:packhouses,id'],
            'employee_name' => ['required', 'string', 'max:255'],
            'job_title_id' => ['required', 'integer', 'exists:job_titles,id'],
            'attendance_date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'hours_worked' => ['nullable', 'numeric', 'min:0'],
            'calculated_wage' => ['nullable', 'numeric', 'min:0'],
            'is_present' => ['nullable', 'boolean'],
            'absence_reason' => ['nullable', 'string'],
        ]);

        $model = $this->service->create($data);
        return $this->success(new AttendanceResource($model), 'created', 201);
    }

    public function show(int $id)
    {
        $model = $this->service->findOrFail($id);
        return $this->success(new AttendanceResource($model));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'packhouse_id' => ['sometimes', 'integer', 'exists:packhouses,id'],
            'employee_name' => ['sometimes', 'string', 'max:255'],
            'job_title_id' => ['sometimes', 'integer', 'exists:job_titles,id'],
            'attendance_date' => ['sometimes', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'hours_worked' => ['nullable', 'numeric', 'min:0'],
            'calculated_wage' => ['nullable', 'numeric', 'min:0'],
            'is_present' => ['nullable', 'boolean'],
            'absence_reason' => ['nullable', 'string'],
        ]);

        $model = $this->service->update($id, $data);
        return $this->success(new AttendanceResource($model), 'updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
        return $this->success(['ok' => true], 'deleted');
    }
}

