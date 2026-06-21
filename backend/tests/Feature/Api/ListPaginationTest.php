<?php

use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\KbChangeAlert;
use App\Models\User;

/**
 * W11-T03: proves previously-unbounded list endpoints now cap their result sets,
 * so no single request can materialize an entire growable clinical table.
 */
beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('caps the gene-drug interactions list at the hard maximum', function () {
    // Seed more rows than the 200 max so an uncapped ->get() would return all of them.
    GeneDrugInteraction::factory()->count(210)->create();

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/genomics/interactions?limit=99999');

    $response->assertStatus(200)->assertJsonPath('success', true);
    expect(count($response->json('data')))->toBeLessThanOrEqual(200);
    expect(count($response->json('data')))->toBe(200);
});

it('caps the kb-alert worklist per_page at the hard maximum', function () {
    // Seed more rows than the 100 per_page cap.
    KbChangeAlert::factory()->count(120)->create();

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/kb-alerts?per_page=99999');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBeLessThanOrEqual(100);
    expect(count($response->json('data')))->toBe(100);
});
