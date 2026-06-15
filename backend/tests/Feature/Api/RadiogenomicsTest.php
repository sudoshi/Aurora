<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicVariant;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

// ── Patient Panel ─────────────────────────────────────────────────────────

describe('GET /api/radiogenomics/patients/{patientId}', function () {
    it('returns patient panel with variants', function () {
        $patient = ClinicalPatient::factory()->create();
        GenomicVariant::factory()->count(2)->create([
            'patient_id' => $patient->id,
            'clinical_significance' => 'pathogenic',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/radiogenomics/patients/{$patient->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'patient_id',
                    'demographics',
                    'variants',
                    'imaging',
                    'drug_exposures',
                    'correlations',
                    'recommendations',
                ],
            ]);

        expect($response->json('data.patient_id'))->toBe($patient->id);
        expect($response->json('data.variants.total'))->toBe(2);
    });

    it('returns 404 for non-existent patient', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/radiogenomics/patients/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    });

    it('requires authentication', function () {
        $this->getJson('/api/radiogenomics/patients/1')
            ->assertStatus(401);
    });
});

// ── Variant-Drug Interactions ─────────────────────────────────────────────

describe('GET /api/radiogenomics/variant-drug-interactions', function () {
    it('returns hardcoded interaction reference', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/radiogenomics/variant-drug-interactions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
        expect($data[0])->toHaveKeys(['gene_symbol', 'drug_name', 'relationship']);
    });

    it('filters by gene', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/radiogenomics/variant-drug-interactions?gene=BRAF');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
        foreach ($data as $item) {
            expect(str_contains(strtoupper($item['gene_symbol']), 'BRAF'))->toBeTrue();
        }
    });

    it('filters by relationship', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/radiogenomics/variant-drug-interactions?relationship=resistant');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
        foreach ($data as $item) {
            expect($item['relationship'])->toBe('resistant');
        }
    });

    it('requires authentication', function () {
        $this->getJson('/api/radiogenomics/variant-drug-interactions')
            ->assertStatus(401);
    });
});
