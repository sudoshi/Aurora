<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Jobs\Genomics\ProcessGenomicUploadJob;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ClinVarSyncLog;
use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicCriteria;
use App\Models\Clinical\GenomicUpload;
use App\Models\Clinical\GenomicVariant;
use App\Services\Genomics\ClinVarSyncService;
use App\Services\Genomics\FhirGenomicsReportExporter;
use App\Services\Genomics\GenomicUploadIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GenomicsController extends Controller
{
    public function __construct(
        private readonly ClinVarSyncService $clinVarSync,
        private readonly GenomicUploadIngestionService $uploadIngestion,
        private readonly FhirGenomicsReportExporter $fhirReportExporter,
    ) {}
    // ── Stats ────────────────────────────────────────────────────────────

    /**
     * GET /api/genomics/stats
     */
    public function stats(): JsonResponse
    {
        $total = GenomicVariant::count();
        $pathogenic = GenomicVariant::whereRaw("LOWER(clinical_significance) IN ('pathogenic', 'likely_pathogenic')")->count();
        $vus = GenomicVariant::whereRaw("LOWER(clinical_significance) IN ('vus', 'uncertain significance')")->count();
        $uploadsByStatus = [];
        GenomicUpload::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->each(function ($row) use (&$uploadsByStatus): void {
                $status = $this->normalizeUploadStatus((string) $row->status);
                $uploadsByStatus[$status] = ($uploadsByStatus[$status] ?? 0) + (int) $row->aggregate;
            });
        $topGenes = GenomicVariant::query()
            ->whereNotNull('gene')
            ->selectRaw('gene, COUNT(*) as aggregate')
            ->groupBy('gene')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->pluck('aggregate', 'gene')
            ->map(fn ($count) => (int) $count)
            ->all();

        return ApiResponse::success([
            'total_variants' => $total,
            'total_uploads' => GenomicUpload::count(),
            'uploads_count' => GenomicUpload::count(),
            'pathogenic_count' => $pathogenic,
            'vus_count' => $vus,
            'mapped_variants' => (int) GenomicUpload::sum('mapped_variants'),
            'review_required' => (int) GenomicUpload::sum('unmapped_variants'),
            'uploads_by_status' => $uploadsByStatus,
            'top_genes' => $topGenes,
        ], 'Genomics stats retrieved');
    }

    // ── Uploads ───────────────────────────────────────────────────────────

    /**
     * GET /api/genomics/uploads
     */
    public function listUploads(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $query = GenomicUpload::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $paginator = $query->orderBy('id', 'desc')
            ->paginate($perPage)
            ->through(fn (GenomicUpload $upload) => $this->formatUpload($upload));

        return ApiResponse::paginated($paginator, 'Uploads retrieved');
    }

    /**
     * POST /api/genomics/uploads
     */
    public function storeUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'file_format' => 'required|string|in:vcf,maf,cbio_maf,fhir_genomics,csv,tsv',
            'genome_build' => 'sometimes|string',
            'sample_id' => 'sometimes|string',
        ]);

        $file = $request->file('file');
        $storedPath = $file->store('genomic-uploads', 'local');

        $upload = GenomicUpload::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_format' => $request->input('file_format'),
            'genome_build' => $request->input('genome_build', 'GRCh38'),
            'sample_id' => $request->input('sample_id'),
            'status' => 'parsing',
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'last_result' => [
                'operation' => 'parse_upload',
                'status' => 'queued',
            ],
        ]);

        ProcessGenomicUploadJob::dispatch($upload->id)->onQueue('genomics');

        return ApiResponse::success($this->formatUpload($upload->refresh()), 'Upload created and parsing queued', 201);
    }

    /**
     * GET /api/genomics/uploads/{id}
     */
    public function showUpload(int $id): JsonResponse
    {
        $upload = GenomicUpload::find($id);

        if (! $upload) {
            return ApiResponse::error('Upload not found', 404);
        }

        return ApiResponse::success($this->formatUpload($upload), 'Upload retrieved');
    }

    /**
     * DELETE /api/genomics/uploads/{id}
     */
    public function destroyUpload(int $id): JsonResponse
    {
        $upload = GenomicUpload::find($id);

        if (! $upload) {
            return ApiResponse::error('Upload not found', 404);
        }

        Storage::disk('local')->delete($upload->stored_path);
        $upload->delete();

        return ApiResponse::success(null, 'Upload deleted');
    }

    /**
     * POST /api/genomics/uploads/{id}/match-persons
     */
    public function matchPersons(int $id): JsonResponse
    {
        $upload = GenomicUpload::find($id);

        if (! $upload) {
            return ApiResponse::error('Upload not found', 404);
        }

        if (in_array($this->normalizeUploadStatus($upload->status), ['pending', 'parsing'], true)) {
            return ApiResponse::error('Upload parsing is still in progress', 409);
        }

        if ($upload->stagedVariants()->count() === 0) {
            return ApiResponse::error('No parsed variants are available for person matching', 422);
        }

        $result = $this->uploadIngestion->matchPersons($upload);

        return ApiResponse::success([
            'operation' => [
                'name' => 'match_persons',
                'status' => 'succeeded',
                'performed' => true,
            ],
            'upload' => $this->formatUpload($upload->refresh()),
            'result' => $result,
        ], 'Person matching complete');
    }

    /**
     * POST /api/genomics/uploads/{id}/import
     */
    public function importToOmop(int $id): JsonResponse
    {
        $upload = GenomicUpload::find($id);

        if (! $upload) {
            return ApiResponse::error('Upload not found', 404);
        }

        if (in_array($this->normalizeUploadStatus($upload->status), ['pending', 'parsing'], true)) {
            return ApiResponse::error('Upload parsing is still in progress', 409);
        }

        $stagedCount = $upload->stagedVariants()->count();
        if ($stagedCount === 0) {
            return ApiResponse::error('No parsed variants are available for import', 422);
        }

        $reviewRequired = $upload->stagedVariants()
            ->where(function ($query) {
                $query->whereNull('patient_id')
                    ->orWhereNotIn('mapping_status', ['matched', 'imported']);
            })
            ->count();

        if ($reviewRequired > 0) {
            return ApiResponse::error('Upload has unmatched or review-required variants', 409, [
                'review_required' => $reviewRequired,
                'matched' => $upload->stagedVariants()->whereNotNull('patient_id')->count(),
            ]);
        }

        $result = $this->uploadIngestion->importUpload($upload);

        return ApiResponse::success([
            'operation' => [
                'name' => 'import_to_omop',
                'status' => $result['errors'] === [] ? 'succeeded' : 'completed_with_errors',
                'performed' => ($result['created'] + $result['updated']) > 0,
            ],
            'upload' => $this->formatUpload($upload->refresh()),
            'result' => array_merge($result, [
                'written' => $result['created'] + $result['updated'],
            ]),
        ], 'Import complete');
    }

    /**
     * POST /api/genomics/uploads/{id}/annotate-clinvar
     */
    public function annotateClinVar(int $id): JsonResponse
    {
        $upload = GenomicUpload::find($id);

        if (! $upload) {
            return ApiResponse::error('Upload not found', 404);
        }

        if (ClinVarVariant::count() === 0) {
            return ApiResponse::error('ClinVar cache is empty; sync ClinVar before annotating uploads', 503);
        }

        $eligible = GenomicVariant::where('source_type', 'upload')
            ->where('source_id', (string) $upload->id)
            ->count();

        if ($eligible === 0) {
            return ApiResponse::error('No imported variants are available for ClinVar annotation', 422);
        }

        $result = $this->uploadIngestion->annotateClinVar($upload);
        $performed = $result['annotated'] > 0;

        return ApiResponse::success([
            'operation' => [
                'name' => 'annotate_clinvar',
                'status' => 'succeeded',
                'performed' => $performed,
            ],
            'upload' => $this->formatUpload($upload->refresh()),
            'result' => array_merge($result, [
                'skipped' => $result['already_annotated'] + $result['missing_reference'],
            ]),
        ], $performed ? 'ClinVar annotation complete' : 'No variants required annotation');
    }

    // ── Variants ────────────────────────────────────────────────────────

    /**
     * GET /api/genomics/variants
     */
    public function listVariants(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id' => 'sometimes|integer',
            'person_id' => 'sometimes|integer',
            'gene' => 'sometimes|string|max:50',
            'clinvar_significance' => 'sometimes|string|max:100',
            'mapping_status' => 'sometimes|string|max:50',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $query = GenomicVariant::query();

        if ($request->filled('upload_id')) {
            $query->where('source_id', $request->input('upload_id'))
                ->where('source_type', 'upload');
        }

        if ($request->filled('person_id')) {
            $query->where('patient_id', $request->input('person_id'));
        }

        if ($request->filled('gene')) {
            $query->where('gene', 'ilike', $request->input('gene'));
        }

        if ($request->filled('clinvar_significance')) {
            $query->where('clinical_significance', $request->input('clinvar_significance'));
        }

        if ($request->filled('mapping_status')) {
            if ($request->input('mapping_status') === 'mapped') {
                $query->whereNotNull('patient_id');
            } elseif ($request->input('mapping_status') === 'unmapped') {
                $query->whereNull('patient_id');
            }
        }

        $perPage = (int) $request->input('per_page', 25);
        $paginator = $query->orderBy('id', 'desc')
            ->paginate($perPage)
            ->through(fn (GenomicVariant $variant) => $this->formatVariant($variant));

        return ApiResponse::paginated($paginator, 'Variants retrieved');
    }

    /**
     * GET /api/genomics/variants/{id}
     */
    public function showVariant(int $id): JsonResponse
    {
        $variant = GenomicVariant::find($id);

        if (! $variant) {
            return ApiResponse::error('Variant not found', 404);
        }

        return ApiResponse::success($this->formatVariant($variant), 'Variant retrieved');
    }

    /**
     * GET /api/genomics/patients/{patient}/fhir-report
     */
    public function fhirReport(int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (! $patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $bundle = $this->fhirReportExporter->exportForPatient($patientModel);

        return ApiResponse::success($bundle, 'FHIR Genomics report exported', 200, [
            'standard' => 'FHIR R4',
            'implementation_guide' => 'HL7 FHIR Genomics Reporting',
            'profile' => FhirGenomicsReportExporter::GENOMIC_REPORT_PROFILE,
            'variant_profile' => FhirGenomicsReportExporter::VARIANT_PROFILE,
            'variant_count' => $this->fhirReportExporter->variantCountForPatient($patientModel),
            'scope' => 'local_export',
        ]);
    }

    // ── Cohort Criteria ─────────────────────────────────────────────────

    /**
     * GET /api/genomics/criteria
     */
    public function listCriteria(Request $request): JsonResponse
    {
        return ApiResponse::success(GenomicCriteria::all(), 'Criteria retrieved');
    }

    /**
     * POST /api/genomics/criteria
     */
    public function storeCriterion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'criteria_type' => 'required|string',
            'criteria_definition' => 'required|array',
            'description' => 'sometimes|string|max:1000',
            'is_shared' => 'sometimes|boolean',
        ]);

        $criterion = GenomicCriteria::create(array_merge($validated, [
            'created_by' => auth()->id(),
        ]));

        return ApiResponse::success($criterion, 'Criterion created', 201);
    }

    /**
     * PUT /api/genomics/criteria/{id}
     */
    public function updateCriterion(Request $request, int $id): JsonResponse
    {
        $criterion = GenomicCriteria::find($id);

        if (! $criterion) {
            return ApiResponse::error('Criterion not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'criteria_type' => 'sometimes|string',
            'criteria_definition' => 'sometimes|array',
            'description' => 'sometimes|string|max:1000',
            'is_shared' => 'sometimes|boolean',
        ]);

        $criterion->update($validated);

        return ApiResponse::success($criterion, 'Criterion updated');
    }

    /**
     * DELETE /api/genomics/criteria/{id}
     */
    public function destroyCriterion(int $id): JsonResponse
    {
        $criterion = GenomicCriteria::find($id);

        if (! $criterion) {
            return ApiResponse::error('Criterion not found', 404);
        }

        $criterion->delete();

        return ApiResponse::success(null, 'Criterion deleted');
    }

    // ── ClinVar ──────────────────────────────────────────────────────────

    /**
     * GET /api/genomics/clinvar/status
     */
    public function clinvarStatus(): JsonResponse
    {
        $latestSync = ClinVarSyncLog::where('status', 'completed')
            ->orderByDesc('finished_at')
            ->first();

        return response()->json([
            'data' => [
                'total_variants' => ClinVarVariant::count(),
                'pathogenic_count' => ClinVarVariant::where('is_pathogenic', true)->count(),
                'last_sync' => $latestSync?->finished_at,
                'last_sync_build' => $latestSync?->genome_build,
                'last_sync_papu' => $latestSync?->papu_only,
                'syncs' => ClinVarSyncLog::orderByDesc('created_at')->limit(5)->get(),
            ],
        ]);
    }

    /**
     * GET /api/genomics/clinvar/search
     */
    public function clinvarSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:200',
            'gene' => 'nullable|string|max:100',
            'significance' => 'nullable|string|max:100',
            'pathogenic_only' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $query = ClinVarVariant::query();

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('gene_symbol', 'ilike', $term)
                    ->orWhere('hgvs', 'ilike', $term)
                    ->orWhere('disease_name', 'ilike', $term)
                    ->orWhere('variation_id', 'ilike', $term)
                    ->orWhere('rs_id', 'ilike', $term);
            });
        }

        if ($request->filled('gene')) {
            $query->where('gene_symbol', 'ilike', $request->string('gene').'%');
        }

        if ($request->filled('significance')) {
            $query->where('clinical_significance', 'ilike', '%'.$request->string('significance').'%');
        }

        if ($request->boolean('pathogenic_only')) {
            $query->where('is_pathogenic', true);
        }

        $results = $query->orderBy('gene_symbol')
            ->orderByDesc('is_pathogenic')
            ->paginate($request->integer('per_page', 50));

        return response()->json($results);
    }

    /**
     * POST /api/genomics/clinvar/sync
     */
    public function clinvarSync(Request $request): JsonResponse
    {
        $request->validate([
            'papu_only' => 'nullable|boolean',
        ]);

        $papuOnly = $request->boolean('papu_only', false);

        try {
            $result = $this->clinVarSync->sync($papuOnly);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sync failed: '.$e->getMessage()], 500);
        }

        return response()->json(['data' => $result]);
    }

    /**
     * GET /api/genomics/interactions
     * Query gene-drug interactions from the evidence database.
     */
    public function interactions(Request $request): JsonResponse
    {
        $query = \App\Models\Clinical\GeneDrugInteraction::query();

        if ($gene = $request->input('gene')) {
            $query->where('gene', strtoupper($gene));
        }
        if ($evidenceLevel = $request->input('evidence_level')) {
            $query->where('evidence_level', $evidenceLevel);
        }
        if ($relationship = $request->input('relationship')) {
            $query->where('relationship', $relationship);
        }
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $interactions = $query->orderBy('gene')->orderBy('evidence_level')->get();

        return response()->json([
            'success' => true,
            'data' => $interactions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUpload(GenomicUpload $upload): array
    {
        $status = $this->normalizeUploadStatus($upload->status);

        return [
            'id' => $upload->id,
            'source_id' => $upload->id,
            'created_by' => $upload->uploaded_by,
            'uploaded_by' => $upload->uploaded_by,
            'filename' => $upload->original_filename,
            'original_filename' => $upload->original_filename,
            'stored_path' => $upload->stored_path,
            'file_format' => $upload->file_format,
            'file_size_bytes' => $upload->file_size,
            'file_size' => $upload->file_size,
            'status' => $status,
            'raw_status' => $upload->status,
            'genome_build' => $upload->genome_build,
            'sample_id' => $upload->sample_id,
            'total_variants' => (int) $upload->total_variants,
            'mapped_variants' => (int) $upload->mapped_variants,
            'unmapped_variants' => (int) $upload->unmapped_variants,
            'review_required' => (int) $upload->unmapped_variants,
            'error_message' => $upload->error_message,
            'last_result' => $upload->last_result,
            'parsed_at' => $upload->parsed_at?->toIso8601String(),
            'matched_at' => $upload->matched_at?->toIso8601String(),
            'imported_at' => $upload->imported_at?->toIso8601String(),
            'clinvar_annotated_at' => $upload->clinvar_annotated_at?->toIso8601String(),
            'created_at' => $upload->created_at?->toIso8601String(),
            'updated_at' => $upload->updated_at?->toIso8601String(),
        ];
    }

    private function normalizeUploadStatus(?string $status): string
    {
        return match ($status) {
            'uploaded', 'pending' => 'pending',
            'processing' => 'parsing',
            'completed' => 'mapped',
            'parsing', 'mapped', 'review', 'imported', 'failed' => $status,
            default => 'pending',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function formatVariant(GenomicVariant $variant): array
    {
        $uploadId = $variant->source_type === 'upload' && is_numeric($variant->source_id)
            ? (int) $variant->source_id
            : null;

        return [
            'id' => $variant->id,
            'upload_id' => $uploadId,
            'source_id' => $uploadId ?? $variant->source_id,
            'source_type' => $variant->source_type,
            'person_id' => $variant->patient_id,
            'patient_id' => $variant->patient_id,
            'sample_id' => null,
            'chromosome' => $variant->chromosome,
            'position' => $variant->position,
            'reference_allele' => $variant->ref_allele,
            'alternate_allele' => $variant->alt_allele,
            'ref_allele' => $variant->ref_allele,
            'alt_allele' => $variant->alt_allele,
            'genome_build' => null,
            'gene_symbol' => $variant->gene,
            'gene' => $variant->gene,
            'variant' => $variant->variant,
            'hgvs_c' => null,
            'hgvs_p' => null,
            'variant_type' => $variant->variant_type,
            'variant_class' => null,
            'consequence' => null,
            'quality' => null,
            'filter_status' => null,
            'zygosity' => $variant->zygosity,
            'allele_frequency' => $variant->allele_frequency === null ? null : (float) $variant->allele_frequency,
            'read_depth' => null,
            'clinvar_id' => null,
            'clinvar_significance' => $variant->clinical_significance,
            'clinical_significance' => $variant->clinical_significance,
            'clinvar_disease' => $variant->clinvar_disease,
            'clinvar_review_status' => $variant->clinvar_review_status,
            'cosmic_id' => null,
            'measurement_concept_id' => null,
            'mapping_status' => $variant->patient_id === null ? 'unmapped' : 'mapped',
            'created_at' => $variant->created_at?->toIso8601String(),
            'updated_at' => $variant->updated_at?->toIso8601String(),
        ];
    }
}
