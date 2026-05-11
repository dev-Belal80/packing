<?php

namespace Database\Seeders;

use App\Models\RawMaterialType;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RawMaterialTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $tenant = Tenant::query()->first();

        RawMaterialType::query()->firstOrCreate([
            'name' => 'Strawberry',
            'tenant_id' => $tenant?->id,
        ], [
            'unit' => 'kg',
            'is_active' => true,
        ]);

        RawMaterialType::query()->firstOrCreate([
            'name' => 'Blueberry',
            'tenant_id' => $tenant?->id,
        ], [
            'unit' => 'kg',
            'is_active' => true,
        ]);
    }
}
