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
                    'message' => 'الاشتراك منتهي — النظام في وضع القراءة فقط'
                ], 403);
            }
        }
        return $next($request);
    }
}

