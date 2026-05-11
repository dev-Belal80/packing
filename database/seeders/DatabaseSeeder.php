<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Spatie teams requires tenant_id on pivot tables.
        // Create a dedicated SYSTEM tenant to host global roles like super_admin.
        $systemTenant = Tenant::query()->firstOrCreate(
            ['name' => 'SYSTEM'],
            ['is_active' => true]
        );

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($systemTenant->id);
        }

        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'tenant_id' => $systemTenant->id,
            ]
        );

        // If the user already existed, ensure it is attached to SYSTEM tenant.
        if ($superAdmin->tenant_id !== $systemTenant->id) {
            $superAdmin->forceFill(['tenant_id' => $systemTenant->id])->save();
        }

        // Ensure role exists under SYSTEM tenant.
        Role::query()->firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $systemTenant->id,
        ]);

        $superAdmin->syncRoles(['super_admin']);

        // Optional local test user.
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        $this->call([
            ContactSeeder::class,
            RawMaterialTypeSeeder::class,
            ClientTestSeeder::class,
            RawDeliveryOrderSeeder::class,
        ]);
    }
}
