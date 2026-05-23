<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SubscriptionActiveMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->user()?->tenant;
        if ($tenant && $tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            if (!$request->isMethod('GET')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Forbidden',
                    'error_code' => 'forbidden',
                    'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
                    'required_permission' => 'subscription.active',
                ], 403);
            }
        }
        return $next($request);
    }
}

