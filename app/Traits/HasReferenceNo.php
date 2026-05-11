<?php

namespace App\Traits;

use App\Support\ReferenceNo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @mixin Model
 */
trait HasReferenceNo
{
    protected static function bootHasReferenceNo(): void
    {
        static::creating(function (Model $model): void {
            if (! empty($model->getAttribute('reference_no'))) {
                return;
            }

            $tenantId = (int) $model->getAttribute('tenant_id');
            if ($tenantId <= 0 && app()->has('current_tenant_id')) {
                $tenantId = (int) app('current_tenant_id');
            }
            if ($tenantId <= 0) {
                return;
            }

            /** @var Carbon $now */
            $now = now();
            $dateYmd = $now->format('Ymd');

            if (! method_exists($model, 'referencePrefix')) {
                return;
            }

            /** @var string $prefix */
            $prefix = (string) $model->referencePrefix();
            $table = $model->getTable();

            $model->setAttribute('reference_no', ReferenceNo::generate($table, $prefix, $tenantId, $dateYmd));
        });
    }
}

