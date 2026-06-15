<?php

use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('returns the ACMG criteria catalog', function () {
    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/acmg/criteria');
    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data'))->toHaveKey('PVS1');
    expect($response->json('data.PVS1.category'))->toBe('pathogenic');
});

it('requires authentication for the catalog', function () {
    $this->getJson('/api/acmg/criteria')->assertStatus(401);
});
