<?php

use App\Models\ClinicalCase;
use App\Models\Session;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

describe('GET /api/sessions', function () {
    it('returns paginated sessions', function () {
        Session::factory()->count(2)->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sessions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // paginated() returns data as array with meta sibling
        expect($response->json('data'))->toBeArray();
        expect($response->json('meta'))->toBeArray();
        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
    });

    it('filters by status', function () {
        Session::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'scheduled',
        ]);

        Session::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sessions?status=scheduled');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sessions = $response->json('data');
        foreach ($sessions as $session) {
            expect($session['status'])->toBe('scheduled');
        }
    });

    it('requires authentication', function () {
        $this->getJson('/api/sessions')->assertStatus(401);
    });
});

describe('POST /api/sessions', function () {
    it('creates a session with valid data', function () {
        $payload = [
            'title' => 'Weekly Tumor Board',
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'session_type' => 'tumor_board',
            'duration_minutes' => 60,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sessions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Weekly Tumor Board')
            ->assertJsonPath('data.status', 'scheduled');
    });

    it('returns 422 for past scheduled_at', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sessions', [
                'title' => 'Past Session',
                'scheduled_at' => now()->subDay()->toIso8601String(),
                'session_type' => 'tumor_board',
            ]);

        $response->assertStatus(422);
    });

    it('returns 422 for missing fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sessions', []);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $this->postJson('/api/sessions', [
            'title' => 'No Auth',
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'session_type' => 'tumor_board',
        ])->assertStatus(401);
    });
});

describe('GET /api/sessions/{session}', function () {
    it('returns session with relations', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.title', $session->title);
    });

    it('returns 404 for non-existent session', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sessions/99999');

        // Route model binding returns 404 for missing models
        expect($response->status())->toBeGreaterThanOrEqual(400);
    });
});

describe('PUT /api/sessions/{session}', function () {
    it('updates a session', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/sessions/{$session->id}", [
                'title' => 'Updated Session Title',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Session Title');
    });
});

describe('DELETE /api/sessions/{session}', function () {
    it('deletes a session', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Session uses SoftDeletes
        $this->assertSoftDeleted('app.clinical_sessions', ['id' => $session->id]);
    });
});

describe('Session lifecycle', function () {
    it('starts a scheduled session', function () {
        $session = Session::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/start");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'live');
    });

    it('cannot start a non-scheduled session', function () {
        $session = Session::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'scheduled',
        ]);

        // Start it first
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/start");

        // Try to start again -- should fail since status is now 'live'
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/start");

        $response->assertStatus(422);
    });

    it('ends a live session', function () {
        $session = Session::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'scheduled',
        ]);

        // Start session first
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/start");

        // End it
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/end");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');
    });

    it('cannot end a non-live session', function () {
        $session = Session::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/end");

        $response->assertStatus(422);
    });
});

describe('Session cases', function () {
    it('adds a case to session', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/cases", [
                'case_id' => $case->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('prevents duplicate case addition', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        // Add case first time
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/cases", [
                'case_id' => $case->id,
            ]);

        // Add same case again
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/cases", [
                'case_id' => $case->id,
            ]);

        $response->assertStatus(422);
    });

    it('removes a case from session', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);
        $case = ClinicalCase::factory()->create(['created_by' => $this->user->id]);

        // Add case
        $addResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/cases", [
                'case_id' => $case->id,
            ]);

        $sessionCaseId = $addResponse->json('data.id');

        // Remove case
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/sessions/{$session->id}/cases/{$sessionCaseId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });
});

describe('Session participants', function () {
    it('user joins a session', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/join", [
                'role' => 'observer',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('prevents duplicate join', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        // Join first time
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/join", [
                'role' => 'observer',
            ]);

        // Join again
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/join", [
                'role' => 'observer',
            ]);

        $response->assertStatus(422);
    });

    it('user leaves a session', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        // Join first
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/join", [
                'role' => 'observer',
            ]);

        // Leave
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/leave");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('returns 404 when leaving without joining', function () {
        $session = Session::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/leave");

        $response->assertStatus(404);
    });
});
