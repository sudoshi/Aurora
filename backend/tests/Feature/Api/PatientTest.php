<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\Medication;
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

describe('GET /api/patients', function () {
    it('returns paginated patient list', function () {
        ClinicalPatient::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Index uses ApiResponse::success() with paginator, items are at data.data
        expect(count($response->json('data.data')))->toBeGreaterThanOrEqual(3);
    });

    it('respects per_page parameter', function () {
        ClinicalPatient::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients?per_page=2');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Index uses ApiResponse::success() with paginator, so per_page is at data.per_page
        expect($response->json('data.per_page'))->toBe(2);
        expect($response->json('data.data'))->toHaveCount(2);
    });

    it('requires authentication', function () {
        $this->getJson('/api/patients')->assertStatus(401);
    });
});

describe('GET /api/patients/{patient}/notes', function () {
    it('returns paginated notes for patient', function () {
        $patient = ClinicalPatient::factory()->create();

        // Insert a clinical note directly
        \Illuminate\Support\Facades\DB::table('clinical.clinical_notes')->insert([
            'patient_id' => $patient->id,
            'note_type' => 'progress',
            'content' => 'Patient is recovering well.',
            'authored_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/patients/{$patient->id}/notes");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
    });

    it('returns 404 for non-existent patient', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/patients/99999/notes');

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        $this->getJson('/api/patients/1/notes')->assertStatus(401);
    });
});

describe('PUT /api/patients/{id} (not implemented)', function () {
    // BTEST-02 requires update endpoint tests. PUT /api/patients/{id} has no route
    // defined in routes/api.php and no update() method in PatientController.
    // This test documents the gap.
    // Note: The catch-all exception handler in bootstrap/app.php converts
    // MethodNotAllowedHttpException to 500 for JSON requests.
    it('returns error because update endpoint is not implemented', function () {
        $patient = ClinicalPatient::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/patients/{$patient->id}", [
                'first_name' => 'Updated',
            ]);

        $response->assertJsonPath('success', false);
        expect($response->status())->toBeGreaterThanOrEqual(400);
    });
});

describe('GET /api/patients/{id}/timeline (not implemented)', function () {
    // BTEST-02 requires timeline endpoint tests. GET /api/patients/{id}/timeline
    // has no route defined in routes/api.php and no timeline() method in PatientController.
    // This test documents the gap.
    // Note: The catch-all exception handler in bootstrap/app.php converts
    // NotFoundHttpException to 500 for JSON requests.
    it('returns error because timeline endpoint is not implemented', function () {
        $patient = ClinicalPatient::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/patients/{$patient->id}/timeline");

        $response->assertJsonPath('success', false);
        expect($response->status())->toBeGreaterThanOrEqual(400);
    });
});
