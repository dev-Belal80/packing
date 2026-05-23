<?php

namespace App\Http\Middleware;

use App\Support\ApiAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'unauthenticated',
                'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
            ], 401);
        }

        $requiredPermission = ApiAccess::routePermissionForAction($request->route()?->getActionName());

        if ($requiredPermission === null) {
            return $next($request);
        }

        if (! ApiAccess::canAccess($user, $requiredPermission)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
                'error_code' => 'forbidden',
                'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
                'required_permission' => $requiredPermission,
            ], 403);
        }

        return $next($request);
    }
}