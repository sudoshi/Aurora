<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\ClinicalPatient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService,
    ) {}

    /**
     * GET /api/patients/search?q={query}
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $results = $this->patientService->searchPatients(
            $request->input('q'),
            (int) $request->input('limit', 20),
        );

        return ApiResponse::success($results, 'Patients found');
    }

    /**
     * GET /api/patients/{patient}/profile
     */
    public function profile(int $patient): JsonResponse
    {
        $model = ClinicalPatient::find($patient);

        if (!$model) {
            return ApiResponse::error('Patient not found', 404);
        }

        $profile = $this->patientService->getProfile((string) $model->id);

        return ApiResponse::success($profile, 'Patient profile retrieved');
    }

    /**
     * GET /api/patients/{patient}/stats
     */
    public function stats(int $patient): JsonResponse
    {
        $model = ClinicalPatient::find($patient);

        if (!$model) {
            return ApiResponse::error('Patient not found', 404);
        }

        $stats = $this->patientService->getStats((string) $model->id);

        return ApiResponse::success($stats, 'Patient stats retrieved');
    }

    /**
     * POST /api/patients
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mrn' => 'required|string|max:100|unique:patients,mrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'sex' => 'nullable|string|max:20',
            'race' => 'nullable|string|max:100',
            'ethnicity' => 'nullable|string|max:100',
            'institution_id' => 'nullable|integer',
            'source_id' => 'nullable|string|max:255',
            'source_type' => 'nullable|string|max:255',
        ]);

        $patient = $this->patientService->createPatient($validated);

        return ApiResponse::success($patient->toArray(), 'Patient created', 201);
    }
}
