<?php

use App\Models\Packhouse;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});


it('rejects create without lines or with empty lines', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');

    // no lines key
    $resp1 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
        ]);

    $resp1->assertStatus(422);

    // empty lines array
    $resp2 = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
            'lines' => [],
        ]);

    $resp2->assertStatus(422);
});

it('rejects negative numeric values in lines', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');
    $productionOrderId = ProductionOrder::query()->value('id');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
            'lines' => [
                [
                    'raw_type' => 'Orange',
                    'production_line_id' => $productionLineId,
                    'production_order_id' => $productionOrderId,
                    'grade_a_kg' => -1.0,
                    'grade_b_kg' => 0,
                    'grade_c_kg' => 0,
                    'waste_kg' => 0,
                    'returned_kg' => 0,
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lines.0.grade_a_kg']);
});

it('fails post when production order has no raw receipt', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');

    // create a production order without raw_receipt_id
    $po = ProductionOrder::query()->create([
        'packhouse_id' => $packhouseId,
        'production_line_id' => $productionLineId,
        'product_id' => 1,
        'target_qty_kg' => 10,
        'status' => 'reserved',
    ]);

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
            'lines' => [
                [
                    'raw_type' => 'Orange',
                    'production_line_id' => $productionLineId,
                    'production_order_id' => $po->id,
                    'grade_a_kg' => 1.0,
                    'grade_b_kg' => 0.0,
                    'grade_c_kg' => 0.0,
                    'waste_kg' => 0.0,
                    'returned_kg' => 0.0,
                ],
            ],
        ]);

    $create->assertCreated();
    $sortRecordId = $create->json('data.id');

    $post = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/production/sort-records/{$sortRecordId}/post");

    $post->assertStatus(422);
});
