<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ImagingController extends Controller
{
    // ─── Helper: format a study row for JSON ─────────────────────────────

    private function formatStudy(ImagingStudy $study): array
    {
        return [
            'id' => $study->id,
            'patient_id' => $study->patient_id,
            'study_uid' => $study->study_uid,
            'modality' => $study->modality,
            'study_date' => $study->study_date?->toDateString(),
            'description' => $study->description,
            'body_part' => $study->body_part,
            'laterality' => $study->laterality,
            'accession_number' => $study->accession_number,
            'num_series' => $study->num_series,
            'num_instances' => $study->num_instances,
            'dicom_endpoint' => $study->dicom_endpoint,
            'source_id' => $study->source_id,
            'source_type' => $study->source_type,
            'measurement_count' => $study->imagingMeasurements()->count(),
            'segmentation_count' => $study->segmentations()->count(),
        ];
    }

    private function formatMeasurement(ImagingMeasurement $m): array
    {
        return [
            'id' => $m->id,
            'imaging_study_id' => $m->imaging_study_id,
            'measurement_type' => $m->measurement_type,
            'target_lesion' => $m->target_lesion,
            'value_numeric' => $m->value_numeric,
            'unit' => $m->unit,
            'measured_by' => $m->measured_by,
            'measured_at' => $m->measured_at?->toISOString(),
            'source_id' => $m->source_id,
            'source_type' => $m->source_type,
        ];
    }

    // =====================================================================
    //  1. GET /imaging/stats
    // =====================================================================

    public function stats(): JsonResponse
    {
        $totalStudies = ImagingStudy::count();
        $totalPatients = ImagingStudy::distinct()->count('patient_id');
        $totalMeasurements = ImagingMeasurement::count();

        $modalityCounts = ImagingStudy::select('modality', DB::raw('count(*) as count'))
            ->whereNotNull('modality')
            ->groupBy('modality')
            ->pluck('count', 'modality');

        $bodyPartCounts = ImagingStudy::select('body_part', DB::raw('count(*) as count'))
            ->whereNotNull('body_part')
            ->groupBy('body_part')
            ->pluck('count', 'body_part');

        return ApiResponse::success([
            'total_studies' => $totalStudies,
            'total_patients' => $totalPatients,
            'total_measurements' => $totalMeasurements,
            'modality_counts' => $modalityCounts,
            'body_part_counts' => $bodyPartCounts,
        ], 'Imaging stats retrieved');
    }

    // =====================================================================
    //  2. GET /imaging/studies  (paginated, with modality/person_id filters)
    // =====================================================================

    public function studies(Request $request): JsonResponse
    {
        $query = ImagingStudy::orderBy('study_date', 'desc');

        if ($request->filled('modality')) {
            $query->where('modality', $request->input('modality'));
        }

        if ($request->filled('person_id')) {
            $query->where('patient_id', (int) $request->input('person_id'));
        }

        $perPage = min((int) ($request->input('per_page', 25)), 100);
        $paginator = $query->paginate($perPage);

        $mapped = new LengthAwarePaginator(
            collect($paginator->items())->map(fn (ImagingStudy $s) => $this->formatStudy($s)),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );

        return ApiResponse::paginated($mapped, 'Imaging studies retrieved');
    }

    // =====================================================================
    //  3. GET /imaging/studies/{id}
    // =====================================================================

    public function studyShow(int $id): JsonResponse
    {
        $study = ImagingStudy::with(['series', 'imagingMeasurements', 'segmentations'])->find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        $data = $this->formatStudy($study);
        $data['series'] = $study->series->map(fn ($s) => [
            'id' => $s->id,
            'series_uid' => $s->series_uid,
            'series_number' => $s->series_number,
            'modality' => $s->modality,
            'description' => $s->description,
            'num_instances' => $s->num_instances,
        ])->values();
        $data['measurements'] = $study->imagingMeasurements->map(fn ($m) => $this->formatMeasurement($m))->values();
        $data['segmentations'] = $study->segmentations->map(fn ($seg) => [
            'id' => $seg->id,
            'segmentation_uid' => $seg->segmentation_uid,
            'algorithm' => $seg->algorithm,
            'label' => $seg->label,
            'volume_mm3' => $seg->volume_mm3,
            'created_at' => $seg->created_at?->toISOString(),
        ])->values();

        return ApiResponse::success($data, 'Imaging study details retrieved');
    }

    // =====================================================================
    //  4. POST /imaging/studies/index-from-dicomweb  (stub)
    // =====================================================================

    public function indexFromDicomweb(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'indexed' => 0,
            'updated' => 0,
            'errors' => 0,
        ], 'DICOMweb indexing not yet implemented');
    }

    // =====================================================================
    //  5. POST /imaging/studies/{id}/index-series  (stub)
    // =====================================================================

    public function indexSeries(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success([
            'indexed' => 0,
            'errors' => 0,
        ], 'Series indexing not yet implemented');
    }

    // =====================================================================
    //  6. POST /imaging/studies/{id}/extract-nlp  (stub)
    // =====================================================================

    public function extractNlp(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success([
            'extracted' => 0,
            'mapped' => 0,
            'errors' => 0,
        ], 'NLP extraction not yet implemented');
    }

    // =====================================================================
    //  7. GET /imaging/features  (stub, paginated)
    // =====================================================================

    public function features(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 25)), 100);
        $page = max((int) ($request->input('page', 1)), 1);

        $paginator = new LengthAwarePaginator([], 0, $perPage, $page);

        return ApiResponse::paginated($paginator, 'Imaging features retrieved');
    }

    // =====================================================================
    //  8. GET /imaging/criteria  (stub)
    // =====================================================================

    public function criteriaIndex(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Imaging criteria retrieved');
    }

    // =====================================================================
    //  9. POST /imaging/criteria  (stub)
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

        return ApiResponse::success([
            'id' => 0,
            'name' => $validated['name'],
            'criteria_type' => $validated['criteria_type'],
            'criteria_definition' => $validated['criteria_definition'],
            'description' => $validated['description'] ?? null,
            'is_shared' => $validated['is_shared'] ?? false,
            'created_at' => now()->toISOString(),
        ], 'Criterion created (stub)', 201);
    }

    // =====================================================================
    //  10. DELETE /imaging/criteria/{id}  (stub)
    // =====================================================================

    public function criteriaDestroy(int $id): JsonResponse
    {
        return ApiResponse::success(null, 'Criterion deleted (stub)');
    }

    // =====================================================================
    //  11. GET /imaging/analytics/population  (stub)
    // =====================================================================

    public function populationAnalytics(Request $request): JsonResponse
    {
        $modality = $request->input('modality');

        $query = ImagingStudy::query();
        if ($modality) {
            $query->where('modality', $modality);
        }

        $totalStudies = $query->count();
        $totalPatients = (clone $query)->distinct()->count('patient_id');

        $modalityDistribution = ImagingStudy::select('modality', DB::raw('count(*) as count'))
            ->whereNotNull('modality')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('modality')
            ->pluck('count', 'modality');

        $bodyPartDistribution = ImagingStudy::select('body_part', DB::raw('count(*) as count'))
            ->whereNotNull('body_part')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('body_part')
            ->pluck('count', 'body_part');

        return ApiResponse::success([
            'total_studies' => $totalStudies,
            'total_patients' => $totalPatients,
            'modality_distribution' => $modalityDistribution,
            'body_part_distribution' => $bodyPartDistribution,
            'temporal_distribution' => [],
        ], 'Population analytics retrieved');
    }

    // =====================================================================
    //  12. POST /imaging/import-local/trigger  (stub)
    // =====================================================================

    public function importLocalTrigger(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'studies_imported' => 0,
            'series_imported' => 0,
            'instances_imported' => 0,
        ], 'Local import not yet implemented');
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

        $studies = ImagingStudy::where('patient_id', $personId)
            ->orderBy('study_date', 'asc')
            ->with('imagingMeasurements')
            ->get();

        $events = $studies->map(fn (ImagingStudy $s) => [
            'study_id' => $s->id,
            'study_date' => $s->study_date?->toDateString(),
            'modality' => $s->modality,
            'description' => $s->description,
            'body_part' => $s->body_part,
            'measurement_count' => $s->imagingMeasurements->count(),
        ])->values();

        return ApiResponse::success([
            'person_id' => $personId,
            'events' => $events,
        ], 'Patient imaging timeline retrieved');
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

        $studies = ImagingStudy::where('patient_id', $personId)
            ->orderBy('study_date', 'desc')
            ->get()
            ->map(fn (ImagingStudy $s) => $this->formatStudy($s));

        return ApiResponse::success($studies, 'Patient imaging studies retrieved');
    }

    // =====================================================================
    //  15. GET /imaging/patients  (paginated patients with imaging)
    // =====================================================================

    public function patientsWithImaging(Request $request): JsonResponse
    {
        $minStudies = max((int) ($request->input('min_studies', 1)), 1);
        $modality = $request->input('modality');
        $perPage = min((int) ($request->input('per_page', 25)), 100);

        $patientIds = ImagingStudy::select('patient_id')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('patient_id')
            ->havingRaw('count(*) >= ?', [$minStudies])
            ->pluck('patient_id');

        $query = ClinicalPatient::whereIn('id', $patientIds)->orderBy('id');
        $paginator = $query->paginate($perPage);

        $studyCounts = ImagingStudy::select('patient_id', DB::raw('count(*) as study_count'))
            ->whereIn('patient_id', collect($paginator->items())->pluck('id'))
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('patient_id')
            ->pluck('study_count', 'patient_id');

        $items = collect($paginator->items())->map(fn (ClinicalPatient $p) => [
            'person_id' => $p->id,
            'first_name' => $p->first_name,
            'last_name' => $p->last_name,
            'date_of_birth' => $p->date_of_birth?->toDateString(),
            'gender' => $p->gender,
            'study_count' => $studyCounts[$p->id] ?? 0,
        ]);

        $mapped = new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );

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

        $study->patient_id = $validated['person_id'];
        $study->save();

        return ApiResponse::success($this->formatStudy($study->fresh()), 'Study linked to patient');
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

        $linked = ImagingStudy::whereIn('id', $validated['study_ids'])
            ->update(['patient_id' => $validated['person_id']]);

        return ApiResponse::success([
            'linked' => $linked,
        ], 'Studies bulk-linked to patient');
    }

    // =====================================================================
    //  18. POST /imaging/studies/auto-link  (stub)
    // =====================================================================

    public function autoLinkStudies(): JsonResponse
    {
        return ApiResponse::success([
            'linked' => 0,
        ], 'Auto-link not yet implemented');
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

        $measurements = $study->imagingMeasurements()
            ->orderBy('measured_at', 'desc')
            ->get()
            ->map(fn (ImagingMeasurement $m) => $this->formatMeasurement($m));

        return ApiResponse::success($measurements, 'Study measurements retrieved');
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

        $measurement = ImagingMeasurement::create([
            'imaging_study_id' => $study->id,
            'measurement_type' => $validated['measurement_type'],
            'target_lesion' => $validated['is_target_lesion'] ?? false,
            'value_numeric' => $validated['value_as_number'],
            'unit' => $validated['unit'],
            'measured_by' => $validated['algorithm_name'] ?? null,
            'measured_at' => $validated['measured_at'] ?? now(),
            'source_id' => $validated['series_id'] ?? null,
            'source_type' => $validated['series_id'] ? 'series' : null,
        ]);

        return ApiResponse::success($this->formatMeasurement($measurement), 'Measurement created', 201);
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
            'value_as_number' => 'sometimes|numeric',
            'value_numeric' => 'sometimes|numeric',
            'unit' => 'sometimes|string|max:30',
            'target_lesion' => 'sometimes|boolean',
            'is_target_lesion' => 'sometimes|boolean',
            'measured_by' => 'nullable|string|max:255',
            'measured_at' => 'nullable|date',
        ]);

        $updates = [];

        if (array_key_exists('measurement_type', $validated)) {
            $updates['measurement_type'] = $validated['measurement_type'];
        }
        if (array_key_exists('value_as_number', $validated)) {
            $updates['value_numeric'] = $validated['value_as_number'];
        } elseif (array_key_exists('value_numeric', $validated)) {
            $updates['value_numeric'] = $validated['value_numeric'];
        }
        if (array_key_exists('unit', $validated)) {
            $updates['unit'] = $validated['unit'];
        }
        if (array_key_exists('is_target_lesion', $validated)) {
            $updates['target_lesion'] = $validated['is_target_lesion'];
        } elseif (array_key_exists('target_lesion', $validated)) {
            $updates['target_lesion'] = $validated['target_lesion'];
        }
        if (array_key_exists('measured_by', $validated)) {
            $updates['measured_by'] = $validated['measured_by'];
        }
        if (array_key_exists('measured_at', $validated)) {
            $updates['measured_at'] = $validated['measured_at'];
        }

        if (! empty($updates)) {
            $measurement->update($updates);
            $measurement->refresh();
        }

        return ApiResponse::success($this->formatMeasurement($measurement), 'Measurement updated');
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

        $studyIds = ImagingStudy::where('patient_id', $personId)->pluck('id');

        $query = ImagingMeasurement::whereIn('imaging_study_id', $studyIds)
            ->orderBy('measured_at', 'desc');

        if ($request->filled('measurement_type')) {
            $query->where('measurement_type', $request->input('measurement_type'));
        }

        if ($request->filled('body_site')) {
            // body_site maps to source_type or similar — filter via study body_part
            $filteredStudyIds = ImagingStudy::where('patient_id', $personId)
                ->where('body_part', $request->input('body_site'))
                ->pluck('id');
            $query->whereIn('imaging_study_id', $filteredStudyIds);
        }

        $measurements = $query->get()->map(fn (ImagingMeasurement $m) => $this->formatMeasurement($m));

        return ApiResponse::success($measurements, 'Patient measurements retrieved');
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

        $studyIds = ImagingStudy::where('patient_id', $personId)->pluck('id');

        $query = ImagingMeasurement::whereIn('imaging_study_id', $studyIds)
            ->where('measurement_type', $measurementType)
            ->orderBy('measured_at', 'asc');

        if ($request->filled('body_site')) {
            $filteredStudyIds = ImagingStudy::where('patient_id', $personId)
                ->where('body_part', $request->input('body_site'))
                ->pluck('id');
            $query->whereIn('imaging_study_id', $filteredStudyIds);
        }

        $measurements = $query->with('imagingStudy')->get();

        $trends = $measurements->map(fn (ImagingMeasurement $m) => [
            'measurement_id' => $m->id,
            'study_id' => $m->imaging_study_id,
            'study_date' => $m->imagingStudy?->study_date?->toDateString(),
            'measurement_type' => $m->measurement_type,
            'value_numeric' => $m->value_numeric,
            'unit' => $m->unit,
            'measured_at' => $m->measured_at?->toISOString(),
        ])->values();

        return ApiResponse::success($trends, 'Measurement trends retrieved');
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

        return $this->computeRecistAssessments($personId);
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

        // Return the assessment as-is (no dedicated table yet — stub persistence)
        $assessment = array_merge($validated, [
            'id' => 0,
            'person_id' => $personId,
            'is_confirmed' => $validated['is_confirmed'] ?? false,
            'created_at' => now()->toISOString(),
        ]);

        return ApiResponse::success($assessment, 'Response assessment created (stub)', 201);
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

        $currentStudy = ImagingStudy::where('id', $validated['current_study_id'])
            ->where('patient_id', $personId)
            ->with('imagingMeasurements')
            ->first();

        if (! $currentStudy) {
            return ApiResponse::error('Current study not found for this patient', 404);
        }

        // Find baseline: explicit or first study
        $baselineStudy = null;
        if (! empty($validated['baseline_study_id'])) {
            $baselineStudy = ImagingStudy::where('id', $validated['baseline_study_id'])
                ->where('patient_id', $personId)
                ->with('imagingMeasurements')
                ->first();
        }

        if (! $baselineStudy) {
            $baselineStudy = ImagingStudy::where('patient_id', $personId)
                ->orderBy('study_date', 'asc')
                ->with('imagingMeasurements')
                ->first();
        }

        if (! $baselineStudy || $baselineStudy->id === $currentStudy->id) {
            return ApiResponse::error('Need at least two distinct studies for assessment', 422);
        }

        $criteriaType = $validated['criteria_type'] ?? 'RECIST';

        $baselineTargets = $baselineStudy->imagingMeasurements->where('target_lesion', true);
        $currentTargets = $currentStudy->imagingMeasurements->where('target_lesion', true);

        $baselineSum = $baselineTargets->sum('value_numeric');
        $currentSum = $currentTargets->sum('value_numeric');

        $percentChange = null;
        $category = 'NE';

        if ($baselineSum > 0) {
            $percentChange = round((($currentSum - $baselineSum) / $baselineSum) * 100, 2);
            $absoluteChange = $currentSum - $baselineSum;
            $allDisappeared = $currentTargets->every(fn ($m) => (float) $m->value_numeric === 0.0);

            if ($allDisappeared && $currentTargets->isNotEmpty()) {
                $category = 'CR';
            } elseif ($percentChange <= -30.0) {
                $category = 'PR';
            } elseif ($percentChange >= 20.0 && $absoluteChange >= 5.0) {
                $category = 'PD';
            } else {
                $category = 'SD';
            }
        }

        return ApiResponse::success([
            'id' => 0,
            'person_id' => $personId,
            'criteria_type' => $criteriaType,
            'assessment_date' => now()->toDateString(),
            'baseline_study_id' => $baselineStudy->id,
            'current_study_id' => $currentStudy->id,
            'response_category' => $category,
            'baseline_value' => round((float) $baselineSum, 2),
            'nadir_value' => null,
            'current_value' => round((float) $currentSum, 2),
            'percent_change_from_baseline' => $percentChange,
            'percent_change_from_nadir' => null,
            'rationale' => "Computed via {$criteriaType}: baseline sum={$baselineSum}, current sum={$currentSum}",
            'is_confirmed' => false,
            'created_at' => now()->toISOString(),
        ], 'Response computed');
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

        $criteriaType = $validated['criteria_type'] ?? 'RECIST';

        $currentStudy = ImagingStudy::where('id', $validated['current_study_id'])
            ->where('patient_id', $personId)
            ->with('imagingMeasurements')
            ->first();

        if (! $currentStudy) {
            return ApiResponse::error('Current study not found for this patient', 404);
        }

        $baselineStudy = ImagingStudy::where('patient_id', $personId)
            ->orderBy('study_date', 'asc')
            ->with('imagingMeasurements')
            ->first();

        if (! $baselineStudy || $baselineStudy->id === $currentStudy->id) {
            return ApiResponse::success([
                'response_category' => 'NE',
                'criteria_type' => $criteriaType,
                'rationale' => 'Insufficient studies for assessment',
                'baseline_value' => null,
                'nadir_value' => null,
                'current_value' => null,
                'percent_change_from_baseline' => null,
                'percent_change_from_nadir' => null,
            ], 'Assessment preview');
        }

        $baselineTargets = $baselineStudy->imagingMeasurements->where('target_lesion', true);
        $currentTargets = $currentStudy->imagingMeasurements->where('target_lesion', true);

        $baselineSum = $baselineTargets->sum('value_numeric');
        $currentSum = $currentTargets->sum('value_numeric');

        $percentChange = null;
        $category = 'NE';

        if ($baselineSum > 0) {
            $percentChange = round((($currentSum - $baselineSum) / $baselineSum) * 100, 2);
            $absoluteChange = $currentSum - $baselineSum;
            $allDisappeared = $currentTargets->every(fn ($m) => (float) $m->value_numeric === 0.0);

            if ($allDisappeared && $currentTargets->isNotEmpty()) {
                $category = 'CR';
            } elseif ($percentChange <= -30.0) {
                $category = 'PR';
            } elseif ($percentChange >= 20.0 && $absoluteChange >= 5.0) {
                $category = 'PD';
            } else {
                $category = 'SD';
            }
        }

        return ApiResponse::success([
            'response_category' => $category,
            'criteria_type' => $criteriaType,
            'rationale' => "Preview via {$criteriaType}: baseline sum={$baselineSum}, current sum={$currentSum}",
            'baseline_value' => $baselineSum > 0 ? round((float) $baselineSum, 2) : null,
            'nadir_value' => null,
            'current_value' => $currentSum > 0 ? round((float) $currentSum, 2) : null,
            'percent_change_from_baseline' => $percentChange,
            'percent_change_from_nadir' => null,
        ], 'Assessment preview');
    }

    // =====================================================================
    //  29. POST /imaging/studies/{id}/ai-extract  (stub)
    // =====================================================================

    public function aiExtractMeasurements(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success([
            'extracted' => 0,
            'measurement_types' => [],
        ], 'AI extraction not yet implemented');
    }

    // =====================================================================
    //  30. GET /imaging/studies/{id}/suggest-template  (stub)
    // =====================================================================

    public function suggestTemplate(int $id): JsonResponse
    {
        $study = ImagingStudy::find($id);

        if (! $study) {
            return ApiResponse::error('Imaging study not found', 404);
        }

        return ApiResponse::success([
            'template' => 'general',
            'fields' => [],
        ], 'Template suggestion not yet implemented');
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

        $query = ImagingStudy::where('patient_id', $patient)
            ->orderBy('study_date', 'desc');

        if ($request->has('modality')) {
            $query->where('modality', $request->input('modality'));
        }

        if ($request->has('body_part')) {
            $query->where('body_part', $request->input('body_part'));
        }

        $studies = $query->get()->map(fn (ImagingStudy $study) => [
            'id' => $study->id,
            'study_uid' => $study->study_uid,
            'modality' => $study->modality,
            'study_date' => $study->study_date?->toDateString(),
            'description' => $study->description,
            'body_part' => $study->body_part,
            'laterality' => $study->laterality,
            'accession_number' => $study->accession_number,
            'num_series' => $study->num_series,
            'num_instances' => $study->num_instances,
            'measurement_count' => $study->imagingMeasurements()->count(),
            'segmentation_count' => $study->segmentations()->count(),
        ]);

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

        $data = [
            'id' => $studyModel->id,
            'study_uid' => $studyModel->study_uid,
            'modality' => $studyModel->modality,
            'study_date' => $studyModel->study_date?->toDateString(),
            'description' => $studyModel->description,
            'body_part' => $studyModel->body_part,
            'laterality' => $studyModel->laterality,
            'accession_number' => $studyModel->accession_number,
            'num_series' => $studyModel->num_series,
            'num_instances' => $studyModel->num_instances,
            'dicom_endpoint' => $studyModel->dicom_endpoint,
            'series' => $studyModel->series->map(fn ($s) => [
                'id' => $s->id,
                'series_uid' => $s->series_uid,
                'series_number' => $s->series_number,
                'modality' => $s->modality,
                'description' => $s->description,
                'num_instances' => $s->num_instances,
            ]),
            'measurements' => $studyModel->imagingMeasurements->map(fn ($m) => [
                'id' => $m->id,
                'measurement_type' => $m->measurement_type,
                'target_lesion' => $m->target_lesion,
                'value_numeric' => $m->value_numeric,
                'unit' => $m->unit,
                'measured_by' => $m->measured_by,
                'measured_at' => $m->measured_at?->toISOString(),
            ]),
            'segmentations' => $studyModel->segmentations->map(fn ($seg) => [
                'id' => $seg->id,
                'segmentation_uid' => $seg->segmentation_uid,
                'algorithm' => $seg->algorithm,
                'label' => $seg->label,
                'volume_mm3' => $seg->volume_mm3,
                'created_at' => $seg->created_at?->toISOString(),
            ]),
        ];

        return ApiResponse::success($data, 'Imaging study details retrieved');
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
            'target_lesion' => 'sometimes|boolean',
            'value_numeric' => 'required|numeric',
            'unit' => 'required|string|max:30',
            'measured_by' => 'nullable|string|max:255',
            'measured_at' => 'nullable|date',
        ]);

        $measurement = ImagingMeasurement::create([
            'imaging_study_id' => $studyModel->id,
            'measurement_type' => $validated['measurement_type'],
            'target_lesion' => $validated['target_lesion'] ?? false,
            'value_numeric' => $validated['value_numeric'],
            'unit' => $validated['unit'],
            'measured_by' => $validated['measured_by'] ?? null,
            'measured_at' => $validated['measured_at'] ?? now(),
        ]);

        return ApiResponse::success([
            'id' => $measurement->id,
            'measurement_type' => $measurement->measurement_type,
            'target_lesion' => $measurement->target_lesion,
            'value_numeric' => $measurement->value_numeric,
            'unit' => $measurement->unit,
            'measured_by' => $measurement->measured_by,
            'measured_at' => $measurement->measured_at?->toISOString(),
        ], 'Measurement added', 201);
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

        return $this->computeRecistAssessments($patient);
    }

    // ─── Shared RECIST computation ───────────────────────────────────────

    private function computeRecistAssessments(int $patientId): JsonResponse
    {
        $studies = ImagingStudy::where('patient_id', $patientId)
            ->orderBy('study_date', 'asc')
            ->with('imagingMeasurements')
            ->get();

        if ($studies->count() < 2) {
            return ApiResponse::success([], 'Insufficient studies for response assessment');
        }

        $assessments = [];
        $baseline = $studies->first();

        foreach ($studies->skip(1) as $current) {
            $baselineMeasurements = $baseline->imagingMeasurements
                ->where('target_lesion', true);
            $currentMeasurements = $current->imagingMeasurements
                ->where('target_lesion', true);

            $baselineSum = $baselineMeasurements->sum('value_numeric');
            $currentSum = $currentMeasurements->sum('value_numeric');

            $percentChange = null;
            $category = 'NE';

            if ($baselineSum > 0) {
                $percentChange = round((($currentSum - $baselineSum) / $baselineSum) * 100, 2);

                $absoluteChange = $currentSum - $baselineSum;
                $allDisappeared = $currentMeasurements->every(
                    fn ($m) => (float) $m->value_numeric === 0.0
                );

                if ($allDisappeared && $currentMeasurements->isNotEmpty()) {
                    $category = 'CR';
                } elseif ($percentChange <= -30.0) {
                    $category = 'PR';
                } elseif ($percentChange >= 20.0 && $absoluteChange >= 5.0) {
                    $category = 'PD';
                } else {
                    $category = 'SD';
                }
            }

            $assessments[] = [
                'baseline_study_id' => $baseline->id,
                'baseline_date' => $baseline->study_date?->toDateString(),
                'current_study_id' => $current->id,
                'current_date' => $current->study_date?->toDateString(),
                'criteria' => 'RECIST',
                'response_category' => $category,
                'percent_change' => $percentChange,
                'baseline_sum_diameters' => round((float) $baselineSum, 2),
                'current_sum_diameters' => round((float) $currentSum, 2),
            ];
        }

        return ApiResponse::success($assessments, 'Response assessments retrieved');
    }
}
