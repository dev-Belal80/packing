<?php

namespace App\Services\Production;

use App\Repositories\Interfaces\Production\ProductionOrderRepositoryInterface;

class ProductionOrderService
{
    public function __construct(private readonly ProductionOrderRepositoryInterface $repo)
    {
    }

    public function paginate(int $perPage = 15, array $filters = [])
    {
        return $this->repo->paginate($perPage, $filters);
    }

    public function findOrFail(int $id)
    {
        return $this->repo->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function dispatch(int $id)
    {
        return $this->repo->update($id, ['status' => 'dispatched']);
    }

    public function pause(int $id)
    {
        return $this->repo->update($id, ['status' => 'paused']);
    }

    public function cancel(int $id)
    {
        return $this->repo->update($id, ['status' => 'cancelled']);
    }
}

