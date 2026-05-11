<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return response()->json(['message' => 'Tenant not found'], 403);
        }
        app()->instance('current_tenant_id', $user->tenant_id);

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($user->tenant_id);
        }

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->set($user->tenant ?? Tenant::query()->find($user->tenant_id));

        return $next($request);
    }
}

