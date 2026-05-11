<?php

namespace Tests\Feature;

use App\Models\Packhouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionDeliveryOrderDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_soft_deletes_a_reception_delivery_order_through_the_api(): void
    {
        $tokenResponse = $this->postJson('/api/auth/token', [
            'email' => 'reception@demo.local',
            'password' => 'password',
            'device_name' => 'pest',
        ]);

        $tokenResponse->assertOk();

        $packhouseId = Packhouse::query()->value('id');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$tokenResponse->json('token'))
            ->postJson('/api/reception/delivery-orders', [
                'packhouse_id' => $packhouseId,
                'order_date' => now()->toDateString(),
                'received_qty' => 100,
                'price_per_unit' => 10,
            ]);

        $createResponse->assertCreated();

        $deliveryOrderId = $createResponse->json('data.id');

        $deleteResponse = $this->withHeader('Authorization', 'Bearer '.$tokenResponse->json('token'))
            ->deleteJson("/api/reception/delivery-orders/{$deliveryOrderId}");

        $deleteResponse->assertOk();
        $deleteResponse->assertJson([
            'status' => 'success',
            'message' => 'تم الحذف',
            'data' => [
                'id' => $deliveryOrderId,
                'deleted' => true,
            ],
        ]);

        $this->assertSoftDeleted('raw_delivery_orders', [
            'id' => $deliveryOrderId,
        ]);
    }
}
