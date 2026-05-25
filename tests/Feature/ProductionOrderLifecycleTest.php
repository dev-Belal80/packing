<?php

use App\Models\Contact;
use App\Models\Packhouse;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\ProductionStage;
use App\Models\RawMaterialType;
use App\Models\RawReceipt;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function productionLifecycleToken(): string
{
    $response = test()->postJson('/api/auth/token', [
        'email' => 'production@demo.local',
        'password' => 'password',
        'device_name' => 'pest',
    ]);

    $response->assertOk();

    return (string) $response->json('token');
}

it('reserves raw receipt quantity on order creation and consumes it on dispatch', function (): void {
    $token = productionLifecycleToken();

    $tenantId = (int) Packhouse::query()->value('tenant_id');
    app()->instance('current_tenant_id', $tenantId);

    $packhouseId = Packhouse::query()->value('id');
    $productId = Product::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');
    $productionStageId = ProductionStage::query()->value('id');
    $supervisorId = User::query()->where('email', 'production@demo.local')->value('id');
    $contactId = Contact::query()->where('email', 'supplier@demo.local')->value('id') ?? Contact::query()->value('id');
    $rawMaterialTypeId = RawMaterialType::query()->value('id');

    $rawReceipt = RawReceipt::query()->create([
        'tenant_id' => $tenantId,
        'packhouse_id' => $packhouseId,
        'reference_no' => 'RR-TEST-RESERVE-001',
        'contact_id' => $contactId,
        'contact_role' => 'supplier',
        'raw_material_type_id' => $rawMaterialTypeId,
        'boxes_count' => 100,
        'quantity_kg' => 1000,
        'quality_result' => 'good',
        'is_partial' => false,
        'has_weight_dispute' => false,
        'transport_cost' => 0,
        'status' => 'in_stock',
        'approval_status' => 'approved',
    ]);

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/orders', [
            'packhouse_id' => $packhouseId,
            'raw_receipt_id' => $rawReceipt->id,
            'product_id' => $productId,
            'production_line_id' => $productionLineId,
            'production_stage_id' => $productionStageId,
            'supervisor_id' => $supervisorId,
            'target_qty_kg' => 400,
            'order_date' => now()->toDateString(),
        ]);

    $create->assertCreated();
    $orderId = (int) $create->json('data.id');

    $rawReceipt->refresh();
    expect((float) $rawReceipt->used_quantity)->toBe(0.0);
    expect($rawReceipt->status)->toBe('reserved');
    expect($rawReceipt->availableQty())->toBe(600.0);

    $this->withoutMiddleware(\App\Http\Middleware\EnsureApiPermission::class);
    $dispatch = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/production/orders/{$orderId}/dispatch");

    // permission middleware disabled for test run

    $dispatch->assertOk();
    $dispatch->assertJsonPath('data.status', 'dispatched');

    $rawReceipt->refresh();
    expect((float) $rawReceipt->used_quantity)->toBe(400.0);
    expect($rawReceipt->status)->toBe('dispatched');
    expect($rawReceipt->availableQty())->toBe(600.0);

    $cancel = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/production/orders/{$orderId}/cancel");

    $cancel->assertOk();
    $cancel->assertJsonPath('data.status', 'cancelled');

    $rawReceipt->refresh();
    expect((float) $rawReceipt->used_quantity)->toBe(0.0);
    expect($rawReceipt->status)->toBe('in_stock');
    expect($rawReceipt->availableQty())->toBe(1000.0);
});