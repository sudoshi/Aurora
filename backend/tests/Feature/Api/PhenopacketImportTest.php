<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

it('imports phenotypic features from a phenopacket', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/import-phenopacket", [
            'phenotypicFeatures' => [
                ['type' => ['id' => 'HP:0001250', 'label' => 'Seizure'], 'excluded' => false],
                ['type' => ['id' => 'HP:0001251', 'label' => 'Ataxia'], 'excluded' => true],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.imported', 2)
        ->assertJsonPath('data.skipped', 0);
    expect($this->odyssey->phenotypeFeatures()->count())->toBe(2);
});

it('returns 422 for a malformed phenopacket', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/import-phenopacket", [
            'phenotypicFeatures' => [['type' => ['id' => 'not-hpo']]],
        ])->assertStatus(422);
});

it('requires authentication', function () {
    $this->postJson("/api/odysseys/{$this->odyssey->id}/import-phenopacket", [])->assertStatus(401);
});
