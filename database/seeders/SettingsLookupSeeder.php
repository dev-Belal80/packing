<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Packhouse;
use App\Models\PalletType;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\ProductionStage;
use App\Models\RawMaterialType;
use App\Models\RawReceipt;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SettingsLookupSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['name' => 'Demo Client Tenant'],
            [
                'location' => 'Cairo',
                'plan' => 'trial',
                'is_active' => true,
                'max_users' => 50,
            ]
        );

        app()->instance('current_tenant_id', $tenant->id);

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenant->id);
        }

        $withTenant = function (array $attrs) use ($tenant) {
            return array_merge(['tenant_id' => $tenant->id], $attrs);
        };

        foreach (['station_admin', 'reception', 'production', 'export', 'stock'] as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);
        }

        $productionSupervisors = [];
        for ($i = 1; $i <= 10; $i++) {
            $productionSupervisor = User::query()->updateOrCreate(
                ['email' => sprintf('production.supervisor.%02d@demo.local', $i)],
                [
                    'tenant_id' => $tenant->id,
                    'name' => sprintf('Production Supervisor %02d', $i),
                    'password' => Hash::make('password'),
                    'role' => 'production',
                    'is_active' => true,
                ]
            );
            $productionSupervisor->syncRoles(['production']);
            $productionSupervisors[] = $productionSupervisor;
        }

        $packhouses = [];
        for ($i = 1; $i <= 10; $i++) {
            $packhouse = Packhouse::query()->updateOrCreate(
                ['code' => sprintf('PKH-SET-%02d', $i), 'tenant_id' => $tenant->id],
                [
                    'tenant_id' => $tenant->id,
                    'name' => sprintf('Settings Packhouse %02d', $i),
                    'location' => sprintf('Zone %02d', $i),
                    'is_active' => true,
                ]
            );
            $packhouses[] = $packhouse;
        }

        $palletTypes = [];
        for ($i = 1; $i <= 10; $i++) {
            $palletType = PalletType::query()->updateOrCreate(
                $withTenant(['name' => sprintf('Pallet Type %02d', $i)]),
                $withTenant([
                    'max_cartons' => 40 + ($i * 4),
                    'max_weight_kg' => 800 + ($i * 40),
                    'is_active' => true,
                ])
            );
            $palletTypes[] = $palletType;
        }

        $products = [];
        for ($i = 1; $i <= 10; $i++) {
            $product = Product::query()->updateOrCreate(
                $withTenant(['code' => sprintf('PRD-SET-%02d', $i)]),
                $withTenant([
                    'name' => sprintf('Product %02d', $i),
                    'unit' => 'carton',
                    'carton_weight_kg' => 15 + $i,
                    'grades' => ['A', 'B', 'C'],
                    'min_cooling_hours' => 8 + $i,
                    'waste_threshold_pct' => 5 + $i,
                    'is_active' => true,
                ])
            );
            $products[] = $product;
        }

        $productionLines = [];
        foreach ($packhouses as $index => $packhouse) {
            $lineNo = $index + 1;
            $productionLine = ProductionLine::query()->updateOrCreate(
                $withTenant(['name' => sprintf('Line %02d', $lineNo)]),
                $withTenant([
                    'packhouse_id' => $packhouse->id,
                    'status' => 'running',
                    'is_active' => true,
                ])
            );
            $productionLines[] = $productionLine;
        }

        $stage = ProductionStage::query()->updateOrCreate(
            $withTenant(['name' => 'Sorting']),
            $withTenant(['order' => 1])
        );

        $suppliers = [];
        for ($i = 1; $i <= 10; $i++) {
            $supplier = Contact::query()->updateOrCreate(
                $withTenant(['email' => sprintf('supplier.settings.%02d@demo.local', $i)]),
                $withTenant([
                    'name' => sprintf('Settings Supplier %02d', $i),
                    'phone' => sprintf('+2010000002%02d', $i),
                    'type' => 'company',
                    'tags' => ['supplier'],
                    'is_active' => true,
                ])
            );
            $suppliers[] = $supplier;
        }

        $clients = [];
        for ($i = 1; $i <= 10; $i++) {
            $client = Contact::query()->updateOrCreate(
                $withTenant(['email' => sprintf('client.settings.%02d@demo.local', $i)]),
                $withTenant([
                    'name' => sprintf('Settings Client %02d', $i),
                    'phone' => sprintf('+2010000003%02d', $i),
                    'type' => 'company',
                    'tags' => ['customer'],
                    'is_active' => true,
                ])
            );
            $clients[] = $client;
        }

        $rawMaterialType = RawMaterialType::query()->updateOrCreate(
            $withTenant(['name' => 'Orange']),
            $withTenant([
                'unit' => 'kg',
                'is_active' => true,
            ])
        );

        $rawReceipts = [];
        for ($i = 1; $i <= 10; $i++) {
            $packhouse = $packhouses[$i - 1];
            $supplier = $suppliers[$i - 1];
            $rawReceipt = RawReceipt::query()->updateOrCreate(
                $withTenant(['reference_no' => sprintf('RR-SET-%04d', $i)]),
                $withTenant([
                    'packhouse_id' => $packhouse->id,
                    'contact_id' => $supplier->id,
                    'contact_role' => 'supplier',
                    'raw_material_type_id' => $rawMaterialType->id,
                    'quantity_kg' => 500 + ($i * 10),
                    'quality_result' => 'pass',
                    'status' => 'in_stock',
                    'approval_status' => 'approved',
                    'approved_by' => $productionSupervisors[$i - 1]->id,
                    'approved_at' => now(),
                ])
            );
            $rawReceipts[] = $rawReceipt;
        }

        for ($i = 1; $i <= 10; $i++) {
            $packhouse = $packhouses[$i - 1];
            $rawReceipt = $rawReceipts[$i - 1];
            $product = $products[$i - 1];
            $palletType = $palletTypes[$i - 1];
            $productionLine = $productionLines[$i - 1];
            $supervisor = $productionSupervisors[$i - 1];
            $supplier = $suppliers[$i - 1];
            $client = $clients[$i - 1];

            ProductionOrder::query()->updateOrCreate(
                $withTenant(['reference_no' => sprintf('PO-SET-%04d', $i)]),
                $withTenant([
                    'packhouse_id' => $packhouse->id,
                    'raw_receipt_id' => $rawReceipt->id,
                    'product_id' => $product->id,
                    'pallet_type_id' => $palletType->id,
                    'production_line_id' => $productionLine->id,
                    'production_stage_id' => $stage->id,
                    'supervisor_id' => $supervisor->id,
                    'supplier_contact_id' => $supplier->id,
                    'client_contact_id' => $client->id,
                    'branch' => sprintf('Branch %02d', $i),
                    'order_date' => now()->toDateString(),
                    'order_type' => 'own',
                    'status' => 'running',
                    'target_qty_kg' => 450 + ($i * 10),
                    'actual_input_kg' => 0,
                ])
            );
        }

        app()->forgetInstance('current_tenant_id');
    }
}
