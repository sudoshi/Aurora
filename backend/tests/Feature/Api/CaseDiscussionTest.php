<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\ClinicalCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->patient = ClinicalPatient::factory()->create();

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
                'content' => 'Patient responding well to treatment protocol.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('returns 422 for missing content', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$this->clinicalCase->id}/discussions", []);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson("/api/cases/{$this->clinicalCase->id}/discussions", [
            'content' => 'Unauthorized discussion.',
        ]);

        $response->assertStatus(401);
    });
});

describe('POST /api/cases/{id}/documents', function () {
    it('uploads a document for a case', function () {
        fakeIsolatedLocalDisk('case-documents');

        $response = $this->actingAs($this->user, 'sanctum')
            ->post("/api/cases/{$this->clinicalCase->id}/documents", [
                'file' => UploadedFile::fake()->create('lab-results.pdf', 512, 'application/pdf'),
                'document_type' => 'clinical_note',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    });

    it('returns 422 for missing files', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/cases/{$this->clinicalCase->id}/documents", []);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson("/api/cases/{$this->clinicalCase->id}/documents", [
            'document_type' => 'radiology',
        ]);

        $response->assertStatus(401);
    });
});
