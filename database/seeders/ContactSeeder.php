<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $tenant = Tenant::query()->first();

        Contact::query()->firstOrCreate([
            'name' => 'Supplier A',
            'tenant_id' => $tenant?->id,
        ], [
            'phone' => '0123456789',
            'email' => 'supplierA@example.com',
            'type' => 'company',
            'tags' => json_encode(['supplier']),
            'is_active' => true,
        ]);

        Contact::query()->firstOrCreate([
            'name' => 'Supplier B',
            'tenant_id' => $tenant?->id,
        ], [
            'phone' => '0987654321',
            'email' => 'supplierB@example.com',
            'type' => 'company',
            'tags' => json_encode(['supplier']),
            'is_active' => true,
        ]);
    }
}
