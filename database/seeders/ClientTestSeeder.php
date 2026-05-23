<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\EmployeeAttendance;
use App\Models\Fridge;
use App\Models\GateInquiry;
use App\Models\JobTitle;
use App\Models\Packhouse;
use App\Models\Pallet;
use App\Models\PalletCooling;
use App\Models\PalletType;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderPicking;
use App\Models\ProductionStage;
use App\Models\RawDeliveryOrder;
use App\Models\RawMaterialType;
use App\Models\RawReceipt;
use App\Models\ScaleNote;
use App\Models\ShippingPolicy;
use App\Models\SortRecord;
use App\Models\SortRecordLine;
use App\Models\StockTransaction;
use App\Models\Tenant;
use App\Models\TransportCost;
use App\Models\TransportCostReceipt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ClientTestSeeder extends Seeder
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

        $roles = ['station_admin', 'reception', 'production', 'export', 'stock'];
        foreach ($roles as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);
        }

        $permissions = [
            'order.dispatch',
            'order.pause',
            'sort-record.post',
            'pallet.create',
            'pallet.update',
            'pallet.delete',
            'pallet.cooling',
            'pallet.confirm',
            'shipping.approve',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $stationAdmin = User::query()->updateOrCreate(
            ['email' => 'station.admin@demo.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Station Admin',
                'password' => Hash::make('password'),
                'role' => 'station_admin',
                'is_active' => true,
            ]
        );
        $stationAdmin->syncRoles(['station_admin']);

        $receptionUser = User::query()->updateOrCreate(
            ['email' => 'reception@demo.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Reception User',
                'password' => Hash::make('password'),
                'role' => 'reception',
                'is_active' => true,
            ]
        );
        $receptionUser->syncRoles(['reception']);

        $exportOfficer = User::query()->updateOrCreate(
            ['email' => 'export.officer@demo.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Export Officer',
                'password' => Hash::make('password'),
                'role' => 'export_officer',
                'is_active' => true,
            ]
        );
        $exportOfficer->syncRoles(['export']);

        $productionUser = User::query()->updateOrCreate(
            ['email' => 'production@demo.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Production User',
                'password' => Hash::make('password'),
                'role' => 'production',
                'is_active' => true,
            ]
        );
        $productionUser->syncRoles(['production']);

        Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'export')
            ->first()?->syncPermissions([
                'pallet.create',
                'pallet.update',
                'pallet.delete',
                'pallet.cooling',
                'pallet.confirm',
                'shipping.approve',
            ]);

        Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'production')
            ->first()?->syncPermissions([
                'order.dispatch',
                'order.pause',
                'sort-record.post',
            ]);

        $packhouse = Packhouse::query()->updateOrCreate(
            ['code' => 'PKH-DEMO-01', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demo Packhouse',
                'location' => 'Giza',
                'is_active' => true,
            ]
        );

        $productionLine = ProductionLine::query()->updateOrCreate(
            $withTenant(['name' => 'Line 1']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'status' => 'running',
                'is_active' => true,
            ])
        );

        $fridge = Fridge::query()->updateOrCreate(
            $withTenant(['name' => 'Fridge A']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'capacity_tons' => 20,
                'min_temp' => 1,
                'max_temp' => 4,
                'is_active' => true,
            ])
        );

        $stage = ProductionStage::query()->updateOrCreate(
            $withTenant(['name' => 'Sorting']),
            $withTenant(['order' => 1])
        );

        $rawMaterialType = RawMaterialType::query()->updateOrCreate(
            $withTenant(['name' => 'Orange']),
            $withTenant([
                'unit' => 'kg',
                'is_active' => true,
            ])
        );

        $palletType = PalletType::query()->updateOrCreate(
            $withTenant(['name' => 'Euro Pallet']),
            $withTenant([
                'max_cartons' => 80,
                'max_weight_kg' => 1200,
                'is_active' => true,
            ])
        );

        $product = Product::query()->updateOrCreate(
            $withTenant(['code' => 'ORG-001']),
            $withTenant([
                'name' => 'Orange Carton',
                'unit' => 'carton',
                'carton_weight_kg' => 20,
                'grades' => ['A', 'B', 'C'],
                'min_cooling_hours' => 12,
                'waste_threshold_pct' => 10,
                'is_active' => true,
            ])
        );

        $jobTitle = JobTitle::query()->updateOrCreate(
            $withTenant(['name' => 'Sorter']),
            $withTenant([
                'daily_rate' => 250,
                'is_active' => true,
            ])
        );

        $supplier = Contact::query()->updateOrCreate(
            $withTenant(['email' => 'supplier@demo.local']),
            $withTenant([
                'name' => 'Demo Supplier',
                'phone' => '+201000000001',
                'type' => 'company',
                'tags' => ['supplier'],
                'is_active' => true,
            ])
        );

        $importer = Contact::query()->updateOrCreate(
            $withTenant(['email' => 'importer@demo.local']),
            $withTenant([
                'name' => 'Demo Importer',
                'phone' => '+201000000002',
                'type' => 'company',
                'tags' => ['customer', 'importer'],
                'is_active' => true,
            ])
        );

        $deliveryOrder = RawDeliveryOrder::query()->updateOrCreate(
            $withTenant(['reference_no' => 'DO-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'supplier_contact_id' => $supplier->id,
                'year' => now()->year,
                'order_date' => now()->toDateString(),
                'received_qty' => 950,
                'status' => 'open',
                'created_by' => $stationAdmin->id,
            ])
        );

        $gateInquiry = GateInquiry::query()->updateOrCreate(
            $withTenant(['reference_no' => 'GI-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'vehicle_number' => 'ABC-123',
                'driver_name' => 'Mahmoud Ali',
                'contact_id' => $supplier->id,
                'raw_material_type_id' => $rawMaterialType->id,
                'expected_qty' => 950,
                'delivery_order_id' => $deliveryOrder->id,
                'status' => 'approved',
            ])
        );

        $scaleNote = ScaleNote::query()->updateOrCreate(
            $withTenant(['reference_no' => 'SN-DEMO-0001']),
            $withTenant([
                'gate_inquiry_id' => $gateInquiry->id,
                'gross_weight' => 1050,
                'tare_weight' => 100,
                'net_weight' => 950,
                'is_manual' => false,
            ])
        );

        $rawReceipt = RawReceipt::query()->updateOrCreate(
            $withTenant(['reference_no' => 'RR-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'contact_id' => $supplier->id,
                'contact_role' => 'supplier',
                'raw_material_type_id' => $rawMaterialType->id,
                'gate_inquiry_id' => $gateInquiry->id,
                'scale_note_id' => $scaleNote->id,
                'boxes_count' => 48,
                'quantity_kg' => 950,
                'quality_result' => 'pass',
                'quality_notes' => 'Seeded data for client-side testing',
                'is_partial' => false,
                'has_weight_dispute' => false,
                'price_per_kg' => 1.2,
                'total_price' => 1140,
                'transport_cost' => 80,
                'status' => 'in_stock',
                'approval_status' => 'approved',
                'approved_by' => $stationAdmin->id,
                'approved_at' => now(),
            ])
        );

        $attendance = EmployeeAttendance::query()->updateOrCreate(
            [
                'employee_name' => 'Ahmed Samir',
                'attendance_date' => now()->toDateString(),
            ],
            [
                'tenant_id' => $tenant->id,
                'packhouse_id' => $packhouse->id,
                'job_title_id' => $jobTitle->id,
                'check_in' => '08:00:00',
                'hours_worked' => 8,
                'calculated_wage' => 250,
                'is_present' => true,
            ]
        );

        $productionOrder = ProductionOrder::query()->updateOrCreate(
            $withTenant(['reference_no' => 'PO-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'raw_receipt_id' => $rawReceipt->id,
                'product_id' => $product->id,
                'production_line_id' => $productionLine->id,
                'production_stage_id' => $stage->id,
                'supervisor_id' => $productionUser->id,
                'target_qty_kg' => 900,
                'actual_input_kg' => 880,
                'order_type' => 'own',
                'status' => 'running',
                'started_at' => now()->subHours(3),
            ])
        );

        ProductionOrderPicking::query()->updateOrCreate(
            $withTenant([
                'production_order_id' => $productionOrder->id,
                'raw_receipt_id' => $rawReceipt->id,
            ]),
            $withTenant([
                'dispatched_qty_kg' => 880,
                'dispatched_by' => $productionUser->id,
                'dispatched_at' => now()->subHours(2),
            ])
        );

        $sortRecord = SortRecord::query()->updateOrCreate(
            $withTenant(['reference_no' => 'SR-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'accounting_period' => now()->format('Y-m'),
                'branch' => 'الرئيسي',
                'sort_date' => now()->toDateString(),
                'sort_time' => now()->format('H:i'),
                'description_ar' => 'فرزة خام تجريبية',
                'description_en' => 'Demo sort record',
                'notes' => 'Seeded sort record',
                'status' => 'posted',
                'posted_by' => $productionUser->id,
                'posted_at' => now(),
                'created_by' => $productionUser->id,
            ])
        );

        SortRecordLine::query()->updateOrCreate(
            $withTenant([
                'sort_record_id' => $sortRecord->id,
                'sort_order' => 0,
            ]),
            $withTenant([
                'raw_type' => 'Orange',
                'lot_no' => '10022',
                'production_line_id' => $productionLine->id,
                'production_order_id' => $productionOrder->id,
                'grade_a_kg' => 620,
                'grade_b_kg' => 140,
                'grade_c_kg' => 80,
                'waste_kg' => 30,
                'returned_kg' => 10,
            ])
        );

        $pallet = Pallet::query()->updateOrCreate(
            $withTenant(['reference_no' => 'PAL-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'pallet_type_id' => $palletType->id,
                'product_id' => $product->id,
                'sort_record_id' => $sortRecord->id,
                'grade' => 'A',
                'cartons_count' => 30,
                'total_weight_kg' => 600,
                'status' => 'cooled',
                'receipt_confirmed' => true,
                'confirmed_at' => now()->subMinutes(30),
                'confirmed_by' => $stationAdmin->id,
            ])
        );

        PalletCooling::query()->updateOrCreate(
            $withTenant(['pallet_id' => $pallet->id]),
            $withTenant([
                'fridge_id' => $fridge->id,
                'entry_temp' => 2.5,
                'entered_at' => now()->subHours(10),
                'ready_at' => now()->subMinutes(20),
                'has_temp_alert' => false,
                'recorded_by' => $productionUser->id,
            ])
        );

        $shippingPolicy = ShippingPolicy::query()->updateOrCreate(
            $withTenant(['reference_no' => 'SP-DEMO-0001']),
            $withTenant([
                'packhouse_id' => $packhouse->id,
                'importer_contact_id' => $importer->id,
                'destination_country' => 'Saudi Arabia',
                'container_number' => 'MSKU1234567',
                'vessel_name' => 'Nile Star',
                'shipping_date' => now()->toDateString(),
                'status' => 'approved',
                'approved_by' => $stationAdmin->id,
                'approved_at' => now(),
            ])
        );

        DB::table('shipping_policy_pallets')->updateOrInsert(
            [
                'tenant_id' => $tenant->id,
                'shipping_policy_id' => $shippingPolicy->id,
                'pallet_id' => $pallet->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );

        $shippingPolicy->recalculateTotals();

        $transportCost = TransportCost::query()->updateOrCreate(
            $withTenant(['distribution_date' => now()->toDateString()]),
            $withTenant([
                'total_cost' => 300,
                'distribution_method' => 'weight',
                'notes' => 'Seeded transport distribution',
                'created_by' => $stationAdmin->id,
            ])
        );

        TransportCostReceipt::query()->updateOrCreate(
            $withTenant([
                'transport_cost_id' => $transportCost->id,
                'raw_receipt_id' => $rawReceipt->id,
            ]),
            $withTenant([
                'allocated_cost' => 80,
            ])
        );

        StockTransaction::query()->updateOrCreate(
            $withTenant([
                'type' => 'in',
                'reason' => 'raw_receipt',
                'raw_receipt_id' => $rawReceipt->id,
            ]),
            $withTenant([
                'production_order_id' => null,
                'shipping_policy_id' => null,
                'quantity_kg' => 950,
                'unit_cost' => 1.2,
                'total_cost' => 1140,
                'created_by' => $receptionUser->id,
            ])
        );

        StockTransaction::query()->updateOrCreate(
            $withTenant([
                'type' => 'out',
                'reason' => 'shipment_dispatch',
                'shipping_policy_id' => $shippingPolicy->id,
            ]),
            $withTenant([
                'raw_receipt_id' => null,
                'production_order_id' => $productionOrder->id,
                'quantity_kg' => 600,
                'unit_cost' => 1.5,
                'total_cost' => 900,
                'created_by' => $stationAdmin->id,
            ])
        );

        $this->command?->info('Client test data seeded.');
        $this->command?->info('Demo station admin: station.admin@demo.local / password');
        $this->command?->info('Demo reception user: reception@demo.local / password');

        app()->forgetInstance('current_tenant_id');
    }
}
