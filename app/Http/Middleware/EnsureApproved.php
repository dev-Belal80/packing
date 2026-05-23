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
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
                'error_code' => 'forbidden',
                'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
                'required_permission' => 'account.approved',
            ], 403);
        }

        if ($user && method_exists($user, 'isApproved') && ! $user->isApproved()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
                'error_code' => 'forbidden',
                'request_id' => (string) ($request->attributes->get('request_id') ?? ''),
                'required_permission' => 'account.approved',
            ], 403);
        }

        return $next($request);
    }
}

