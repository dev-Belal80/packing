<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TenantProvisionController extends Controller
{
    /**
     * Create a tenant (station) + initial station admin user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant' => ['required', 'array'],
            'tenant.name' => ['required', 'string', 'max:255'],
            'tenant.location' => ['nullable', 'string', 'max:255'],
            'tenant.plan' => ['nullable', 'string', 'max:100'],
            'tenant.max_users' => ['nullable', 'integer', 'min:1'],
            'tenant.subscription_ends_at' => ['nullable', 'date'],

            'admin' => ['required', 'array'],
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin.password' => ['nullable', 'string', 'min:8'],
        ]);

        $tenant = Tenant::create($data['tenant']);

        $plainPassword = $data['admin']['password'] ?? Str::password(12);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['admin']['name'],
            'email' => $data['admin']['email'],
            'password' => Hash::make($plainPassword),
        ]);

        // station_admin role scoped to tenant via Spatie teams.
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenant->id);
        }

        Role::query()->firstOrCreate([
            'name' => 'station_admin',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);

        $user->assignRole('station_admin');

        return response()->json([
            'tenant' => $tenant,
            'admin_user' => $user,
            // In production, prefer sending this via email/one-time link.
            'admin_plain_password' => $plainPassword,
        ], 201);
    }
}
