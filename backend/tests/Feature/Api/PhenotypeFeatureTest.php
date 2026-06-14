<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
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

it('adds an observed phenotype feature', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/phenotypes", [
            'hpo_id' => 'HP:0001250',
            'hpo_label' => 'Seizure',
            'severity_hpo_id' => 'HP:0012828',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.hpo_id', 'HP:0001250')
        ->assertJsonPath('data.excluded', false);
});

it('records an explicitly excluded (absent) phenotype', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/phenotypes", [
            'hpo_id' => 'HP:0001251',
            'hpo_label' => 'Ataxia',
            'excluded' => true,
        ]);

    $response->assertStatus(201)->assertJsonPath('data.excluded', true);
});

it('rejects a malformed HPO id', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/phenotypes", [
            'hpo_id' => 'seizure',
            'hpo_label' => 'Seizure',
        ])->assertStatus(422);
});

it('lists phenotype features for an odyssey', function () {
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'recorded_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/odysseys/{$this->odyssey->id}/phenotypes");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a phenotype feature', function () {
    $feature = PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'recorded_by' => $this->user->id,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/phenotypes/{$feature->id}")
        ->assertStatus(200);

    expect(PhenotypeFeature::find($feature->id))->toBeNull();
});
