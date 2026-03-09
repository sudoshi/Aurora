<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\Measurement;
use App\Models\Clinical\Medication;
use App\Models\Clinical\Observation;
use App\Models\Clinical\Procedure;
use App\Models\Clinical\Visit;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

describe('POST /api/patients', function () {
    it('creates a patient with valid data', function () {
        $payload = [
            'mrn' => 'MRN-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1985-03-15',
            'sex' => 'Female',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/patients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mrn', 'MRN-001')
            ->assertJsonPath('data.first_name', 'Jane');

        $this->assertDatabaseHas('patients', [
            'mrn' => 'MRN-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    });

    it('returns 422 for missing required fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/patients', []);

        $response->assertStatus(422);
    });

    it('returns 422 for duplicate MRN', function () {
        ClinicalPatient::create([
            'mrn' => 'MRN-DUP',
            'first_name' => 'First',
            'last_name' => 'Patient',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/patients', [
                'mrn' => 'MRN-DUP',
                'first_name' => 'Second',
                'last_name' => 'Patient',
            ]);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/patients', [
            'mrn' => 'MRN-NOAUTH',
            'first_name' => 'No',
            'last_name' => 'Auth',
        ]);

        $response->assertStatus(401);
    });
});

describe('GET /api/patients/{patient}/profile', function () {
    it('returns a full patient profile', function () {
        $patient = ClinicalPatient::create([
            'mrn' => 'MRN-PROFILE',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'date_of_birth' => '1970-01-01',
            'sex' => 'Male',
        ]);

        Condition::create([
            'patient_id' => $patient->id,
            'concept_name' => 'Lung Cancer',
            'concept_code' => 'C34.9',
            'vocabulary' => 'ICD10',
            'domain' => 'oncology',
            'status' => 'active',
        ]);

        Medication::create([
            'patient_id' => $patient->id,
            'drug_name' => 'Pembrolizumab',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/patients/{$patient->id}/profile");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.patient.mrn', 'MRN-PROFILE')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'patient',
                    'conditions',
                    'medications',
                    'procedures',
                    'measurements',
                    'observations',
                    'visits',
                    'notes',
                    'imaging',
                    'genomics',
                ],
            ]);

        expect($response->json('data.conditions'))->toHaveCount(1);
        expect($response->json('data.medications'))->toHaveCount(1);
    });

    it('returns 404 for non-existent patient', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients/99999/profile');

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/patients/1/profile');

        $response->assertStatus(401);
    });
});

describe('GET /api/patients/search', function () {
    it('searches patients by name', function () {
        ClinicalPatient::create([
            'mrn' => 'MRN-SEARCH1',
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
        ]);

        ClinicalPatient::create([
            'mrn' => 'MRN-SEARCH2',
            'first_name' => 'Bob',
            'last_name' => 'Builder',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients/search?q=Alice');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.first_name'))->toBe('Alice');
    });

    it('searches patients by MRN', function () {
        ClinicalPatient::create([
            'mrn' => 'MRN-UNIQUE-42',
            'first_name' => 'Test',
            'last_name' => 'MRN',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients/search?q=MRN-UNIQUE-42');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
    });

    it('returns 422 when query is missing', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients/search');

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/patients/search?q=test');

        $response->assertStatus(401);
    });
});

describe('GET /api/patients/{patient}/stats', function () {
    it('returns domain counts for a patient', function () {
        $patient = ClinicalPatient::create([
            'mrn' => 'MRN-STATS',
            'first_name' => 'Stats',
            'last_name' => 'Patient',
        ]);

        Condition::create([
            'patient_id' => $patient->id,
            'concept_name' => 'Hypertension',
            'status' => 'active',
        ]);

        Condition::create([
            'patient_id' => $patient->id,
            'concept_name' => 'Diabetes',
            'status' => 'chronic',
        ]);

        Medication::create([
            'patient_id' => $patient->id,
            'drug_name' => 'Metformin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/patients/{$patient->id}/stats");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.conditions', 2)
            ->assertJsonPath('data.medications', 1)
            ->assertJsonPath('data.procedures', 0)
            ->assertJsonPath('data.measurements', 0)
            ->assertJsonPath('data.observations', 0)
            ->assertJsonPath('data.visits', 0)
            ->assertJsonPath('data.notes', 0)
            ->assertJsonPath('data.imaging_studies', 0)
            ->assertJsonPath('data.genomic_variants', 0);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/patients/1/stats');

        $response->assertStatus(401);
    });
});
