<?php

namespace App\Database\Scopes;

use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantId = $tenantManager->id();

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
