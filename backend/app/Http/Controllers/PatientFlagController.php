<?php
namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StorePatientFlagRequest;
use App\Models\Clinical\ClinicalPatient;
use App\Models\PatientFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientFlagController extends Controller
{
    public function index(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $query = $patientModel->flags()->with(['flagger:id,name', 'resolver:id,name']);

        if ($request->has('domain')) {
            $query->forDomain($request->domain);
        }

        if ($request->has('resolved')) {
            if ($request->boolean('resolved')) {
                $query->whereNotNull('resolved_at');
            } else {
                $query->unresolved();
            }
        }

        $flags = $query->orderByDesc('created_at')->get();

        return ApiResponse::success($flags);
    }

    public function store(StorePatientFlagRequest $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $flag = $patientModel->flags()->create([
            ...$request->validated(),
            'flagged_by' => $request->user()->id,
        ]);

        $flag->load('flagger:id,name');

        return ApiResponse::success($flag, 'Created', 201);
    }

    public function update(Request $request, int $flag): JsonResponse
    {
        $flag = PatientFlag::findOrFail($flag);

        $validated = $request->validate([
            'severity' => 'sometimes|string|in:critical,attention,informational',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        // Handle resolve action
        if ($request->boolean('resolve')) {
            $validated['resolved_at'] = now();
            $validated['resolved_by'] = $request->user()->id;
        }

        $flag->update($validated);
        $flag->load(['flagger:id,name', 'resolver:id,name']);

        return ApiResponse::success($flag);
    }

    public function destroy(Request $request, int $flag): JsonResponse
    {
        $flag = PatientFlag::findOrFail($flag);

        // Authorization: only creator or admin can delete
        if ($flag->flagged_by !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $flag->delete();

        return ApiResponse::success(null, 'Deleted', 200);
    }
}
