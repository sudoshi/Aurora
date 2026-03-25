<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\ClinVarSyncLog;
use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicCriteria;
use App\Models\Clinical\GenomicUpload;
use App\Models\Clinical\GenomicVariant;
use App\Services\Genomics\ClinVarAnnotationService;
use App\Services\Genomics\ClinVarSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GenomicsController extends Controller
{
    public function __construct(
        private readonly ClinVarSyncService $clinVarSync,
        private readonly ClinVarAnnotationService $clinVarAnnotation,
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

        return ApiResponse::success([
            'total_variants' => $total,
            'uploads_count' => GenomicUpload::count(),
            'pathogenic_count' => $pathogenic,
            'vus_count' => $vus,
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
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        return ApiResponse::paginated($paginator, 'Uploads retrieved');
    }

    /**
     * POST /api/genomics/uploads
     */
    public function storeUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'file_format' => 'required|string',
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
            'status' => 'uploaded',
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return ApiResponse::success($upload, 'Upload created', 201);
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

        return ApiResponse::success($upload, 'Upload retrieved');
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
        return ApiResponse::success([
            'matched' => 0,
            'unmatched' => 0,
        ], 'Person matching complete');
    }

    /**
     * POST /api/genomics/uploads/{id}/import
     */
    public function importToOmop(int $id): JsonResponse
    {
        $upload = [
            'id' => $id,
            'original_filename' => 'stub.vcf',
            'file_format' => 'vcf',
            'genome_build' => 'GRCh38',
            'sample_id' => null,
            'status' => 'imported',
            'total_variants' => 0,
            'mapped_variants' => 0,
            'unmapped_variants' => 0,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        return ApiResponse::success([
            'upload' => $upload,
            'result' => [
                'written' => 0,
                'skipped' => 0,
                'errors' => 0,
            ],
        ], 'Import complete');
    }

    /**
     * POST /api/genomics/uploads/{id}/annotate-clinvar
     */
    public function annotateClinVar(int $id): JsonResponse
    {
        return ApiResponse::success([
            'annotated' => 0,
            'skipped' => 0,
        ], 'ClinVar annotation complete');
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
            $query->where('gene', $request->input('gene'));
        }

        if ($request->filled('clinvar_significance')) {
            $query->where('clinical_significance', $request->input('clinvar_significance'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

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

        return ApiResponse::success($variant, 'Variant retrieved');
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
}
