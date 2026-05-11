<?php

namespace App\Http\Controllers\Api\Export;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Export\PalletResource;
use App\Services\Export\PalletService;
use Illuminate\Http\Request;

class PalletController extends BaseApiController
{
    public function __construct(private readonly PalletService $service)
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
            'product_id' => ['nullable', 'integer'],
            'pallet_type_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $model = $this->service->create($data);
        return $this->success(new PalletResource($model), 'created', 201);
    }

    public function show(int $id)
    {
        $model = $this->service->findOrFail($id);
        return $this->success(new PalletResource($model));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $model = $this->service->update($id, $data);
        return $this->success(new PalletResource($model), 'updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
        return $this->success(['ok' => true], 'deleted');
    }

    public function cooling(int $id)
    {
        $model = $this->service->cooling($id);
        return $this->success(new PalletResource($model), 'cooling_started');
    }

    public function confirmReceipt(int $id)
    {
        $model = $this->service->confirmReceipt($id);
        return $this->success(new PalletResource($model), 'receipt_confirmed');
    }
}

