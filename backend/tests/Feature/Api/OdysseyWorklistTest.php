<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('lists all odysseys with patient + phenotype count, paginated', function () {
    $patient = ClinicalPatient::factory()->create();
    DiagnosticOdyssey::factory()->count(2)->create([
        'patient_id' => $patient->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/odysseys');

    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data'))->toBeArray();
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
});

it('filters the worklist by status', function () {
    $patient = ClinicalPatient::factory()->create();
    DiagnosticOdyssey::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id, 'status' => 'phenotyping']);
    DiagnosticOdyssey::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id, 'status' => 'referral']);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/odysseys?status=phenotyping');

    $response->assertStatus(200);
    foreach ($response->json('data') as $row) {
        expect($row['status'])->toBe('phenotyping');
    }
});

it('requires authentication', function () {
    $this->getJson('/api/odysseys')->assertStatus(401);
});
