<?php

use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\GenomicCriteria;
use App\Models\Clinical\GenomicUpload;
use App\Models\Clinical\GenomicVariant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
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
        // Distinct gene/drug pairs — factory()->count(3) can collide on the
        // (gene, variant_pattern, drug) unique index when randomElement repeats.
        GeneDrugInteraction::factory()->create(['gene' => 'BRAF', 'drug' => 'Vemurafenib']);
        GeneDrugInteraction::factory()->create(['gene' => 'EGFR', 'drug' => 'Erlotinib']);
        GeneDrugInteraction::factory()->create(['gene' => 'KRAS', 'drug' => 'Sotorasib']);

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

// ── Genomics uploads ─────────────────────────────────────────────────────

describe('Genomics uploads', function () {
    it('storeUpload stores file on disk and creates DB record', function () {
        fakeIsolatedLocalDisk('genomic-upload-store');

        $file = UploadedFile::fake()->create('sample.vcf', 1024);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => $file,
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
                'sample_id' => 'SAMPLE-001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.file_format', 'vcf')
            ->assertJsonPath('data.genome_build', 'GRCh38')
            ->assertJsonPath('data.sample_id', 'SAMPLE-001')
            ->assertJsonPath('data.status', 'uploaded');

        $this->assertDatabaseHas('clinical.genomic_uploads', [
            'file_format' => 'vcf',
            'sample_id' => 'SAMPLE-001',
            'uploaded_by' => $this->user->id,
        ]);

        Storage::disk('local')->assertExists($response->json('data.stored_path'));
    });

    it('storeUpload validates required fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', []);

        $response->assertStatus(422);
    });

    it('listUploads returns persisted uploads with pagination', function () {
        GenomicUpload::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/uploads');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'meta']);

        expect(count($response->json('data')))->toBe(3);
    });

    it('listUploads filters by status', function () {
        GenomicUpload::factory()->count(2)->create(['status' => 'uploaded']);
        GenomicUpload::factory()->count(1)->create(['status' => 'completed']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/uploads?status=uploaded');

        $response->assertStatus(200);
        expect(count($response->json('data')))->toBe(2);
    });

    it('showUpload returns the specific upload record', function () {
        $upload = GenomicUpload::factory()->create(['file_format' => 'vcf']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/genomics/uploads/{$upload->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $upload->id)
            ->assertJsonPath('data.file_format', 'vcf');
    });

    it('showUpload returns 404 for non-existent', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/uploads/99999');

        $response->assertStatus(404);
    });

    it('destroyUpload removes record and file', function () {
        fakeIsolatedLocalDisk('genomic-upload-destroy');

        $path = 'genomic-uploads/test-file.vcf';
        Storage::disk('local')->put($path, 'fake content');
        $upload = GenomicUpload::factory()->create(['stored_path' => $path]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/genomics/uploads/{$upload->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clinical.genomic_uploads', ['id' => $upload->id]);
        Storage::disk('local')->assertMissing($path);
    });

    it('destroyUpload returns 404 for non-existent', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/genomics/uploads/99999');

        $response->assertStatus(404);
    });
});

// ── Genomics criteria ─────────────────────────────────────────────────────

describe('Genomics criteria', function () {
    it('storeCriterion creates a DB record', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/criteria', [
                'name' => 'Test Criterion',
                'criteria_type' => 'variant',
                'criteria_definition' => ['gene' => 'BRAF'],
                'description' => 'A test criterion',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Criterion')
            ->assertJsonPath('data.criteria_type', 'variant');

        $this->assertDatabaseHas('clinical.genomic_criteria', [
            'name' => 'Test Criterion',
            'criteria_type' => 'variant',
            'created_by' => $this->user->id,
        ]);
    });

    it('storeCriterion validates required fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/criteria', []);

        $response->assertStatus(422);
    });

    it('listCriteria returns persisted criteria', function () {
        GenomicCriteria::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/criteria');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        expect(count($response->json('data')))->toBe(3);
    });

    it('updateCriterion updates existing record', function () {
        $criterion = GenomicCriteria::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/genomics/criteria/{$criterion->id}", [
                'name' => 'Updated Criterion',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Criterion');

        $this->assertDatabaseHas('clinical.genomic_criteria', [
            'id' => $criterion->id,
            'name' => 'Updated Criterion',
        ]);
    });

    it('updateCriterion returns 404 for non-existent', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/genomics/criteria/99999', [
                'name' => 'Nope',
            ]);

        $response->assertStatus(404);
    });

    it('destroyCriterion deletes record', function () {
        $criterion = GenomicCriteria::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/genomics/criteria/{$criterion->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clinical.genomic_criteria', ['id' => $criterion->id]);
    });

    it('destroyCriterion returns 404 for non-existent', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/genomics/criteria/99999');

        $response->assertStatus(404);
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
