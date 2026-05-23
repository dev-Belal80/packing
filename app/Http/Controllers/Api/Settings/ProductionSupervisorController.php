<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Support\ApiAccess;
use Illuminate\Http\Request;

class ProductionSupervisorController extends BaseApiController
{
    public function index(Request $request)
    {
        $actor = $request->user();

        if (! $actor || ! $actor->tenant_id) {
            return response()->json(['message' => 'Tenant context required.'], 422);
        }

        $internalRole = ApiAccess::internalRoleForApiRole('production_supervisor');

        $users = User::query()
            ->where('tenant_id', $actor->tenant_id)
            ->where('role', $internalRole)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => ApiAccess::userPayload($user));

        return $this->success($users);
    }
}
