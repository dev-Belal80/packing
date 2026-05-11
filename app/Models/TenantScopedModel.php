<?php

namespace App\Models;

use App\Database\Scopes\TenantScope;
use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Model;

abstract class TenantScopedModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->id() !== null && empty($model->getAttribute('tenant_id'))) {
                $model->setAttribute('tenant_id', $tenantManager->id());
            }
        });
    }
}
