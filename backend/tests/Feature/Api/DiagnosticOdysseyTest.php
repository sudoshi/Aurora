<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->patient = ClinicalPatient::factory()->create();
});

describe('POST /api/patients/{patient}/odysseys', function () {
    it('creates an odyssey in referral', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/patients/{$this->patient->id}/odysseys", [
                'title' => 'Undiagnosed myopathy',
                'referral_reason' => 'Progressive weakness, normal initial workup',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'referral')
            ->assertJsonPath('data.progress_status', 'in_progress');
    });

    it('requires a title', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/patients/{$this->patient->id}/odysseys", [])
            ->assertStatus(422);
    });

    it('requires authentication', function () {
        $this->postJson("/api/patients/{$this->patient->id}/odysseys", ['title' => 'x'])
            ->assertStatus(401);
    });
});

describe('GET /api/patients/{patient}/odysseys', function () {
    it('lists odysseys for a patient', function () {
        DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/patients/{$this->patient->id}/odysseys");

        $response->assertStatus(200)->assertJsonPath('success', true);
        expect($response->json('data'))->toHaveCount(1);
    });
});

describe('POST /api/odysseys/{odyssey}/transition', function () {
    it('advances through an allowed transition', function () {
        $odyssey = DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/odysseys/{$odyssey->id}/transition", [
                'to_status' => 'phenotyping',
                'note' => 'Begin deep phenotyping',
            ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'phenotyping');
    });

    it('rejects an illegal transition with 422', function () {
        $odyssey = DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'status' => 'referral',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/odysseys/{$odyssey->id}/transition", ['to_status' => 'diagnosed'])
            ->assertStatus(422);
    });
});

describe('GET /api/odysseys/{odyssey}/phenopacket', function () {
    it('exports a phenopacket with the patient as subject', function () {
        $odyssey = DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/odysseys/{$odyssey->id}/phenopacket");

        $response->assertStatus(200)
            ->assertJsonPath('data.subject.id', (string) $this->patient->id)
            ->assertJsonPath('data.metaData.phenopacketSchemaVersion', '2.0');
    });
});
