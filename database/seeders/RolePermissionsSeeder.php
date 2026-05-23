<?php

namespace Database\Seeders;

use App\Models\RoleScreenPermission;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantIds = Tenant::query()->pluck('id');

        foreach ($tenantIds as $tenantId) {
            app()->instance('current_tenant_id', (int) $tenantId);

            foreach (RoleScreenPermission::getDefaults() as [$role, $screenId, $level]) {
                RoleScreenPermission::query()->firstOrCreate(
                    ['tenant_id' => (int) $tenantId, 'role' => $role, 'screen_id' => $screenId],
                    ['access_level' => $level]
                );
            }
        }
    }
}
