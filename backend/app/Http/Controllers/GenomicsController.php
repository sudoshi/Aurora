<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\GenomicVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenomicsController extends Controller
{
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
            'uploads_count' => 0,
            'pathogenic_count' => $pathogenic,
            'vus_count' => $vus,
        ], 'Genomics stats retrieved');
    }

    // ── Uploads (stubs) ─────────────────────────────────────────────────

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

        return response()->json([
            'success' => true,
            'message' => 'Uploads retrieved',
            'data' => [],
            'meta' => [
                'total' => 0,
                'page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('per_page', 25),
                'last_page' => 1,
            ],
        ]);
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

        $stub = [
            'id' => 1,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'file_format' => $request->input('file_format'),
            'genome_build' => $request->input('genome_build', 'GRCh38'),
            'sample_id' => $request->input('sample_id'),
            'status' => 'uploaded',
            'total_variants' => 0,
            'mapped_variants' => 0,
            'unmapped_variants' => 0,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        return ApiResponse::success($stub, 'Upload created', 201);
    }

    /**
     * GET /api/genomics/uploads/{id}
     */
    public function showUpload(int $id): JsonResponse
    {
        $stub = [
            'id' => $id,
            'original_filename' => 'stub.vcf',
            'file_format' => 'vcf',
            'genome_build' => 'GRCh38',
            'sample_id' => null,
            'status' => 'uploaded',
            'total_variants' => 0,
            'mapped_variants' => 0,
            'unmapped_variants' => 0,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        return ApiResponse::success($stub, 'Upload retrieved');
    }

    /**
     * DELETE /api/genomics/uploads/{id}
     */
    public function destroyUpload(int $id): JsonResponse
    {
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

    // ── Cohort Criteria (stubs) ─────────────────────────────────────────

    /**
     * GET /api/genomics/criteria
     */
    public function listCriteria(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Criteria retrieved');
    }

    /**
     * POST /api/genomics/criteria
     */
    public function storeCriterion(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'criteria_type' => 'required|string',
            'criteria_definition' => 'required|array',
            'description' => 'sometimes|string|max:1000',
            'is_shared' => 'sometimes|boolean',
        ]);

        $stub = [
            'id' => 1,
            'name' => $request->input('name'),
            'criteria_type' => $request->input('criteria_type'),
            'criteria_definition' => $request->input('criteria_definition'),
            'description' => $request->input('description'),
            'is_shared' => $request->boolean('is_shared', false),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        return ApiResponse::success($stub, 'Criterion created', 201);
    }

    /**
     * PUT /api/genomics/criteria/{id}
     */
    public function updateCriterion(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'criteria_type' => 'sometimes|string',
            'criteria_definition' => 'sometimes|array',
            'description' => 'sometimes|string|max:1000',
            'is_shared' => 'sometimes|boolean',
        ]);

        $stub = [
            'id' => $id,
            'name' => $request->input('name', 'Updated criterion'),
            'criteria_type' => $request->input('criteria_type', 'variant'),
            'criteria_definition' => $request->input('criteria_definition', []),
            'description' => $request->input('description'),
            'is_shared' => $request->boolean('is_shared', false),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        return ApiResponse::success($stub, 'Criterion updated');
    }

    /**
     * DELETE /api/genomics/criteria/{id}
     */
    public function destroyCriterion(int $id): JsonResponse
    {
        return ApiResponse::success(null, 'Criterion deleted');
    }

    // ── ClinVar (stubs) ─────────────────────────────────────────────────

    /**
     * GET /api/genomics/clinvar/status
     */
    public function clinvarStatus(): JsonResponse
    {
        return ApiResponse::success([
            'last_sync' => null,
            'total_entries' => 0,
            'status' => 'not_configured',
        ], 'ClinVar status retrieved');
    }

    /**
     * GET /api/genomics/clinvar/search
     */
    public function clinvarSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'sometimes|string|max:255',
            'gene' => 'sometimes|string|max:50',
            'significance' => 'sometimes|string|max:100',
            'pathogenic_only' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ClinVar search results',
            'data' => [],
            'meta' => [
                'total' => 0,
                'page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('per_page', 25),
                'last_page' => 1,
            ],
        ]);
    }

    /**
     * POST /api/genomics/clinvar/sync
     */
    public function clinvarSync(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'inserted' => 0,
            'updated' => 0,
            'errors' => 0,
            'log_id' => 0,
        ], 'ClinVar sync complete');
    }
}
