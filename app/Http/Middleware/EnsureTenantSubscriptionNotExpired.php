<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionNotExpired
{
    /**
     * If subscription is expired, tenant becomes read-only.
     * Allows only safe methods (GET/HEAD/OPTIONS).
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->current();

        $forbiddenMessage = null;

        if ($tenant !== null) {
            if (! $tenant->is_active) {
                $forbiddenMessage = 'Tenant is inactive.';
            } elseif ($tenant->is_subscription_expired && ! $request->isMethodSafe()) {
                $forbiddenMessage = 'Subscription expired: tenant is read-only.';
            }
        }

        return $forbiddenMessage
            ? response()->json(['message' => $forbiddenMessage], 403)
            : $next($request);
    }
}
