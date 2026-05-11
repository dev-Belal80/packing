<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void addGlobalScope(string|\Illuminate\Database\Eloquent\Scope $identifier, \Closure $scope = null)
 * @method static void registerModelEvent(string $event, callable $callback)
 * @method string getTable()
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_tenant_id')) {
                $builder->where(
                    (new static)->getTable() . '.tenant_id',
                    app('current_tenant_id')
                );
            }
        });

        static::registerModelEvent('creating', function ($model) {
            if (app()->has('current_tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = app('current_tenant_id');
            }
        });
    }
}



