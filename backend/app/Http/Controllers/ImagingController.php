<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Jobs\Imaging\ImportLocalDicomJob;
use App\Jobs\Imaging\IndexDicomwebStudiesJob;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingCriteria;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingStudy;
use App\Services\Imaging\ImagingAnalyticsService;
use App\Services\Imaging\ImagingIngestionService;
use App\Services\Imaging\ImagingMeasurementService;
use App\Services\Imaging\ImagingResponseAssessmentService;
use App\Services\Imaging\ImagingStudyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImagingController extends Controller
{
    public function __construct(
        private readonly ImagingIngestionService $ingestionService,
        private readonly ImagingStudyService $studyService,
        private readonly ImagingMeasurementService $measurementService,
        private readonly ImagingResponseAssessmentService $assessmentService,
        private readonly ImagingAnalyticsService $analyticsService,
    ) {}

    // =====================================================================
    //  1. GET /imaging/stats
    // =====================================================================

    public function stats(): JsonResponse
    {
        return ApiResponse::success($this->studyService->stats(), 'Imaging stats retrieved');
    }

    // =====================================================================
    //  2. GET /imaging/studies  (paginated, with modality/person_id filters)
    // =====================================================================

    public function studies(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 25)), 100);

        $mapped = $this->studyService->paginateStudies(
            $request->filled('modality') ? (string) $request->input('modality') : null,
            $request->filled('person_id') ? (int) $request->input('person_id') : null,
            $perPage,
        );

        return ApiResponse::paginated($mapped, 'Imaging studies retrieved');
    }

    // =====================================================================
    //  3. GET /imaging/studies/{id}
    // =====================================================================

    public function studyShow(int $id): JsonResponse
    {
        $data = $this->studyService->findStudyDetail($id);

        if ($data === null) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success($data, 'Imaging study details retrieved');
    }

    // =====================================================================
    //  4. POST /imaging/studies/index-from-dicomweb
    // =====================================================================

    public function indexFromDicomweb(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'sometimes|integer|min:1|max:500',
            'modality' => 'nullable|string|max:20',
            'patient_id' => 'nullable|string|max:255',
            'accession_number' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'index_series' => 'sometimes|boolean',
        ]);

        [$run, $created] = $this->ingestionService->createOrReuseRun(
            'dicomweb_index',
            $validated,
            $request->user()?->id,
        );

        if ($created) {
            IndexDicomwebStudiesJob::dispatch($run->id)->onQueue('imaging');
        }

        return ApiResponse::success(
            $this->ingestionService->runPayload($run),
            $created ? 'DICOMweb indexing queued' : 'Matching DICOMweb indexing run is already active',
            202
        );
    }

    // =====================================================================
    //  5. POST /imaging/studies/{id}/index-series
    // =====================================================================

    public function indexSeries(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $result = $this->studyService->indexSeries($study);

        if (! $result['ok']) {
            return ApiResponse::error(
                $result['message'],
                $result['status'],
                $result['extra'] ?? (isset($result['detail']) ? ['detail' => $result['detail']] : [])
            );
        }

        return ApiResponse::success($result['data'], 'Series indexed from Orthanc');
    }

    // =====================================================================
    //  6. POST /imaging/studies/{id}/extract-nlp
    // =====================================================================

    public function extractNlp(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $result = $this->studyService->extractNlp($study);

        if (! $result['ok']) {
            return ApiResponse::error(
                $result['message'],
                $result['status'],
                $result['extra'] ?? (isset($result['detail']) ? ['detail' => $result['detail']] : [])
            );
        }

        return ApiResponse::success($result['data'], $result['message']);
    }

    // =====================================================================
    //  7. GET /imaging/features
    // =====================================================================

    public function features(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 25)), 100);

        $mapped = $this->studyService->paginateFeatures(
            $request->filled('study_id') ? (int) $request->input('study_id') : null,
            $request->filled('person_id') ? (int) $request->input('person_id') : null,
            $request->filled('feature_type') ? (string) $request->input('feature_type') : null,
            $perPage,
        );

        return ApiResponse::paginated($mapped, 'Imaging features retrieved');
    }

    // =====================================================================
    //  8. GET /imaging/criteria
    // =====================================================================

    public function criteriaIndex(Request $request): JsonResponse
    {
        $criteria = ImagingCriteria::query()
            ->when($request->filled('type'), fn ($q) => $q->where('criteria_type', $request->input('type')))
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success($criteria, 'Imaging criteria retrieved');
    }

    // =====================================================================
    //  9. POST /imaging/criteria
    // =====================================================================

    public function criteriaStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'criteria_type' => 'required|string|max:50',
            'criteria_definition' => 'required|array',
            'description' => 'nullable|string|max:1000',
            'is_shared' => 'sometimes|boolean',
        ]);

        $criterion = ImagingCriteria::create(array_merge($validated, [
            'created_by' => $request->user()?->id,
        ]));

        return ApiResponse::success($criterion, 'Criterion created', 201);
    }

    // =====================================================================
    //  10. DELETE /imaging/criteria/{id}
    // =====================================================================

    public function criteriaDestroy(int $id): JsonResponse
    {
        $criterion = ImagingCriteria::find($id);

        if (! $criterion) {
            return ApiResponse::error('Criterion not found', 404);
        }

        $criterion->delete();

        return ApiResponse::success(null, 'Criterion deleted');
    }

    // =====================================================================
    //  11. GET /imaging/analytics/population
    // =====================================================================

    public function populationAnalytics(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->analyticsService->population($request->input('modality')),
            'Population analytics retrieved'
        );
    }

    // =====================================================================
    //  12. POST /imaging/import-local/trigger
    // =====================================================================

    public function importLocalTrigger(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|required_without:dir|string|max:4096',
            'dir' => 'nullable|required_without:path|string|max:4096',
        ]);

        $path = $validated['path'] ?? $validated['dir'] ?? null;

        if (empty($this->ingestionService->localImportRoots())) {
            return ApiResponse::error('Local DICOM import roots are not configured', 503);
        }

        if (! config('services.imaging.local_import_command')) {
            return ApiResponse::error('Local DICOM import command is not configured', 503);
        }

        if (! $path || ! $this->ingestionService->pathIsAllowlisted($path)) {
            return ApiResponse::error('Local DICOM import path is outside the configured allowlist', 422);
        }

        [$run, $created] = $this->ingestionService->createOrReuseRun(
            'local_import',
            ['path' => $path],
            $request->user()?->id,
        );

        if ($created) {
            ImportLocalDicomJob::dispatch($run->id)->onQueue('imaging');
        }

        return ApiResponse::success(
            $this->ingestionService->runPayload($run),
            $created ? 'Local DICOM import queued' : 'Matching local import run is already active',
            202
        );
    }

    // =====================================================================
    //  13. GET /imaging/patients/{personId}/timeline
    // =====================================================================

    public function patientTimeline(int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        return ApiResponse::success(
            $this->studyService->patientTimeline($patient, $personId),
            'Patient imaging timeline retrieved'
        );
    }

    // =====================================================================
    //  14. GET /imaging/patients/{personId}/studies
    // =====================================================================

    public function patientStudies(int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        return ApiResponse::success(
            $this->studyService->patientStudies($personId),
            'Patient imaging studies retrieved'
        );
    }

    // =====================================================================
    //  15. GET /imaging/patients  (paginated patients with imaging)
    // =====================================================================

    public function patientsWithImaging(Request $request): JsonResponse
    {
        $minStudies = max((int) ($request->input('min_studies', 1)), 1);
        $modality = $request->input('modality');
        $perPage = min((int) ($request->input('per_page', 25)), 100);

        $mapped = $this->studyService->patientsWithImaging($minStudies, $modality, $perPage);

        return ApiResponse::paginated($mapped, 'Patients with imaging retrieved');
    }

    // =====================================================================
    //  16. POST /imaging/studies/{id}/link-person
    // =====================================================================

    public function linkStudyToPerson(Request $request, int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $validated = $request->validate([
            'person_id' => 'required|integer',
        ]);

        $patient = ClinicalPatient::find($validated['person_id']);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        return ApiResponse::success(
            $this->studyService->linkStudyToPerson($study, $validated['person_id']),
            'Study linked to patient'
        );
    }

    // =====================================================================
    //  17. POST /imaging/studies/bulk-link
    // =====================================================================

    public function bulkLinkStudies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'study_ids' => 'required|array|min:1',
            'study_ids.*' => 'integer',
            'person_id' => 'required|integer',
        ]);

        $patient = ClinicalPatient::find($validated['person_id']);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        $linked = $this->studyService->bulkLinkStudies($validated['study_ids'], $validated['person_id']);

        return ApiResponse::success([
            'linked' => $linked,
        ], 'Studies bulk-linked to patient');
    }

    // =====================================================================
    //  18. POST /imaging/studies/auto-link
    // =====================================================================

    public function autoLinkStudies(): JsonResponse
    {
        return ApiResponse::error(
            'Auto-link requires staged unlinked studies. Current imaging ingestion resolves deterministic DICOM patient identifiers before insert and quarantines unmatched studies.',
            422,
            [
                'rules' => [
                    'blank_patient_id' => 'manual_review',
                    'deterministic_identifier_match' => 'ingest_only',
                ],
            ]
        );
    }

    // =====================================================================
    //  19. GET /imaging/studies/{id}/measurements
    // =====================================================================

    public function studyMeasurements(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success(
            $this->measurementService->studyMeasurements($study),
            'Study measurements retrieved'
        );
    }

    // =====================================================================
    //  20. POST /imaging/studies/{id}/measurements
    // =====================================================================

    public function createStudyMeasurement(Request $request, int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $validated = $request->validate([
            'measurement_type' => 'required|string|max:50',
            'measurement_name' => 'nullable|string|max:255',
            'value_as_number' => 'required|numeric',
            'unit' => 'required|string|max:30',
            'body_site' => 'nullable|string|max:100',
            'laterality' => 'nullable|string|max:30',
            'series_id' => 'nullable|integer',
            'algorithm_name' => 'nullable|string|max:255',
            'confidence' => 'nullable|numeric|min:0|max:1',
            'measured_at' => 'nullable|date',
            'is_target_lesion' => 'sometimes|boolean',
            'target_lesion_number' => 'nullable|integer',
        ]);

        $result = $this->measurementService->createStudyMeasurement($study, $validated);

        if (! $result['ok']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success($result['data'], 'Measurement created', 201);
    }

    // =====================================================================
    //  21. PUT /imaging/measurements/{id}
    // =====================================================================

    public function updateMeasurement(Request $request, int $id): JsonResponse
    {
        $measurement = ImagingMeasurement::find($id);

        if (! $measurement) {
            return ApiResponse::error('Measurement not found', 404);
        }

        $validated = $request->validate([
            'measurement_type' => 'sometimes|string|max:50',
            'measurement_name' => 'nullable|string|max:255',
            'value_as_number' => 'sometimes|numeric',
            'value_numeric' => 'sometimes|numeric',
            'unit' => 'sometimes|string|max:30',
            'body_site' => 'nullable|string|max:100',
            'laterality' => 'nullable|string|max:30',
            'series_id' => 'nullable|integer',
            'target_lesion' => 'sometimes|boolean',
            'is_target_lesion' => 'sometimes|boolean',
            'target_lesion_number' => 'nullable|integer',
            'measured_by' => 'nullable|string|max:255',
            'algorithm_name' => 'nullable|string|max:255',
            'confidence' => 'nullable|numeric|min:0|max:1',
            'measured_at' => 'nullable|date',
        ]);

        $result = $this->measurementService->updateMeasurement($measurement, $validated);

        if (! $result['ok']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success($result['data'], 'Measurement updated');
    }

    // =====================================================================
    //  22. DELETE /imaging/measurements/{id}
    // =====================================================================

    public function destroyMeasurement(int $id): JsonResponse
    {
        $measurement = ImagingMeasurement::find($id);

        if (! $measurement) {
            return ApiResponse::error('Measurement not found', 404);
        }

        $measurement->delete();

        return ApiResponse::success(null, 'Measurement deleted');
    }

    // =====================================================================
    //  23. GET /imaging/patients/{personId}/measurements
    // =====================================================================

    public function patientMeasurements(Request $request, int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        return ApiResponse::success(
            $this->measurementService->patientMeasurements($personId, $request),
            'Patient measurements retrieved'
        );
    }

    // =====================================================================
    //  24. GET /imaging/patients/{personId}/measurements/trends
    // =====================================================================

    public function measurementTrends(Request $request, int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        $measurementType = $request->input('measurement_type');

        if (! $measurementType) {
            return ApiResponse::error('measurement_type parameter is required', 422);
        }

        return ApiResponse::success(
            $this->measurementService->measurementTrends($personId, $measurementType, $request),
            'Measurement trends retrieved'
        );
    }

    // =====================================================================
    //  25. GET /imaging/patients/{personId}/response-assessments
    // =====================================================================

    public function patientResponseAssessments(int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        $stored = $this->assessmentService->storedAssessments($personId);

        if ($stored !== null) {
            return ApiResponse::success($stored, 'Response assessments retrieved');
        }

        $computed = $this->assessmentService->computeRecistAssessments($personId);

        return ApiResponse::success($computed['data'], $computed['message']);
    }

    // =====================================================================
    //  26. POST /imaging/patients/{personId}/response-assessments
    // =====================================================================

    public function createResponseAssessment(Request $request, int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        $validated = $request->validate([
            'criteria_type' => 'required|string|max:50',
            'assessment_date' => 'required|date',
            'baseline_study_id' => 'required|integer',
            'current_study_id' => 'required|integer',
            'response_category' => 'required|string|max:10',
            'body_site' => 'nullable|string|max:100',
            'baseline_value' => 'nullable|numeric',
            'nadir_value' => 'nullable|numeric',
            'current_value' => 'nullable|numeric',
            'percent_change_from_baseline' => 'nullable|numeric',
            'percent_change_from_nadir' => 'nullable|numeric',
            'rationale' => 'nullable|string|max:2000',
            'is_confirmed' => 'sometimes|boolean',
        ]);

        $result = $this->assessmentService->createManual($personId, $validated, $request->user()?->id);

        if (! $result['ok']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success($result['data'], 'Response assessment created', 201);
    }

    // =====================================================================
    //  27. POST /imaging/patients/{personId}/compute-response
    // =====================================================================

    public function computeResponse(Request $request, int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        $validated = $request->validate([
            'current_study_id' => 'required|integer',
            'baseline_study_id' => 'nullable|integer',
            'criteria_type' => 'nullable|string|max:50',
        ]);

        $result = $this->assessmentService->computeResponse($personId, $validated, $request->user()?->id);

        if (! $result['ok']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success($result['data'], 'Response computed');
    }

    // =====================================================================
    //  28. POST /imaging/patients/{personId}/assess-preview
    // =====================================================================

    public function assessPreview(Request $request, int $personId): JsonResponse
    {
        $patient = ClinicalPatient::find($personId);

        if (! $patient) {
            return ApiResponse::error('Patient not found', 404);
        }

        $validated = $request->validate([
            'current_study_id' => 'required|integer',
            'criteria_type' => 'nullable|string|max:50',
        ]);

        $result = $this->assessmentService->assessPreview($personId, $validated);

        if (! $result['ok']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success($result['data'], 'Assessment preview');
    }

    // =====================================================================
    //  29. POST /imaging/studies/{id}/ai-extract
    // =====================================================================

    public function aiExtractMeasurements(Request $request, int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $validated = $request->validate([
            'measurement_type' => 'sometimes|string|in:tumor_volume,organ_volume',
        ]);

        $measurementType = $validated['measurement_type'] ?? 'tumor_volume';

        $result = $this->measurementService->aiExtractMeasurements($study, $measurementType);

        if (! $result['ok']) {
            return ApiResponse::error(
                $result['message'],
                $result['status'],
                $result['extra'] ?? (isset($result['detail']) ? ['detail' => $result['detail']] : [])
            );
        }

        return ApiResponse::success($result['data'], 'AI measurements extracted');
    }

    // =====================================================================
    //  30. GET /imaging/studies/{id}/suggest-template
    // =====================================================================

    public function suggestTemplate(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success(
            $this->studyService->suggestTemplate($study),
            'Measurement template suggested'
        );
    }

    public function ingestionRuns(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 25)), 100);
        $query = \App\Models\Clinical\ImagingIngestionRun::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('run_type')) {
            $query->where('run_type', (string) $request->input('run_type'));
        }

        $paginator = $query->paginate($perPage);
        $mapped = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($paginator->items())->map(fn ($run) => $this->ingestionService->runPayload($run)),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );

        return ApiResponse::paginated($mapped, 'Imaging ingestion runs retrieved');
    }

    public function ingestionRunShow(int $id): JsonResponse
    {
        $run = \App\Models\Clinical\ImagingIngestionRun::find($id);

        if (! $run) {
            return ApiResponse::error('Imaging ingestion run not found', 404);
        }

        return ApiResponse::success($this->ingestionService->runPayload($run), 'Imaging ingestion run retrieved');
    }

    // =====================================================================
    //  Legacy patient-scoped methods (used by /patients/{patient}/imaging routes)
    // =====================================================================

    /**
     * GET /api/patients/{patient}/imaging
     */
    public function index(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (! $patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $studies = $this->studyService->legacyIndex(
            $patient,
            $request->has('modality') ? $request->input('modality') : null,
            $request->has('body_part') ? $request->input('body_part') : null,
        );

        return ApiResponse::success($studies, 'Imaging studies retrieved');
    }

    /**
     * GET /api/patients/{patient}/imaging/{study}
     */
    public function show(int $patient, int $study): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (! $patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $studyModel = ImagingStudy::where('id', $study)
            ->where('patient_id', $patient)
            ->first();

        if (! $studyModel) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success($this->studyService->legacyShow($studyModel), 'Imaging study details retrieved');
    }

    /**
     * POST /api/patients/{patient}/imaging/{study}/measurements
     */
    public function storeMeasurement(Request $request, int $patient, int $study): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (! $patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $studyModel = ImagingStudy::where('id', $study)
            ->where('patient_id', $patient)
            ->first();

        if (! $studyModel) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $validated = $request->validate([
            'measurement_type' => 'required|string|max:30',
            'measurement_name' => 'nullable|string|max:255',
            'target_lesion' => 'sometimes|boolean',
            'is_target_lesion' => 'sometimes|boolean',
            'target_lesion_number' => 'nullable|integer',
            'value_numeric' => 'required|numeric',
            'value_as_number' => 'sometimes|numeric',
            'unit' => 'required|string|max:30',
            'body_site' => 'nullable|string|max:100',
            'laterality' => 'nullable|string|max:30',
            'series_id' => 'nullable|integer',
            'measured_by' => 'nullable|string|max:255',
            'algorithm_name' => 'nullable|string|max:255',
            'confidence' => 'nullable|numeric|min:0|max:1',
            'measured_at' => 'nullable|date',
        ]);

        $result = $this->measurementService->storeLegacyMeasurement($studyModel, $validated);

        if (! $result['ok']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success($result['data'], 'Measurement added', 201);
    }

    /**
     * GET /api/patients/{patient}/imaging/response-assessments
     */
    public function responseAssessments(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (! $patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $computed = $this->assessmentService->computeRecistAssessments($patient);

        return ApiResponse::success($computed['data'], $computed['message']);
    }
}
