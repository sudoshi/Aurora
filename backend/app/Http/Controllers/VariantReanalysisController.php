<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\AcknowledgeKbAlertRequest;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Services\Genomics\Reanalysis\VariantCanonicalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariantReanalysisController extends Controller
{
    public function __construct(private VariantCanonicalizer $canonicalizer) {}

    public function canonicalize(int $variant): JsonResponse
    {
        $model = GenomicVariant::findOrFail($variant);

        return ApiResponse::success($this->canonicalizer->canonicalize($model));
    }

    public function patientAlerts(Request $request, int $patient): JsonResponse
    {
        $query = KbChangeAlert::where('patient_id', $patient)->with('task:id,title,status');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ApiResponse::success($query->orderByDesc('created_at')->get());
    }

    public function worklist(Request $request): JsonResponse
    {
        $query = KbChangeAlert::query()->with('variant:id,gene,patient_id')->orderByDesc('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        return ApiResponse::paginated($query->paginate($perPage));
    }

    public function acknowledge(AcknowledgeKbAlertRequest $request, int $alert): JsonResponse
    {
        $model = KbChangeAlert::findOrFail($alert);
        $model->update([
            'status' => $request->validated()['status'],
            'resolution_note' => $request->validated()['resolution_note'] ?? null,
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);

        return ApiResponse::success($model);
    }
}
