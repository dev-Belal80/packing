<?php

namespace App\Http\Controllers\Api\Station;

use App\Models\User;
use App\Support\ApiAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Station admin creates users within their tenant and assigns a role.
     */
    public function store(Request $request)
    {
        $actor = $request->user();

        if (! $actor || ! $actor->tenant_id) {
            return response()->json(['message' => 'Tenant context required.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'max:100'],
        ]);

        $internalRole = ApiAccess::internalRoleForApiRole($data['role']);

        $user = User::create([
            'tenant_id' => $actor->tenant_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $internalRole,
        ]);

        Role::query()->firstOrCreate([
            'name' => $internalRole,
            'guard_name' => 'web',
            'tenant_id' => $actor->tenant_id,
        ]);

        $user->assignRole($internalRole);

        return response()->json(['user' => ApiAccess::userPayload($user)], 201);
    }
}
