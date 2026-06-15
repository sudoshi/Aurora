<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantClassification;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->variant = GenomicVariant::factory()->create(['gene_symbol' => 'BRCA1']);
});

it('creates a classification and auto-populates from supplied evidence', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/genomic-variants/{$this->variant->id}/classifications", [
            'population_af' => 0.0, 'revel' => 0.95,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.computed_classification', 'vus')
        ->assertJsonPath('data.computed_points', 5);
    expect(collect($response->json('data.criteria'))->pluck('code'))->toContain('PP3');
});

it('adds a curator criterion and recomputes', function () {
    $c = VariantClassification::factory()->create(['genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/criteria", ['code' => 'PVS1', 'applied_strength' => 'very_strong']);

    $response->assertStatus(200)->assertJsonPath('data.computed_points', 8);
});

it('rejects an invalid ACMG code with 422', function () {
    $c = VariantClassification::factory()->create(['genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/criteria", ['code' => 'NOPE', 'applied_strength' => 'strong'])
        ->assertStatus(422);
});

it('confirms with human sign-off', function () {
    $c = VariantClassification::factory()->create([
        'genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id,
        'computed_classification' => 'vus', 'computed_points' => 3,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/confirm", [
            'final_classification' => 'likely_pathogenic',
            'override_reason' => 'PP1_Strong segregation',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.final_classification', 'likely_pathogenic');
});

it('requires an override reason when final differs from computed (422)', function () {
    $c = VariantClassification::factory()->create([
        'genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id,
        'computed_classification' => 'vus', 'computed_points' => 3,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/confirm", ['final_classification' => 'pathogenic'])
        ->assertStatus(422);
});

it('requires authentication', function () {
    $this->postJson("/api/genomic-variants/{$this->variant->id}/classifications", [])->assertStatus(401);
});

it('applies a gene-specific BA1 threshold from a seeded spec', function () {
    app(\Database\Seeders\AcmgGeneSpecificationSeeder::class)->run();
    $myh7 = GenomicVariant::factory()->create(['gene_symbol' => 'MYH7']);

    // AF 0.002 is benign-common under MYH7's stricter BA1 (0.001) but not under the 0.05 baseline.
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/genomic-variants/{$myh7->id}/classifications", ['population_af' => 0.002]);

    $response->assertStatus(201);
    expect(collect($response->json('data.criteria'))->pluck('code'))->toContain('BA1');
});
