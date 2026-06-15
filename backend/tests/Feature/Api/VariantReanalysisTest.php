<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('canonicalizes a variant on demand', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA777', 'externalRecords' => ['ClinVarVariations' => [['variationId' => 42]]],
    ], 200)]);
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX', 'variant' => 'NC_000017.11:g.43045712G>A']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/genomic-variants/{$variant->id}/canonicalize");

    $response->assertStatus(200)
        ->assertJsonPath('data.caid', 'CA777')
        ->assertJsonPath('data.clinvar_variation_id', '42');
});

it('lists kb-change alerts for a patient', function () {
    $alert = KbChangeAlert::factory()->create(['status' => 'new']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/patients/{$alert->patient_id}/kb-alerts");

    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data'))->toHaveCount(1);
});

it('lists the global kb-alert worklist filtered by status', function () {
    KbChangeAlert::factory()->create(['status' => 'new']);
    KbChangeAlert::factory()->create(['status' => 'acknowledged']);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/kb-alerts?status=new');
    $response->assertStatus(200);
    foreach ($response->json('data') as $row) {
        expect($row['status'])->toBe('new');
    }
});

it('acknowledges an alert with a resolution note', function () {
    $alert = KbChangeAlert::factory()->create(['status' => 'new']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/kb-alerts/{$alert->id}/acknowledge", ['status' => 'dismissed', 'resolution_note' => 'Already reviewed; no change to plan']);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'dismissed');
    expect($alert->fresh()->acknowledged_by)->toBe($this->user->id);
});

it('requires authentication', function () {
    $this->getJson('/api/kb-alerts')->assertStatus(401);
});
