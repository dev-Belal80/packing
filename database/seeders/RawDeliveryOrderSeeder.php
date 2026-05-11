<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Packhouse;
use App\Models\RawDeliveryOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RawDeliveryOrderSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->first();
        $packhouse = Packhouse::query()->first();
        $user = User::query()->where('tenant_id', $tenant?->id)->first();

        if (!$tenant || !$packhouse || !$user) {
            $this->command->info('Skipping RawDeliveryOrderSeeder: Missing required data (tenant, packhouse, or user)');
            return;
        }

        // Get or create suppliers and clients
        $suppliers = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('tags', 'like', '%supplier%')
            ->limit(3)
            ->get();

        $clients = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('tags', 'like', '%customer%')
            ->limit(3)
            ->get();

        // Fallback: create test contacts if none exist
        if ($suppliers->isEmpty() || $clients->isEmpty()) {
            Contact::query()->firstOrCreate(
                ['name' => 'Test Supplier', 'tenant_id' => $tenant->id],
                ['email' => 'supplier@test.com', 'tags' => json_encode(['supplier']), 'is_active' => true]
            );
            Contact::query()->firstOrCreate(
                ['name' => 'Test Client', 'tenant_id' => $tenant->id],
                ['email' => 'client@test.com', 'tags' => json_encode(['customer']), 'is_active' => true]
            );
            $suppliers = Contact::query()->where('tags', 'like', '%supplier%')->where('tenant_id', $tenant->id)->get();
            $clients = Contact::query()->where('tags', 'like', '%customer%')->where('tenant_id', $tenant->id)->get();
        }

        $orders = [
            [
                'branch' => 'Branch A',
                'order_date' => now()->subDays(5),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-001',
                'cost_center' => 'CC-001',
                'raw_type' => 'Tomato',
                'loading_warehouse' => 'Warehouse 1',
                'destination_warehouse' => 'Warehouse 2',
                'supplying_station' => 'Station A',
                'delivery_station' => 'Station B',
                'description_ar' => 'توريد طماطم طازة',
                'weight_on_entry' => 1000,
                'weight_on_exit' => 100,
                'received_qty' => 450,
                'discount_pct' => 5,
                'extra_discount_pct' => 2,
                'price_per_unit' => 25,
                'invoice_number' => 'INV-001',
                'units_count' => 50,
                'sorting_cost' => 250,
                'supply_qty' => 400,
                'supply_discount_pct' => 3,
                'supplied_qty' => 390,
                'cost_price' => 20,
                'supply_units_count' => 40,
                'transport_contractor' => 'Transport Co A',
                'transport_units' => 5,
                'transport_unit_cost' => 100,
                'transport_discount_qty' => 0.5,
                'transport_price' => 50,
                'status' => 'draft',
            ],
            [
                'branch' => 'Branch B',
                'order_date' => now()->subDays(4),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-002',
                'cost_center' => 'CC-002',
                'raw_type' => 'Cucumber',
                'loading_warehouse' => 'Warehouse 2',
                'destination_warehouse' => 'Warehouse 3',
                'supplying_station' => 'Station C',
                'delivery_station' => 'Station D',
                'description_ar' => 'توريد خيار عالي الجودة',
                'weight_on_entry' => 800,
                'weight_on_exit' => 80,
                'received_qty' => 350,
                'discount_pct' => 3,
                'extra_discount_pct' => 1,
                'price_per_unit' => 30,
                'invoice_number' => 'INV-002',
                'units_count' => 35,
                'sorting_cost' => 180,
                'supply_qty' => 330,
                'supply_discount_pct' => 2,
                'supplied_qty' => 325,
                'cost_price' => 24,
                'supply_units_count' => 33,
                'transport_contractor' => 'Transport Co B',
                'transport_units' => 4,
                'transport_unit_cost' => 120,
                'transport_discount_qty' => 0.3,
                'transport_price' => 60,
                'status' => 'confirmed',
            ],
            [
                'branch' => 'Branch A',
                'order_date' => now()->subDays(3),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-003',
                'cost_center' => 'CC-001',
                'raw_type' => 'Lettuce',
                'loading_warehouse' => 'Warehouse 1',
                'destination_warehouse' => 'Warehouse 4',
                'supplying_station' => 'Station A',
                'delivery_station' => 'Station E',
                'description_ar' => 'توريد خس طازة',
                'weight_on_entry' => 600,
                'weight_on_exit' => 60,
                'received_qty' => 280,
                'discount_pct' => 4,
                'extra_discount_pct' => 1.5,
                'price_per_unit' => 18,
                'invoice_number' => 'INV-003',
                'units_count' => 28,
                'sorting_cost' => 140,
                'supply_qty' => 260,
                'supply_discount_pct' => 2.5,
                'supplied_qty' => 255,
                'cost_price' => 15,
                'supply_units_count' => 26,
                'transport_contractor' => 'Transport Co A',
                'transport_units' => 3,
                'transport_unit_cost' => 90,
                'transport_discount_qty' => 0.2,
                'transport_price' => 45,
                'status' => 'draft',
            ],
            [
                'branch' => 'Branch C',
                'order_date' => now()->subDays(2),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-004',
                'cost_center' => 'CC-003',
                'raw_type' => 'Bell Pepper',
                'loading_warehouse' => 'Warehouse 3',
                'destination_warehouse' => 'Warehouse 1',
                'supplying_station' => 'Station F',
                'delivery_station' => 'Station A',
                'description_ar' => 'توريد فلفل أحمر',
                'weight_on_entry' => 900,
                'weight_on_exit' => 90,
                'received_qty' => 420,
                'discount_pct' => 6,
                'extra_discount_pct' => 2,
                'price_per_unit' => 35,
                'invoice_number' => 'INV-004',
                'units_count' => 42,
                'sorting_cost' => 300,
                'supply_qty' => 400,
                'supply_discount_pct' => 3.5,
                'supplied_qty' => 390,
                'cost_price' => 28,
                'supply_units_count' => 40,
                'transport_contractor' => 'Transport Co C',
                'transport_units' => 6,
                'transport_unit_cost' => 110,
                'transport_discount_qty' => 0.6,
                'transport_price' => 55,
                'status' => 'confirmed',
            ],
            [
                'branch' => 'Branch B',
                'order_date' => now()->subDays(1),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-005',
                'cost_center' => 'CC-002',
                'raw_type' => 'Onion',
                'loading_warehouse' => 'Warehouse 2',
                'destination_warehouse' => 'Warehouse 3',
                'supplying_station' => 'Station C',
                'delivery_station' => 'Station B',
                'description_ar' => 'توريد بصل',
                'weight_on_entry' => 1200,
                'weight_on_exit' => 120,
                'received_qty' => 550,
                'discount_pct' => 5,
                'extra_discount_pct' => 1,
                'price_per_unit' => 12,
                'invoice_number' => 'INV-005',
                'units_count' => 55,
                'sorting_cost' => 200,
                'supply_qty' => 520,
                'supply_discount_pct' => 2,
                'supplied_qty' => 510,
                'cost_price' => 10,
                'supply_units_count' => 52,
                'transport_contractor' => 'Transport Co B',
                'transport_units' => 7,
                'transport_unit_cost' => 95,
                'transport_discount_qty' => 0.7,
                'transport_price' => 40,
                'status' => 'draft',
            ],
            [
                'branch' => 'Branch A',
                'order_date' => now(),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-006',
                'cost_center' => 'CC-001',
                'raw_type' => 'Carrot',
                'loading_warehouse' => 'Warehouse 1',
                'destination_warehouse' => 'Warehouse 2',
                'supplying_station' => 'Station A',
                'delivery_station' => 'Station D',
                'description_ar' => 'توريد جزر برتقالي',
                'weight_on_entry' => 700,
                'weight_on_exit' => 70,
                'received_qty' => 330,
                'discount_pct' => 3,
                'extra_discount_pct' => 0.5,
                'price_per_unit' => 22,
                'invoice_number' => 'INV-006',
                'units_count' => 33,
                'sorting_cost' => 165,
                'supply_qty' => 310,
                'supply_discount_pct' => 1.5,
                'supplied_qty' => 305,
                'cost_price' => 18,
                'supply_units_count' => 31,
                'transport_contractor' => 'Transport Co A',
                'transport_units' => 3,
                'transport_unit_cost' => 105,
                'transport_discount_qty' => 0.25,
                'transport_price' => 52,
                'status' => 'draft',
            ],
            [
                'branch' => 'Branch C',
                'order_date' => now()->addDays(1),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-007',
                'cost_center' => 'CC-003',
                'raw_type' => 'Spinach',
                'loading_warehouse' => 'Warehouse 4',
                'destination_warehouse' => 'Warehouse 1',
                'supplying_station' => 'Station G',
                'delivery_station' => 'Station A',
                'description_ar' => 'توريد سبانخ طازة',
                'weight_on_entry' => 400,
                'weight_on_exit' => 40,
                'received_qty' => 180,
                'discount_pct' => 2,
                'extra_discount_pct' => 1,
                'price_per_unit' => 40,
                'invoice_number' => 'INV-007',
                'units_count' => 18,
                'sorting_cost' => 90,
                'supply_qty' => 170,
                'supply_discount_pct' => 1,
                'supplied_qty' => 168,
                'cost_price' => 32,
                'supply_units_count' => 17,
                'transport_contractor' => 'Transport Co D',
                'transport_units' => 2,
                'transport_unit_cost' => 130,
                'transport_discount_qty' => 0.1,
                'transport_price' => 65,
                'status' => 'draft',
            ],
            [
                'branch' => 'Branch B',
                'order_date' => now()->addDays(2),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-008',
                'cost_center' => 'CC-002',
                'raw_type' => 'Broccoli',
                'loading_warehouse' => 'Warehouse 2',
                'destination_warehouse' => 'Warehouse 4',
                'supplying_station' => 'Station H',
                'delivery_station' => 'Station E',
                'description_ar' => 'توريد بروكلي',
                'weight_on_entry' => 550,
                'weight_on_exit' => 55,
                'received_qty' => 260,
                'discount_pct' => 4,
                'extra_discount_pct' => 1.5,
                'price_per_unit' => 38,
                'invoice_number' => 'INV-008',
                'units_count' => 26,
                'sorting_cost' => 130,
                'supply_qty' => 245,
                'supply_discount_pct' => 2,
                'supplied_qty' => 240,
                'cost_price' => 30,
                'supply_units_count' => 24,
                'transport_contractor' => 'Transport Co B',
                'transport_units' => 3,
                'transport_unit_cost' => 115,
                'transport_discount_qty' => 0.3,
                'transport_price' => 57,
                'status' => 'confirmed',
            ],
            [
                'branch' => 'Branch A',
                'order_date' => now()->addDays(3),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-009',
                'cost_center' => 'CC-001',
                'raw_type' => 'Zucchini',
                'loading_warehouse' => 'Warehouse 1',
                'destination_warehouse' => 'Warehouse 3',
                'supplying_station' => 'Station A',
                'delivery_station' => 'Station C',
                'description_ar' => 'توريد كوسة',
                'weight_on_entry' => 750,
                'weight_on_exit' => 75,
                'received_qty' => 350,
                'discount_pct' => 5,
                'extra_discount_pct' => 2,
                'price_per_unit' => 28,
                'invoice_number' => 'INV-009',
                'units_count' => 35,
                'sorting_cost' => 175,
                'supply_qty' => 330,
                'supply_discount_pct' => 3,
                'supplied_qty' => 320,
                'cost_price' => 22,
                'supply_units_count' => 33,
                'transport_contractor' => 'Transport Co A',
                'transport_units' => 4,
                'transport_unit_cost' => 100,
                'transport_discount_qty' => 0.4,
                'transport_price' => 50,
                'status' => 'draft',
            ],
            [
                'branch' => 'Branch C',
                'order_date' => now()->addDays(4),
                'agricultural_season' => '2026 Summer',
                'work_order' => 'WO-010',
                'cost_center' => 'CC-003',
                'raw_type' => 'Cabbage',
                'loading_warehouse' => 'Warehouse 3',
                'destination_warehouse' => 'Warehouse 2',
                'supplying_station' => 'Station F',
                'delivery_station' => 'Station B',
                'description_ar' => 'توريد ملفوف',
                'weight_on_entry' => 650,
                'weight_on_exit' => 65,
                'received_qty' => 310,
                'discount_pct' => 3.5,
                'extra_discount_pct' => 1,
                'price_per_unit' => 15,
                'invoice_number' => 'INV-010',
                'units_count' => 31,
                'sorting_cost' => 120,
                'supply_qty' => 290,
                'supply_discount_pct' => 2,
                'supplied_qty' => 285,
                'cost_price' => 12,
                'supply_units_count' => 29,
                'transport_contractor' => 'Transport Co C',
                'transport_units' => 3,
                'transport_unit_cost' => 108,
                'transport_discount_qty' => 0.2,
                'transport_price' => 54,
                'status' => 'confirmed',
            ],
        ];

        $count = 0;
        foreach ($orders as $orderData) {
            $supplier = $suppliers->random();
            $client = $clients->random();

            // Run calculations
            $calculated = RawDeliveryOrder::calculate($orderData);

            // Check if already exists by invoice number
            $existing = RawDeliveryOrder::query()
                ->where('tenant_id', $tenant->id)
                ->where('invoice_number', $orderData['invoice_number'])
                ->first();

            if ($existing) {
                continue;
            }

            $count++;
            $year = now()->year;

            // Create without using firstOrCreate to let boot method handle reference_no
            RawDeliveryOrder::create([
                'tenant_id' => $tenant->id,
                'packhouse_id' => $packhouse->id,
                'supplier_contact_id' => $supplier->id,
                'client_contact_id' => $client->id,
                'created_by' => $user->id,
                'confirmed_by' => $calculated['status'] === 'confirmed' ? $user->id : null,
                'confirmed_at' => $calculated['status'] === 'confirmed' ? now() : null,
                'reference_no' => sprintf('%04d/%d', $count, $year),
                'year' => $year,
                ...$calculated,
            ]);
        }

        $this->command->info("✓ Created {$count} RawDeliveryOrder records with sample data");
    }
}
