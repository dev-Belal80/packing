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
                'message' => 'Forbidden',
                'code' => 'FORBIDDEN',
                'required_permission' => 'authenticated',
            ], 403);
        }

        $requiredPermission = ApiAccess::routePermissionForAction($request->route()?->getActionName());

        if ($requiredPermission === null) {
            return $next($request);
        }

        if (! ApiAccess::canAccess($user, $requiredPermission)) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'FORBIDDEN',
                'required_permission' => $requiredPermission,
            ], 403);
        }

        return $next($request);
    }
}