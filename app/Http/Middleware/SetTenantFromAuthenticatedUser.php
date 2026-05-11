<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromAuthenticatedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $user = $request->user();

        // Set Spatie team id as early as possible (required for hasRole/hasPermission when teams=true).
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($user?->tenant_id);
        }

        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $tenantManager->set(null);
            return $next($request);
        }

        if ($user?->tenant_id) {
            $tenant = Tenant::query()->find($user->tenant_id);
            $tenantManager->set($tenant);

            if (function_exists('setPermissionsTeamId') && $tenant === null) {
                setPermissionsTeamId(null);
            }
        } else {
            $tenantManager->set(null);

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId(null);
            }
        }

        return $next($request);
    }
}
