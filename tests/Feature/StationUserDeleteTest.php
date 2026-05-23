<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('allows station admin to delete a user in their tenant', function (): void {
    $stationAdmin = User::query()->where('email', 'station.admin@demo.local')->firstOrFail();
    $target = User::query()->where('email', 'reception@demo.local')->firstOrFail();

    expect($target->tenant_id)->toBe($stationAdmin->tenant_id);

    $response = $this->actingAs($stationAdmin, 'sanctum')
        ->deleteJson("/api/station/users/{$target->id}");

    $response->assertOk();
    $response->assertJson([
        'ok' => true,
        'id' => $target->id,
    ]);

    $target->refresh();
    expect($target->is_active)->toBeFalse();
});

it('prevents station admin from deleting themselves', function (): void {
    $stationAdmin = User::query()->where('email', 'station.admin@demo.local')->firstOrFail();

    $response = $this->actingAs($stationAdmin, 'sanctum')
        ->deleteJson("/api/station/users/{$stationAdmin->id}");

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'You cannot delete your own user.',
    ]);
});
