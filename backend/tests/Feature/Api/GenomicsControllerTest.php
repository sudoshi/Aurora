<?php

use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\GenomicVariant;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

// ── Stats ────────────────────────────────────────────────────────────────

describe('GET /api/genomics/stats', function () {
    it('returns genomics statistics', function () {
        GenomicVariant::factory()->count(3)->create(['clinical_significance' => 'pathogenic']);
        GenomicVariant::factory()->count(2)->create(['clinical_significance' => 'VUS']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_variants', 5)
            ->assertJsonPath('data.pathogenic_count', 3)
            ->assertJsonPath('data.vus_count', 2);
    });

    it('returns zeros when no variants exist', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_variants', 0)
            ->assertJsonPath('data.pathogenic_count', 0)
            ->assertJsonPath('data.vus_count', 0);
    });

    it('requires authentication', function () {
        $this->getJson('/api/genomics/stats')
            ->assertStatus(401);
    });
});

// ── Interactions ──────────────────────────────────────────────────────────

describe('GET /api/genomics/interactions', function () {
    it('returns gene-drug interactions', function () {
        GeneDrugInteraction::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/interactions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);

        expect(count($response->json('data')))->toBe(3);
    });

    it('filters by gene', function () {
        GeneDrugInteraction::factory()->create(['gene' => 'BRAF', 'drug' => 'Vemurafenib']);
        GeneDrugInteraction::factory()->create(['gene' => 'BRAF', 'drug' => 'Dabrafenib']);
        GeneDrugInteraction::factory()->create(['gene' => 'KRAS', 'drug' => 'Sotorasib']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/interactions?gene=BRAF');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(2);
        foreach ($data as $item) {
            expect($item['gene'])->toBe('BRAF');
        }
    });

    it('requires authentication', function () {
        $this->getJson('/api/genomics/interactions')
            ->assertStatus(401);
    });
});

// ── Variants ──────────────────────────────────────────────────────────────

describe('GET /api/genomics/variants', function () {
    it('returns paginated variants', function () {
        GenomicVariant::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/variants');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'meta']);

        expect(count($response->json('data')))->toBe(5);
    });

    it('filters by gene', function () {
        GenomicVariant::factory()->count(2)->create(['gene' => 'BRCA1']);
        GenomicVariant::factory()->count(3)->create(['gene' => 'TP53']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/variants?gene=BRCA1');

        $response->assertStatus(200);
        expect(count($response->json('data')))->toBe(2);
    });

    it('requires authentication', function () {
        $this->getJson('/api/genomics/variants')
            ->assertStatus(401);
    });
});

describe('GET /api/genomics/variants/{id}', function () {
    it('returns a single variant', function () {
        $variant = GenomicVariant::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/genomics/variants/{$variant->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $variant->id);
    });

    it('returns 404 for non-existent variant', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/variants/99999');

        $response->assertStatus(404);
    });
});

// ── Upload stubs ──────────────────────────────────────────────────────────

describe('Genomics upload stubs', function () {
    it('listUploads returns empty array', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/uploads');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    });

    it('showUpload returns stub data', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/uploads/1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 1);
    });

    it('destroyUpload returns success', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/genomics/uploads/1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });
});

// ── Criteria stubs ────────────────────────────────────────────────────────

describe('Genomics criteria stubs', function () {
    it('listCriteria returns empty array', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/criteria');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    });

    it('storeCriterion returns stub', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/criteria', [
                'name' => 'Test Criterion',
                'criteria_type' => 'variant',
                'criteria_definition' => ['gene' => 'BRAF'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Criterion');
    });

    it('updateCriterion returns stub', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/genomics/criteria/1', [
                'name' => 'Updated Criterion',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Criterion');
    });

    it('destroyCriterion returns success', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/genomics/criteria/1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });
});

// ── ClinVar endpoints ─────────────────────────────────────────────────────

describe('ClinVar endpoints', function () {
    it('clinvarStatus returns status data', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/clinvar/status');

        // clinvarStatus returns { data: {...} } without success field
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_variants', 'pathogenic_count']]);
    });

    it('clinvarSearch returns paginated results', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/clinvar/search');

        // clinvarSearch returns raw Laravel paginator JSON (no API envelope)
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    });
});
