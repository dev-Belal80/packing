<?php

use App\Models\Contact;
use App\Models\Fridge;
use App\Models\Packhouse;
use App\Models\PalletType;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\SortRecord;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function stationAdminToken(): string
{
    $response = test()->postJson('/api/auth/token', [
        'email' => 'station.admin@demo.local',
        'password' => 'password',
        'device_name' => 'pest',
    ]);

    $response->assertOk();

    return (string) $response->json('token');
}

it('creates a pallet with the expanded export form fields', function (): void {
    $token = stationAdminToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');
    $palletTypeId = PalletType::query()->value('id');
    $productId = Product::query()->value('id');
    $sortRecordId = SortRecord::query()->value('id');
    $productionOrderId = ProductionOrder::query()->value('id');
    $fridgeId = Fridge::query()->value('id');
    $clientContactId = Contact::query()->where('email', 'importer@demo.local')->value('id');
    $supplierContactId = Contact::query()->where('email', 'supplier@demo.local')->value('id');

    $payload = [
        'packhouse_id' => $packhouseId,
        'sort_record_id' => $sortRecordId,
        'pallet_date' => now()->toDateString(),
        'pallet_time' => '09:30',
        'branch' => 'الرئيسي',
        'wooden_pallet_no' => 'WP-12',
        'order_number' => 'ORD-1001',
        'final_order_no' => 'FIN-1001',
        'production_line_id' => $productionLineId,
        'pallet_type_id' => $palletTypeId,
        'client_contact_id' => $clientContactId,
        'client_code' => '38345',
        'product_id' => $productId,
        'supplier_contact_id' => $supplierContactId,
        'lot_no' => 'LOT-55',
        'fridge_id' => $fridgeId,
        'raw_type' => 'Orange',
        'package_type' => 'Carton',
        'size' => '4.5 kg',
        'grade' => 'A',
        'storage_location' => 'A-1',
        'actual_weight' => 600,
        'net_weight' => 594.5,
        'cooling_start' => now()->subHours(4)->toDateTimeString(),
        'cooling_end' => now()->subHours(1)->toDateTimeString(),
        'stickers' => 'Main sticker',
        'customer_lot_no' => 'C-9001',
        'has_carton' => true,
        'has_punnet' => false,
        'no_label' => false,
        'original_pallet_ref' => 'DS0001',
        'special_specs' => 'Keep upright',
        'production_order_id' => $productionOrderId,
        'cartons_count' => 30,
        'status' => 'building',
    ];

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/export/pallets', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.branch', 'الرئيسي');
    $response->assertJsonPath('data.actual_weight', '600.000');
    $response->assertJsonPath('data.net_weight', '594.500');
    $response->assertJsonPath('data.weight_diff', '5.500');
    expect((string) $response->json('data.reference_no'))->toStartWith('DS');
});

it('returns available posted sort lines for pallet selection', function (): void {
    $token = stationAdminToken();

    $sortRecord = SortRecord::query()->firstOrFail();
    $sortRecord->forceFill(['status' => 'posted'])->save();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/export/pallets/available-orders?per_page=10');

    $response->assertOk();
    $response->assertJsonStructure([
        'status',
        'data' => [[
            'id',
            'sort_record_id',
            'sort_record_reference_no',
            'sort_date',
            'lot_no',
            'production_line',
            'product',
            'order_number',
            'available_cartons',
            'available_weight_kg',
        ]],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);

    expect($response->json('data.0.available_cartons'))->toBeGreaterThan(0);
});

it('rejects pallet cartons that exceed the available sort line quantity', function (): void {
    $token = stationAdminToken();

    $availableResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/export/pallets/available-orders?per_page=10');

    $availableResponse->assertOk();

    $availableLine = collect($availableResponse->json('data'))
        ->first(fn (array $item): bool => (int) ($item['available_cartons'] ?? 0) > 0);

    expect($availableLine)->not->toBeNull();

    $palletTypeId = PalletType::query()->value('id');
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/export/pallets', [
            'available_order_id' => $availableLine['available_order_id'],
            'pallet_type_id' => $palletTypeId,
            'cartons_count' => ((int) $availableLine['available_cartons']) + 1,
            'actual_weight' => 600,
            'net_weight' => 594.5,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['cartons_count']);
});
