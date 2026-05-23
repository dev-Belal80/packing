<?php

namespace Database\Seeders;

use App\Models\Fridge;
use App\Models\Packhouse;
use App\Models\Pallet;
use App\Models\PalletCooling;
use App\Models\PalletType;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\SortRecord;
use App\Models\SortRecordLine;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PalletSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();
        if (! $tenant) {
            $this->command?->info('No tenant found, run ClientTestSeeder first.');
            return;
        }

        app()->instance('current_tenant_id', $tenant->id);

        $withTenant = function (array $attrs) use ($tenant) {
            return array_merge(['tenant_id' => $tenant->id], $attrs);
        };

        $packhouse = Packhouse::query()->first();
        $palletType = PalletType::query()->first();
        $product = Product::query()->first();
        $sortRecord = SortRecord::query()->first();
        $sortRecordLine = SortRecordLine::query()->first();
        $productionLine = ProductionLine::query()->first();
        $fridge = Fridge::query()->first();
        $stationAdmin = User::query()->where('email', 'station.admin@demo.local')->first();

        if (! $packhouse || ! $palletType || ! $product || ! $sortRecord) {
            $this->command?->info('Required demo data missing. Run ClientTestSeeder first.');
            return;
        }

        $grades = is_array($product->grades) ? $product->grades : ['A'];

        for ($i = 1; $i <= 10; $i++) {
            $cartons = rand(20, 50);
            $cartonWeight = $product->carton_weight_kg ?? 20;
            $totalWeight = $cartons * $cartonWeight;
            $grade = $grades[array_rand($grades)];

            $palletData = $withTenant([
                'packhouse_id' => $packhouse->id,
                'pallet_type_id' => $palletType->id,
                'product_id' => $product->id,
                'sort_record_id' => $sortRecord->id,
                'sort_record_line_id' => $sortRecordLine?->id,
                'production_line_id' => $productionLine?->id,
                'cartons_count' => $cartons,
                'total_weight_kg' => $totalWeight,
                'grade' => $grade,
                'lot_no' => 'LOT-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'pallet_date' => now()->toDateString(),
                'status' => $i % 3 === 0 ? 'building' : 'cooled',
                'receipt_confirmed' => $i % 2 === 0,
                'created_by' => $stationAdmin?->id,
            ]);

            $pallet = Pallet::query()->create($palletData);

            if ($pallet && $pallet->status === 'cooled' && $fridge) {
                PalletCooling::query()->create($withTenant([
                    'pallet_id' => $pallet->id,
                    'fridge_id' => $fridge->id,
                    'entry_temp' => round(rand(15, 30) / 10, 1),
                    'entered_at' => now()->subHours(rand(6, 48)),
                    'ready_at' => now()->subHours(rand(1, 5)),
                    'recorded_by' => $stationAdmin?->id,
                ]));
            }
        }

        $this->command?->info('Seeded 10 pallets.');

        app()->forgetInstance('current_tenant_id');
    }
}
