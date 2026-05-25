<?php

use App\Models\Packhouse;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\StockTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function productionAuthToken(): string
{
    $response = test()->postJson('/api/auth/token', [
        'email' => 'production@demo.local',
        'password' => 'password',
        'device_name' => 'pest',
    ]);

    $response->assertOk();

    return (string) $response->json('token');
}

it('creates a sort record with a single line and totals', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');
    $productionOrderId = ProductionOrder::query()->value('id');

    $payload = [
        'packhouse_id' => $packhouseId,
        'sort_date' => now()->toDateString(),
        'sort_time' => '08:30',
        'accounting_period' => now()->format('Y-m'),
        'description_en' => 'daily sort',
        'notes' => 'single line',
        'lines' => [
            [
                'raw_type' => 'Orange',
                'lot_no' => '10022',
                'production_line_id' => $productionLineId,
                'production_order_id' => $productionOrderId,
                'grade_a_kg' => 1.5,
                'grade_b_kg' => 2.0,
                'grade_c_kg' => 0.5,
                'waste_kg' => 0.2,
                'returned_kg' => 0.1,
            ],
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', $payload);

    // debug removed

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'draft');
    $response->assertJsonCount(1, 'data.lines');

    $data = $response->json('data');
    expect((float) $data['total_grade_a'])->toBe(1.5);
    expect((float) $data['total_grade_b'])->toBe(2.0);
    expect((float) $data['total_grade_c'])->toBe(0.5);
    expect((float) $data['total_waste'])->toBe(0.2);
    expect((float) $data['total_returned'])->toBe(0.1);
    expect((float) $data['total_sort'])->toBe(4.3);
});

it('posts a sort record and blocks updates', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');
    $productionOrderId = ProductionOrder::query()->value('id');

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
            'lines' => [
                [
                    'raw_type' => 'Orange',
                    'production_line_id' => $productionLineId,
                    'production_order_id' => $productionOrderId,
                    'grade_a_kg' => 1.0,
                    'grade_b_kg' => 1.0,
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

    $post->assertOk();
    $post->assertJsonPath('data.status', 'posted');

    $update = $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson("/api/production/sort-records/{$sortRecordId}", [
            'notes' => 'try update',
        ]);

    $update->assertStatus(403);
});

it('requires a production order on sort record lines', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
            'lines' => [
                [
                    'raw_type' => 'Orange',
                    'production_line_id' => $productionLineId,
                    'grade_a_kg' => 1.0,
                    'grade_b_kg' => 1.0,
                    'grade_c_kg' => 0.0,
                    'waste_kg' => 0.0,
                    'returned_kg' => 0.0,
                ],
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lines.0.production_order_id']);
});

it('creates stock transactions when posting a sort record', function (): void {
    $token = productionAuthToken();

    $packhouseId = Packhouse::query()->value('id');
    $productionLineId = ProductionLine::query()->value('id');
    $productionOrderId = ProductionOrder::query()->value('id');

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/production/sort-records', [
            'packhouse_id' => $packhouseId,
            'sort_date' => now()->toDateString(),
            'lines' => [
                [
                    'raw_type' => 'Orange',
                    'production_line_id' => $productionLineId,
                    'production_order_id' => $productionOrderId,
                    'grade_a_kg' => 1.0,
                    'grade_b_kg' => 1.5,
                    'grade_c_kg' => 0.5,
                    'waste_kg' => 0.25,
                    'returned_kg' => 0.0,
                ],
            ],
        ]);

    $create->assertCreated();
    $sortRecordId = $create->json('data.id');

    $beforeCount = StockTransaction::query()->where('production_order_id', $productionOrderId)->count();

    $post = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/production/sort-records/{$sortRecordId}/post");

    $post->assertOk();

    $transactions = StockTransaction::query()
        ->where('production_order_id', $productionOrderId)
        ->orderBy('id')
        ->get();

    expect($transactions)->toHaveCount($beforeCount + 3);

    $created = $transactions->slice($beforeCount)->values();

    expect($created[0]->type)->toBe('out');
    expect($created[0]->reason)->toBe('sort_consumption');
    expect($created[0]->sort_record_line_id)->not()->toBeNull();
    expect((float) $created[0]->quantity_kg)->toBe(3.25);

    expect($created[1]->type)->toBe('in');
    expect($created[1]->reason)->toBe('sort_finished_goods');
    expect($created[1]->sort_record_line_id)->not()->toBeNull();
    expect((float) $created[1]->quantity_kg)->toBe(3.0);

    expect($created[2]->type)->toBe('out');
    expect($created[2]->reason)->toBe('sort_waste');
    expect($created[2]->sort_record_line_id)->not()->toBeNull();
    expect((float) $created[2]->quantity_kg)->toBe(0.25);
});
