<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request; 

class EnsureApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && property_exists($user, 'approved_at') && empty($user->approved_at)) {
            return response()->json(['message' => 'Account not approved'], 403);
        }

        if ($user && method_exists($user, 'isApproved') && ! $user->isApproved()) {
            return response()->json(['message' => 'Account not approved'], 403);
        }

        return $next($request);
    }
}

