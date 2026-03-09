<?php

use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->patient = Patient::factory()->create();

    $this->clinicalCase = ClinicalCase::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

describe('GET /api/cases/{id}/discussions', function () {
    it('returns discussions for a case', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/cases/{$this->clinicalCase->id}/discussions");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('returns 404 for non-existent case', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cases/99999/discussions');

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/cases/{$this->clinicalCase->id}/discussions");

        $response->assertStatus(401);
    });
});

describe('POST /api/cases/{id}/discussions', function () {
    it('creates a discussion for a case', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$this->clinicalCase->id}/discussions", [
                'message' => 'Patient responding well to treatment protocol.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('returns 422 for missing message', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$this->clinicalCase->id}/discussions", []);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson("/api/cases/{$this->clinicalCase->id}/discussions", [
            'message' => 'Unauthorized discussion.',
        ]);

        $response->assertStatus(401);
    });
});

describe('POST /api/cases/{id}/attachments', function () {
    it('uploads files for a case', function () {
        Storage::fake('local');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$this->clinicalCase->id}/attachments", [
                'files' => [
                    UploadedFile::fake()->create('lab-results.pdf', 512, 'application/pdf'),
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('returns 422 for missing files', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$this->clinicalCase->id}/attachments", []);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson("/api/cases/{$this->clinicalCase->id}/attachments", [
            'files' => [
                UploadedFile::fake()->create('scan.dcm', 1024),
            ],
        ]);

        $response->assertStatus(401);
    });
});
