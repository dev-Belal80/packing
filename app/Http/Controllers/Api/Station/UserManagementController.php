<?php

namespace App\Http\Controllers\Api\Station;

use App\Models\User;
use App\Support\ApiAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();

        if (! $actor || ! $actor->tenant_id) {
            return response()->json(['message' => 'Tenant context required.'], 422);
        }

        $role = $request->string('role');
        $internalRole = $role->isNotEmpty() ? ApiAccess::internalRoleForApiRole($role->toString()) : null;

        $users = User::query()
            ->where('tenant_id', $actor->tenant_id)
            ->where(function ($query): void {
                $query->whereNull('is_active')->orWhere('is_active', true);
            })
            ->when($internalRole, fn ($query) => $query->where('role', $internalRole))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => ApiAccess::userPayload($user));

        return response()->json(['data' => $users]);
    }

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

    public function update(Request $request, User $user)
    {
        $actor = $request->user();

        if (! $actor || ! $actor->tenant_id) {
            return response()->json(['message' => 'Tenant context required.'], 422);
        }

        if ((int) $user->tenant_id !== (int) $actor->tenant_id) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('password', $data)) {
            if ($data['password'] === null || $data['password'] === '') {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }

        $internalRole = null;
        if (array_key_exists('role', $data)) {
            $internalRole = ApiAccess::internalRoleForApiRole($data['role']);
            $data['role'] = $internalRole;
        }

        $user->fill($data);
        $user->save();

        if ($internalRole !== null) {
            Role::query()->firstOrCreate([
                'name' => $internalRole,
                'guard_name' => 'web',
                'tenant_id' => $actor->tenant_id,
            ]);

            $user->syncRoles([$internalRole]);
        }

        return response()->json(['user' => ApiAccess::userPayload($user)]);
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();

        if (! $actor || ! $actor->tenant_id) {
            return response()->json(['message' => 'Tenant context required.'], 422);
        }

        if ((int) $user->tenant_id !== (int) $actor->tenant_id) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ((int) $user->id === (int) $actor->id) {
            return response()->json(['message' => 'You cannot delete your own user.'], 422);
        }

        // We do not hard-delete users because they are referenced by multiple workflow tables
        // (e.g. created_by/posted_by). Instead we revoke access and deactivate the account.
        $user->tokens()->delete();
        $user->syncRoles([]);
        $user->forceFill(['is_active' => false])->save();

        return response()->json([
            'ok' => true,
            'id' => $user->id,
        ]);
    }
}
