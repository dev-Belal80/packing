<?php

use App\Models\RawReceipt;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('returns normalized role and permissions for bearer auth', function (): void {
    $tokenResponse = app()->handle(Request::create('/api/auth/token', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'email' => 'station.admin@demo.local',
        'password' => 'password',
        'device_name' => 'pest',
    ], JSON_THROW_ON_ERROR)));

    expect($tokenResponse->getStatusCode())->toBe(200);

    $tokenPayload = json_decode($tokenResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
    expect($tokenPayload['user']['role'])->toBe('manager');
    expect($tokenPayload['permissions']['screens'])->toContain('dashboard', 'raw-receipt', 'receipts-list', 'production-order', 'shipping-policy');
    expect($tokenPayload['permissions']['actions'])->toContain('receipt.approve', 'order.dispatch', 'shipping.approve');

    $stationAdmin = User::query()->where('email', 'station.admin@demo.local')->firstOrFail();

    $meResponse = $this->actingAs($stationAdmin, 'sanctum')->getJson('/api/auth/me');

    $meResponse->assertOk();

    $mePayload = $meResponse->json();
    expect($mePayload['user']['role'])->toBe('manager');
    expect($mePayload['permissions']['actions'])->toContain('receipt.approve');
});

it('returns a consistent forbidden payload for unauthorized actions', function (): void {
    $login = $this->postJson('/api/auth/token', [
        'email' => 'reception@demo.local',
        'password' => 'password',
        'device_name' => 'pest',
    ]);

    $rawReceiptId = RawReceipt::query()->value('id');

    $response = $this->withHeader('Authorization', 'Bearer '.$login->json('token'))
        ->patchJson("/api/reception/raw-receipts/{$rawReceiptId}/approve");

    $response->assertStatus(403);
    $response->assertExactJson([
        'message' => 'Forbidden',
        'code' => 'FORBIDDEN',
        'required_permission' => 'receipt.approve',
    ]);
});
