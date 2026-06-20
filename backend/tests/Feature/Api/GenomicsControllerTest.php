<?php

use App\Jobs\Genomics\ProcessGenomicUploadJob;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\GenomicCriteria;
use App\Models\Clinical\GenomicUpload;
use App\Models\Clinical\GenomicUploadVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\PatientIdentifier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

function auroraGenomicVcf(string $sampleId = 'SAMPLE-001', string $info = 'GENEINFO=BRAF:673;AF=0.33'): string
{
    return implode("\n", [
        '##fileformat=VCFv4.2',
        '#CHROM	POS	ID	REF	ALT	QUAL	FILTER	INFO	FORMAT	'.$sampleId,
        '7	140453136	rs113488022	A	T	99	PASS	'.$info.'	GT:AD:DP	0/1:20,10:30',
        '',
    ]);
}

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

describe('GET /api/genomics/patients/{patient}/fhir-report', function () {
    it('exports a FHIR Genomics report bundle for a patient', function () {
        $patient = ClinicalPatient::factory()->create([
            'mrn' => 'MRN-FHIR-REPORT-01',
            'first_name' => 'FHIR',
            'last_name' => 'Patient',
            'sex' => 'female',
        ]);

        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'BRAF',
            'variant' => 'V600E',
            'clinical_significance' => 'pathogenic',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/genomics/patients/{$patient->id}/fhir-report");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.standard', 'FHIR R4')
            ->assertJsonPath('meta.scope', 'local_export')
            ->assertJsonPath('meta.variant_count', 1)
            ->assertJsonPath('data.resourceType', 'Bundle')
            ->assertJsonPath('data.type', 'collection')
            ->assertJsonPath('data.entry.0.resource.resourceType', 'Patient')
            ->assertJsonPath('data.entry.1.resource.resourceType', 'DiagnosticReport')
            ->assertJsonPath('data.entry.1.resource.meta.profile.0', \App\Services\Genomics\FhirGenomicsReportExporter::GENOMIC_REPORT_PROFILE)
            ->assertJsonPath('data.entry.2.resource.resourceType', 'Observation')
            ->assertJsonPath('data.entry.2.resource.meta.profile.0', \App\Services\Genomics\FhirGenomicsReportExporter::VARIANT_PROFILE);
    });

    it('returns 404 for a missing patient', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/genomics/patients/99999/fhir-report')
            ->assertStatus(404);
    });

    it('requires authentication', function () {
        $this->getJson('/api/genomics/patients/1/fhir-report')
            ->assertStatus(401);
    });
});

// ── Genomics uploads ─────────────────────────────────────────────────────

describe('Genomics uploads', function () {
    it('storeUpload stores file on disk and creates DB record', function () {
        fakeIsolatedLocalDisk('genomic-upload-store');
        Queue::fake();

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
            ->assertJsonPath('data.status', 'parsing');

        $this->assertDatabaseHas('clinical.genomic_uploads', [
            'file_format' => 'vcf',
            'sample_id' => 'SAMPLE-001',
            'uploaded_by' => $this->user->id,
            'status' => 'parsing',
        ]);

        Storage::disk('local')->assertExists($response->json('data.stored_path'));
        Queue::assertPushed(ProcessGenomicUploadJob::class, fn ($job) => $job->uploadId === $response->json('data.id') && $job->queue === 'genomics');
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

    it('parses VCF uploads into staged variants and deterministically matches sample identifiers', function () {
        fakeIsolatedLocalDisk('genomic-upload-vcf-parse');

        $patient = ClinicalPatient::factory()->create(['mrn' => 'MRN-GEN-001']);
        PatientIdentifier::create([
            'patient_id' => $patient->id,
            'identifier_type' => 'sample_id',
            'identifier_value' => 'SAMPLE-VCF-001',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('sample.vcf', auroraGenomicVcf('SAMPLE-VCF-001')),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'mapped')
            ->assertJsonPath('data.total_variants', 1)
            ->assertJsonPath('data.mapped_variants', 1)
            ->assertJsonPath('data.review_required', 0);

        $this->assertDatabaseHas('clinical.genomic_upload_variants', [
            'genomic_upload_id' => $response->json('data.id'),
            'patient_id' => $patient->id,
            'sample_id' => 'SAMPLE-VCF-001',
            'mapping_status' => 'matched',
            'chromosome' => '7',
            'position' => 140453136,
            'reference_allele' => 'A',
            'alternate_allele' => 'T',
            'gene_symbol' => 'BRAF',
        ]);
    });

    it('imports matched staged variants idempotently into clinical genomic variants', function () {
        fakeIsolatedLocalDisk('genomic-upload-import');

        $patient = ClinicalPatient::factory()->create(['mrn' => 'SAMPLE-IMPORT-001']);

        $uploadResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('sample.vcf', auroraGenomicVcf('SAMPLE-IMPORT-001')),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $uploadId = $uploadResponse->json('data.id');

        $firstImport = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$uploadId}/import");

        $firstImport->assertStatus(200)
            ->assertJsonPath('data.operation.status', 'succeeded')
            ->assertJsonPath('data.result.created', 1)
            ->assertJsonPath('data.result.written', 1)
            ->assertJsonPath('data.upload.status', 'imported');

        $secondImport = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$uploadId}/import");

        $secondImport->assertStatus(200)
            ->assertJsonPath('data.result.created', 0)
            ->assertJsonPath('data.result.updated', 1);

        expect(GenomicVariant::where('source_type', 'upload')
            ->where('source_id', (string) $uploadId)
            ->count())->toBe(1);

        $this->assertDatabaseHas('clinical.genomic_variants', [
            'patient_id' => $patient->id,
            'source_type' => 'upload',
            'source_id' => (string) $uploadId,
            'gene' => 'BRAF',
            'chromosome' => '7',
            'position' => 140453136,
            'ref_allele' => 'A',
            'alt_allele' => 'T',
        ]);
    });

    it('blocks import when staged variants have unmatched samples', function () {
        fakeIsolatedLocalDisk('genomic-upload-unmatched');

        $uploadResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('sample.vcf', auroraGenomicVcf('UNKNOWN-SAMPLE')),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $uploadResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'review')
            ->assertJsonPath('data.review_required', 1);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads/'.$uploadResponse->json('data.id').'/import')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Upload has unmatched or review-required variants');
    });

    it('returns 422 when person matching is requested without parsed variants', function () {
        $upload = GenomicUpload::factory()->create(['status' => 'review']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$upload->id}/match-persons")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No parsed variants are available for person matching');
    });

    it('marks malformed VCF uploads failed instead of reporting false success', function () {
        fakeIsolatedLocalDisk('genomic-upload-malformed');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('malformed.vcf', "7\t140453136\t.\tA\tT\n"),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_message', 'No importable variants were parsed from the upload.');
    });

    it('deduplicates repeated VCF rows while staging upload variants', function () {
        fakeIsolatedLocalDisk('genomic-upload-duplicates');

        ClinicalPatient::factory()->create(['mrn' => 'SAMPLE-DUP-001']);
        $content = auroraGenomicVcf('SAMPLE-DUP-001')."7\t140453136\trs113488022\tA\tT\t99\tPASS\tGENEINFO=BRAF:673;AF=0.33\tGT:AD:DP\t0/1:20,10:30\n";

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('dupe.vcf', $content),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_variants', 1);

        expect(GenomicUploadVariant::where('genomic_upload_id', $response->json('data.id'))->count())->toBe(1);
    });

    it('parses CSV uploads and imports matched variants', function () {
        fakeIsolatedLocalDisk('genomic-upload-csv');

        $patient = ClinicalPatient::factory()->create(['mrn' => 'CSV-SAMPLE-001']);
        $csv = implode("\n", [
            'sample_id,gene,chromosome,position,ref,alt,allele_frequency',
            'CSV-SAMPLE-001,TP53,17,7674220,C,T,0.42',
            '',
        ]);

        $uploadResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('variants.csv', $csv),
                'file_format' => 'csv',
                'genome_build' => 'GRCh38',
            ]);

        $uploadResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'mapped')
            ->assertJsonPath('data.total_variants', 1);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads/'.$uploadResponse->json('data.id').'/import')
            ->assertStatus(200)
            ->assertJsonPath('data.result.written', 1);

        $this->assertDatabaseHas('clinical.genomic_variants', [
            'patient_id' => $patient->id,
            'gene' => 'TP53',
            'chromosome' => '17',
            'position' => 7674220,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
        ]);
    });

    it('annotates imported upload variants from the local ClinVar cache', function () {
        fakeIsolatedLocalDisk('genomic-upload-clinvar');

        ClinicalPatient::factory()->create(['mrn' => 'SAMPLE-CLINVAR-001']);
        ClinVarVariant::create([
            'variation_id' => '113488022',
            'chromosome' => '7',
            'position' => 140453136,
            'reference_allele' => 'A',
            'alternate_allele' => 'T',
            'genome_build' => 'GRCh38',
            'gene_symbol' => 'BRAF',
            'clinical_significance' => 'Pathogenic',
            'disease_name' => 'Melanoma',
            'review_status' => 'reviewed by expert panel',
            'is_pathogenic' => true,
        ]);

        $uploadResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('sample.vcf', auroraGenomicVcf('SAMPLE-CLINVAR-001', 'GENEINFO=BRAF:673')),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $uploadId = $uploadResponse->json('data.id');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$uploadId}/import")
            ->assertStatus(200);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$uploadId}/annotate-clinvar");

        $response->assertStatus(200)
            ->assertJsonPath('data.operation.performed', true)
            ->assertJsonPath('data.result.annotated', 1)
            ->assertJsonPath('data.result.missing_reference', 0);

        $this->assertDatabaseHas('clinical.genomic_variants', [
            'source_type' => 'upload',
            'source_id' => (string) $uploadId,
            'clinical_significance' => 'Pathogenic',
            'clinvar_disease' => 'Melanoma',
            'clinvar_review_status' => 'reviewed by expert panel',
        ]);
    });

    it('requires a populated ClinVar cache before upload annotation', function () {
        fakeIsolatedLocalDisk('genomic-upload-clinvar-empty');

        ClinicalPatient::factory()->create(['mrn' => 'SAMPLE-NO-CLINVAR-001']);

        $uploadResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/genomics/uploads', [
                'file' => UploadedFile::fake()->createWithContent('sample.vcf', auroraGenomicVcf('SAMPLE-NO-CLINVAR-001', 'GENEINFO=BRAF:673')),
                'file_format' => 'vcf',
                'genome_build' => 'GRCh38',
            ]);

        $uploadId = $uploadResponse->json('data.id');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$uploadId}/import")
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/genomics/uploads/{$uploadId}/annotate-clinvar")
            ->assertStatus(503)
            ->assertJsonPath('message', 'ClinVar cache is empty; sync ClinVar before annotating uploads');
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
