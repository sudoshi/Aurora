<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\ClinicalCase;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

describe('GET /api/cases', function () {
    it('returns paginated cases for user', function () {
        // Create case with user as creator so forUser scope finds it
        ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cases');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // paginated() returns data as array with meta sibling
        expect($response->json('data'))->toBeArray();
        expect($response->json('meta'))->toBeArray();
        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
    });

    it('filters by status', function () {
        ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'active',
        ]);

        ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'closed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cases?status=active');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // All returned cases should be active
        $cases = $response->json('data');
        foreach ($cases as $case) {
            expect($case['status'])->toBe('active');
        }
    });

    it('requires authentication', function () {
        $this->getJson('/api/cases')->assertStatus(401);
    });
});

describe('POST /api/cases', function () {
    it('creates a case with valid data', function () {
        $patient = ClinicalPatient::factory()->create();

        $payload = [
            'title' => 'Test Tumor Board Case',
            'specialty' => 'oncology',
            'case_type' => 'tumor_board',
            'patient_id' => $patient->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cases', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Test Tumor Board Case');

        $this->assertDatabaseHas('app.cases', [
            'title' => 'Test Tumor Board Case',
            'specialty' => 'oncology',
            'case_type' => 'tumor_board',
        ]);
    });

    it('creates a case without patient_id', function () {
        $payload = [
            'title' => 'Case Without Patient',
            'specialty' => 'surgical',
            'case_type' => 'surgical_review',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cases', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Case Without Patient');
    });

    it('returns 422 for missing required fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cases', []);

        $response->assertStatus(422);
    });

    it('returns 422 for invalid specialty', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cases', [
                'title' => 'Bad Specialty Case',
                'specialty' => 'invalid_specialty',
                'case_type' => 'tumor_board',
            ]);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $this->postJson('/api/cases', [
            'title' => 'No Auth',
            'specialty' => 'oncology',
            'case_type' => 'tumor_board',
        ])->assertStatus(401);
    });
});

describe('GET /api/cases/{case}', function () {
    it('returns case with relations', function () {
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/cases/{$case->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $case->id)
            ->assertJsonPath('data.title', $case->title);
    });

    it('returns 404 for non-existent case', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cases/99999');

        $response->assertStatus(404);
    });
});

describe('PUT /api/cases/{case}', function () {
    it('updates a case', function () {
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/cases/{$case->id}", [
                'title' => 'Updated Case Title',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Case Title');
    });

    it('returns 404 for non-existent case', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/cases/99999', [
                'title' => 'Should Not Work',
            ]);

        $response->assertStatus(404);
    });
});

describe('DELETE /api/cases/{case}', function () {
    it('archives and soft-deletes a case', function () {
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/cases/{$case->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify soft-deleted
        $this->assertSoftDeleted('app.cases', ['id' => $case->id]);
    });
});

describe('POST /api/cases/{case}/team', function () {
    it('adds a team member', function () {
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);
        $reviewer = User::factory()->create(['is_active' => true, 'must_change_password' => false]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$case->id}/team", [
                'user_id' => $reviewer->id,
                'role' => 'reviewer',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('returns 409 for duplicate team member', function () {
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);
        $reviewer = User::factory()->create(['is_active' => true, 'must_change_password' => false]);

        // Add team member first time
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$case->id}/team", [
                'user_id' => $reviewer->id,
                'role' => 'reviewer',
            ]);

        // Add same team member again -- should conflict
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$case->id}/team", [
                'user_id' => $reviewer->id,
                'role' => 'reviewer',
            ]);

        $response->assertStatus(409);
    });
});

describe('DELETE /api/cases/{case}/team/{user}', function () {
    it('removes a team member', function () {
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);
        $reviewer = User::factory()->create(['is_active' => true, 'must_change_password' => false]);

        // Add team member
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$case->id}/team", [
                'user_id' => $reviewer->id,
                'role' => 'reviewer',
            ]);

        // Remove team member
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/cases/{$case->id}/team/{$reviewer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });
});
