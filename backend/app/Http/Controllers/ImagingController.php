<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Jobs\Imaging\ImportLocalDicomJob;
use App\Jobs\Imaging\IndexDicomwebStudiesJob;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingCriteria;
use App\Models\Clinical\ImagingFeature;
use App\Models\Clinical\ImagingIngestionRun;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingResponseAssessment;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Services\Imaging\ImagingIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImagingController extends Controller
{
    public function __construct(private readonly ImagingIngestionService $ingestionService) {}

    // ─── Helper: format a study row for JSON ─────────────────────────────

    private function formatStudy(ImagingStudy $study): array
    {
        $isIndexed = $study->dicom_endpoint === 'orthanc'
            || $study->source_type === 'orthanc'
            || str_contains((string) $study->dicom_endpoint, 'dicom-web');

        return [
            'id' => $study->id,
            'patient_id' => $study->patient_id,
            'person_id' => $study->patient_id,
            'study_uid' => $study->study_uid,
            'study_instance_uid' => $study->study_uid,
            'modality' => $study->modality,
            'study_date' => $study->study_date?->toDateString(),
            'study_description' => $study->description,
            'description' => $study->description,
            'body_part' => $study->body_part,
            'body_part_examined' => $study->body_part,
            'laterality' => $study->laterality,
            'accession_number' => $study->accession_number,
            'num_series' => $study->num_series,
            'num_images' => $study->num_instances,
            'num_instances' => $study->num_instances,
            'dicom_endpoint' => $study->dicom_endpoint,
            'orthanc_study_id' => $isIndexed ? $study->study_uid : null,
            'wadors_uri' => $isIndexed ? '/orthanc/dicom-web' : null,
            'status' => $isIndexed ? 'indexed' : 'pending',
            'source_id' => $study->source_id,
            'source_type' => $study->source_type,
            'measurement_count' => $study->imagingMeasurements()->count(),
            'measurements_count' => $study->imagingMeasurements()->count(),
            'segmentation_count' => $study->segmentations()->count(),
            'created_at' => $study->created_at?->toISOString(),
            'updated_at' => $study->updated_at?->toISOString(),
        ];
    }

    private function orthancRequest()
    {
        $request = Http::baseUrl(rtrim((string) config('services.orthanc.base_url'), '/'))
            ->acceptJson()
            ->timeout(30);

        $user = config('services.orthanc.user');
        $password = config('services.orthanc.password');

        if ($user && $password) {
            $request = $request->withBasicAuth((string) $user, (string) $password);
        }

        return $request;
    }

    private function formatMeasurement(ImagingMeasurement $m): array
    {
        $study = $m->relationLoaded('imagingStudy') ? $m->imagingStudy : null;
        $seriesId = $m->imaging_series_id;

        if ($seriesId === null && $m->source_type === 'series' && is_numeric($m->source_id)) {
            $seriesId = (int) $m->source_id;
        }

        $value = (float) $m->value_numeric;
        $measurementName = $m->measurement_name ?: $m->measurement_type;
        $bodySite = $m->body_site ?: $study?->body_part;
        $algorithmName = $m->algorithm_name ?: $m->measured_by;

        return [
            'id' => $m->id,
            'imaging_study_id' => $m->imaging_study_id,
            'study_id' => $m->imaging_study_id,
            'person_id' => $study?->patient_id,
            'series_id' => $seriesId,
            'measurement_type' => $m->measurement_type,
            'measurement_name' => $measurementName,
            'target_lesion' => $m->target_lesion,
            'is_target_lesion' => $m->target_lesion,
            'target_lesion_number' => $m->target_lesion_number,
            'value_numeric' => $value,
            'value_as_number' => $value,
            'unit' => $m->unit,
            'body_site' => $bodySite,
            'laterality' => $m->laterality,
            'measured_by' => $m->measured_by,
            'algorithm_name' => $algorithmName,
            'confidence' => $m->confidence !== null ? (float) $m->confidence : null,
            'measured_at' => $m->measured_at?->toISOString(),
            'source_id' => $m->source_id,
            'source_type' => $m->source_type,
            'created_by' => null,
            'created_at' => $m->created_at?->toISOString(),
            'updated_at' => $m->updated_at?->toISOString(),
            'study' => $study ? [
                'id' => $study->id,
                'study_date' => $study->study_date?->toDateString(),
                'modality' => $study->modality,
                'body_part_examined' => $study->body_part,
            ] : null,
        ];
    }

    private function formatResponseAssessment(ImagingResponseAssessment $assessment): array
    {
        $baselineStudy = $assessment->relationLoaded('baselineStudy') ? $assessment->baselineStudy : null;
        $currentStudy = $assessment->relationLoaded('currentStudy') ? $assessment->currentStudy : null;

        return [
            'id' => $assessment->id,
            'person_id' => $assessment->patient_id,
            'criteria_type' => $assessment->criteria_type,
            'assessment_date' => $assessment->assessment_date?->toDateString(),
            'body_site' => $assessment->body_site,
            'baseline_study_id' => $assessment->baseline_study_id,
            'current_study_id' => $assessment->current_study_id,
            'baseline_value' => $assessment->baseline_value !== null ? (float) $assessment->baseline_value : null,
            'nadir_value' => $assessment->nadir_value !== null ? (float) $assessment->nadir_value : null,
            'current_value' => $assessment->current_value !== null ? (float) $assessment->current_value : null,
            'percent_change_from_baseline' => $assessment->percent_change_from_baseline !== null
                ? (float) $assessment->percent_change_from_baseline
                : null,
            'percent_change_from_nadir' => $assessment->percent_change_from_nadir !== null
                ? (float) $assessment->percent_change_from_nadir
                : null,
            'response_category' => $assessment->response_category,
            'rationale' => $assessment->rationale,
            'assessed_by' => $assessment->assessed_by,
            'is_confirmed' => $assessment->is_confirmed,
            'source_type' => $assessment->source_type,
            'source_id' => $assessment->source_id,
            'created_at' => $assessment->created_at?->toISOString(),
            'baseline_study' => $baselineStudy ? $this->formatStudy($baselineStudy) : null,
            'current_study' => $currentStudy ? $this->formatStudy($currentStudy) : null,
        ];
    }

    private function formatFeature(ImagingFeature $feature): array
    {
        return [
            'id' => $feature->id,
            'study_id' => $feature->imaging_study_id,
            'imaging_study_id' => $feature->imaging_study_id,
            'source_id' => $feature->source_id,
            'person_id' => $feature->patient_id,
            'patient_id' => $feature->patient_id,
            'feature_type' => $feature->feature_type,
            'algorithm_name' => $feature->algorithm_name,
            'feature_name' => $feature->feature_name,
            'feature_source_value' => $feature->feature_source_value,
            'value_as_number' => $feature->value_numeric !== null ? (float) $feature->value_numeric : null,
            'value_numeric' => $feature->value_numeric !== null ? (float) $feature->value_numeric : null,
            'value_as_string' => $feature->value_text,
            'value_text' => $feature->value_text,
            'value_concept_id' => $feature->value_concept_id,
            'unit_source_value' => $feature->unit,
            'unit' => $feature->unit,
            'body_site' => $feature->body_site,
            'confidence' => $feature->confidence !== null ? (float) $feature->confidence : null,
            'requires_review' => $feature->requires_review,
            'source_type' => $feature->source_type,
            'metadata' => $feature->metadata,
            'created_at' => $feature->created_at?->toISOString(),
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
        $totalFeatures = ImagingFeature::count();

        $modalityCounts = ImagingStudy::select('modality', DB::raw('count(*) as count'))
            ->whereNotNull('modality')
            ->groupBy('modality')
            ->pluck('count', 'modality');

        $bodyPartCounts = ImagingStudy::select('body_part', DB::raw('count(*) as count'))
            ->whereNotNull('body_part')
            ->groupBy('body_part')
            ->pluck('count', 'body_part');
        $featuresByType = ImagingFeature::select('feature_type', DB::raw('count(*) as count'))
            ->whereNotNull('feature_type')
            ->groupBy('feature_type')
            ->pluck('count', 'feature_type');

        return ApiResponse::success([
            'total_studies' => $totalStudies,
            'total_patients' => $totalPatients,
            'total_measurements' => $totalMeasurements,
            'modality_counts' => $modalityCounts,
            'body_part_counts' => $bodyPartCounts,
            'total_features' => $totalFeatures,
            'persons_with_imaging' => $totalPatients,
            'studies_by_modality' => $modalityCounts,
            'features_by_type' => $featuresByType,
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
            'series_instance_uid' => $s->series_uid,
            'series_number' => $s->series_number,
            'modality' => $s->modality,
            'description' => $s->description,
            'series_description' => $s->description,
            'num_instances' => $s->num_instances,
            'num_images' => $s->num_instances,
            'source_id' => $s->source_id,
            'source_type' => $s->source_type,
        ])->values();
        $data['measurements'] = $study->imagingMeasurements
            ->each->setRelation('imagingStudy', $study)
            ->map(fn ($m) => $this->formatMeasurement($m))
            ->values();
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

        if (! $study->study_uid) {
            return ApiResponse::error('Study has no StudyInstanceUID', 422);
        }

        try {
            $findResponse = $this->orthancRequest()->post('/tools/find', [
                'Level' => 'Study',
                'Query' => [
                    'StudyInstanceUID' => $study->study_uid,
                ],
            ]);

            if ($findResponse->failed()) {
                return ApiResponse::error('Orthanc study lookup failed', 502, [
                    'status' => $findResponse->status(),
                ]);
            }

            $orthancStudyIds = $findResponse->json();
            if (! is_array($orthancStudyIds) || empty($orthancStudyIds)) {
                return ApiResponse::error('Study not found in Orthanc', 404);
            }

            $orthancStudyId = (string) $orthancStudyIds[0];
            $studyResponse = $this->orthancRequest()->get('/studies/'.$orthancStudyId);

            if ($studyResponse->failed()) {
                return ApiResponse::error('Orthanc study metadata fetch failed', 502, [
                    'status' => $studyResponse->status(),
                ]);
            }

            $orthancStudy = $studyResponse->json();
            $seriesIds = is_array($orthancStudy) ? ($orthancStudy['Series'] ?? []) : [];
            if (! is_array($seriesIds)) {
                $seriesIds = [];
            }

            $indexed = 0;
            $updated = 0;
            $errors = 0;
            $totalInstances = 0;

            foreach ($seriesIds as $orthancSeriesId) {
                $seriesResponse = $this->orthancRequest()->get('/series/'.(string) $orthancSeriesId);

                if ($seriesResponse->failed()) {
                    $errors++;

                    continue;
                }

                $orthancSeries = $seriesResponse->json();
                $tags = is_array($orthancSeries) && is_array($orthancSeries['MainDicomTags'] ?? null)
                    ? $orthancSeries['MainDicomTags']
                    : [];
                $seriesUid = $tags['SeriesInstanceUID'] ?? null;

                if (! $seriesUid) {
                    $errors++;

                    continue;
                }

                $orthancInstances = is_array($orthancSeries) ? ($orthancSeries['Instances'] ?? null) : null;
                $instances = is_array($orthancInstances)
                    ? count($orthancInstances)
                    : null;
                $totalInstances += $instances ?? 0;

                $series = ImagingSeries::updateOrCreate(
                    ['series_uid' => $seriesUid],
                    [
                        'imaging_study_id' => $study->id,
                        'series_number' => isset($tags['SeriesNumber']) ? (int) $tags['SeriesNumber'] : null,
                        'modality' => $tags['Modality'] ?? null,
                        'description' => $tags['SeriesDescription'] ?? null,
                        'num_instances' => $instances,
                        'source_id' => (string) $orthancSeriesId,
                        'source_type' => 'orthanc',
                    ],
                );

                $series->wasRecentlyCreated ? $indexed++ : $updated++;
            }

            $study->forceFill([
                'num_series' => count($seriesIds),
                'num_instances' => $totalInstances > 0 ? $totalInstances : $study->num_instances,
                'dicom_endpoint' => 'orthanc',
            ])->save();

            return ApiResponse::success([
                'indexed' => $indexed,
                'updated' => $updated,
                'errors' => $errors,
                'series_total' => $indexed + $updated,
            ], 'Series indexed from Orthanc');
        } catch (\Throwable $e) {
            return ApiResponse::error('Unable to index series from Orthanc', 502, [
                'detail' => $e->getMessage(),
            ]);
        }
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

        $aiBaseUrl = rtrim((string) config('services.ai.base_url', 'http://localhost:8100'), '/');

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->post($aiBaseUrl.'/api/ai/imaging/extract-features', [
                    'study_id' => $study->id,
                ]);

            if ($response->failed()) {
                return ApiResponse::error('AI feature extraction failed', 502, [
                    'status' => $response->status(),
                ]);
            }

            $features = $response->json('features');

            if (! is_array($features)) {
                return ApiResponse::error('AI feature extraction returned an invalid payload', 502);
            }

            ImagingFeature::where('imaging_study_id', $study->id)
                ->where('source_type', 'ai_feature_extraction')
                ->delete();

            $created = collect($features)
                ->filter(fn (mixed $feature) => is_array($feature))
                ->reject(fn (array $feature) => ($feature['confidence'] ?? null) === 0.0
                    && ($feature['feature_name'] ?? '') === 'No measurements available')
                ->map(function (array $feature) use ($study) {
                    return ImagingFeature::create([
                        'imaging_study_id' => $study->id,
                        'patient_id' => $study->patient_id,
                        'feature_type' => (string) ($feature['category'] ?? 'other'),
                        'algorithm_name' => 'aurora-ai-nlp',
                        'feature_name' => (string) ($feature['feature_name'] ?? 'Imaging feature'),
                        'feature_source_value' => isset($feature['value']) ? (string) $feature['value'] : null,
                        'value_text' => isset($feature['value']) ? (string) $feature['value'] : null,
                        'body_site' => $study->body_part,
                        'confidence' => is_numeric($feature['confidence'] ?? null) ? (float) $feature['confidence'] : null,
                        'requires_review' => true,
                        'source_type' => 'ai_feature_extraction',
                        'source_id' => 'ai-feature:'.$study->id.':'.md5(json_encode($feature)),
                        'metadata' => [
                            'study_uid' => $study->study_uid,
                        ],
                    ]);
                })
                ->values();

            return ApiResponse::success([
                'extracted' => $created->count(),
                'mapped' => $created->count(),
                'errors' => 0,
                'features' => $created->map(fn (ImagingFeature $feature) => $this->formatFeature($feature))->values(),
            ], $created->isEmpty() ? 'No imaging features extracted' : 'Imaging features extracted');
        } catch (\Throwable $e) {
            return ApiResponse::error('Unable to extract imaging features', 502, [
                'detail' => $e->getMessage(),
            ]);
        }
    }

    // =====================================================================
    //  7. GET /imaging/features
    // =====================================================================

    public function features(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 25)), 100);
        $query = ImagingFeature::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($request->filled('study_id')) {
            $query->where('imaging_study_id', (int) $request->input('study_id'));
        }

        if ($request->filled('person_id')) {
            $query->where('patient_id', (int) $request->input('person_id'));
        }

        if ($request->filled('feature_type')) {
            $query->where('feature_type', (string) $request->input('feature_type'));
        }

        $paginator = $query->paginate($perPage);

        $mapped = new LengthAwarePaginator(
            collect($paginator->items())->map(fn (ImagingFeature $feature) => $this->formatFeature($feature)),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
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
        $modality = $request->input('modality');

        $query = ImagingStudy::query();
        if ($modality) {
            $query->where('modality', $modality);
        }

        $totalStudies = $query->count();
        $totalPatients = (clone $query)->distinct()->count('patient_id');

        $byModality = ImagingStudy::select(
            'modality',
            DB::raw('count(*) as n'),
            DB::raw('count(distinct patient_id) as unique_persons')
        )
            ->whereNotNull('modality')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('modality')
            ->orderByDesc('n')
            ->get()
            ->map(fn (ImagingStudy $row) => [
                'modality' => $row->modality,
                'n' => (int) $row->n,
                'unique_persons' => (int) $row->unique_persons,
            ])
            ->values();

        $byBodyPart = ImagingStudy::select('body_part', DB::raw('count(*) as n'))
            ->whereNotNull('body_part')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('body_part')
            ->orderByDesc('n')
            ->get()
            ->map(fn (ImagingStudy $row) => [
                'body_part_examined' => $row->body_part,
                'n' => (int) $row->n,
            ])
            ->values();

        return ApiResponse::success([
            'total_studies' => $totalStudies,
            'total_patients' => $totalPatients,
            'by_modality' => $byModality,
            'by_body_part' => $byBodyPart,
            'top_features' => ImagingFeature::select('feature_name', 'feature_type', DB::raw('count(*) as n'))
                ->when($modality, function ($q) use ($modality) {
                    $studyIds = ImagingStudy::where('modality', $modality)->pluck('id');

                    return $q->whereIn('imaging_study_id', $studyIds);
                })
                ->groupBy('feature_name', 'feature_type')
                ->orderByDesc('n')
                ->limit(10)
                ->get()
                ->map(fn (ImagingFeature $row) => [
                    'feature_name' => $row->feature_name,
                    'feature_type' => $row->feature_type,
                    'n' => (int) $row->n,
                ])
                ->values(),
            'modality_distribution' => $byModality->mapWithKeys(fn (array $row) => [$row['modality'] => $row['n']]),
            'body_part_distribution' => $byBodyPart->mapWithKeys(fn (array $row) => [$row['body_part_examined'] => $row['n']]),
            'temporal_distribution' => [],
        ], 'Population analytics retrieved');
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

        $studies = ImagingStudy::where('patient_id', $personId)
            ->orderBy('study_date', 'asc')
            ->with('imagingMeasurements')
            ->get();

        $timelineStudies = $studies->map(fn (ImagingStudy $s) => [
            'id' => $s->id,
            'study_instance_uid' => $s->study_uid,
            'study_date' => $s->study_date?->toDateString(),
            'modality' => $s->modality,
            'body_part' => $s->body_part,
            'body_part_examined' => $s->body_part,
            'study_description' => $s->description,
            'num_series' => $s->num_series ?? 0,
            'num_images' => $s->num_instances ?? 0,
            'status' => ($s->dicom_endpoint === 'orthanc' || $s->source_type === 'orthanc') ? 'indexed' : 'pending',
            'measurement_count' => $s->imagingMeasurements->count(),
        ])->values();

        $events = $studies->map(fn (ImagingStudy $s) => [
            'study_id' => $s->id,
            'study_date' => $s->study_date?->toDateString(),
            'modality' => $s->modality,
            'description' => $s->description,
            'body_part' => $s->body_part,
            'measurement_count' => $s->imagingMeasurements->count(),
        ])->values();

        $measurements = $studies
            ->flatMap(fn (ImagingStudy $s) => $s->imagingMeasurements->each->setRelation('imagingStudy', $s))
            ->sortByDesc(fn (ImagingMeasurement $m) => $m->measured_at?->timestamp ?? 0)
            ->values()
            ->map(fn (ImagingMeasurement $m) => $this->formatMeasurement($m));

        $drugExposures = DB::table('drug_eras')
            ->where('patient_id', $personId)
            ->orderBy('era_start')
            ->get()
            ->map(function ($drug) {
                $start = $drug->era_start ? strtotime((string) $drug->era_start) : null;
                $end = $drug->era_end ? strtotime((string) $drug->era_end) : null;

                return [
                    'drug_concept_id' => 0,
                    'drug_name' => $drug->drug_name,
                    'drug_class' => null,
                    'start_date' => $drug->era_start,
                    'end_date' => $drug->era_end,
                    'total_days' => ($start && $end) ? max(0, (int) floor(($end - $start) / 86400)) : 0,
                ];
            })
            ->values();

        $studyDates = $studies->pluck('study_date')->filter();
        $firstStudyDate = $studyDates->min()?->toDateString();
        $lastStudyDate = $studyDates->max()?->toDateString();

        return ApiResponse::success([
            'person_id' => $personId,
            'events' => $events,
            'person' => [
                'person_id' => $personId,
                'year_of_birth' => $patient->date_of_birth?->year,
                'gender' => $patient->sex,
                'race' => $patient->race,
            ],
            'studies' => $timelineStudies,
            'drug_exposures' => $drugExposures,
            'measurements' => $measurements,
            'summary' => [
                'total_studies' => $studies->count(),
                'modalities' => $studies->pluck('modality')->filter()->unique()->values(),
                'date_range' => [
                    'first' => $firstStudyDate,
                    'last' => $lastStudyDate,
                ],
                'total_measurements' => $measurements->count(),
                'measurement_types' => $measurements->pluck('measurement_type')->filter()->unique()->values(),
                'total_drugs' => $drugExposures->count(),
                'imaging_span_days' => ($firstStudyDate && $lastStudyDate)
                    ? max(0, (int) floor((strtotime($lastStudyDate) - strtotime($firstStudyDate)) / 86400))
                    : null,
            ],
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

        $measurements = $study->imagingMeasurements()
            ->with('imagingStudy')
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

        $seriesId = $validated['series_id'] ?? null;
        if ($seriesId !== null) {
            $series = ImagingSeries::where('id', $seriesId)
                ->where('imaging_study_id', $study->id)
                ->first();

            if (! $series) {
                return ApiResponse::error('Series not found for this study', 422);
            }
        }

        $algorithmName = $validated['algorithm_name'] ?? null;

        $measurement = ImagingMeasurement::create([
            'imaging_study_id' => $study->id,
            'imaging_series_id' => $seriesId,
            'measurement_type' => $validated['measurement_type'],
            'measurement_name' => $validated['measurement_name'] ?? $validated['measurement_type'],
            'target_lesion' => $validated['is_target_lesion'] ?? false,
            'target_lesion_number' => $validated['target_lesion_number'] ?? null,
            'value_numeric' => $validated['value_as_number'],
            'unit' => $validated['unit'],
            'body_site' => $validated['body_site'] ?? null,
            'laterality' => $validated['laterality'] ?? null,
            'measured_by' => $algorithmName,
            'algorithm_name' => $algorithmName,
            'confidence' => $validated['confidence'] ?? null,
            'measured_at' => $validated['measured_at'] ?? now(),
            'source_id' => $seriesId,
            'source_type' => $seriesId ? 'series' : 'manual',
        ]);

        return ApiResponse::success($this->formatMeasurement($measurement->load('imagingStudy')), 'Measurement created', 201);
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

        $updates = [];

        if (array_key_exists('measurement_type', $validated)) {
            $updates['measurement_type'] = $validated['measurement_type'];
        }
        if (array_key_exists('measurement_name', $validated)) {
            $updates['measurement_name'] = $validated['measurement_name'];
        }
        if (array_key_exists('value_as_number', $validated)) {
            $updates['value_numeric'] = $validated['value_as_number'];
        } elseif (array_key_exists('value_numeric', $validated)) {
            $updates['value_numeric'] = $validated['value_numeric'];
        }
        if (array_key_exists('unit', $validated)) {
            $updates['unit'] = $validated['unit'];
        }
        if (array_key_exists('body_site', $validated)) {
            $updates['body_site'] = $validated['body_site'];
        }
        if (array_key_exists('laterality', $validated)) {
            $updates['laterality'] = $validated['laterality'];
        }
        if (array_key_exists('series_id', $validated)) {
            $seriesId = $validated['series_id'];
            if ($seriesId !== null) {
                $series = ImagingSeries::where('id', $seriesId)
                    ->where('imaging_study_id', $measurement->imaging_study_id)
                    ->first();

                if (! $series) {
                    return ApiResponse::error('Series not found for this study', 422);
                }
            }
            $updates['imaging_series_id'] = $seriesId;
            $updates['source_id'] = $seriesId;
            $updates['source_type'] = $seriesId ? 'series' : 'manual';
        }
        if (array_key_exists('is_target_lesion', $validated)) {
            $updates['target_lesion'] = $validated['is_target_lesion'];
        } elseif (array_key_exists('target_lesion', $validated)) {
            $updates['target_lesion'] = $validated['target_lesion'];
        }
        if (array_key_exists('target_lesion_number', $validated)) {
            $updates['target_lesion_number'] = $validated['target_lesion_number'];
        }
        if (array_key_exists('measured_by', $validated)) {
            $updates['measured_by'] = $validated['measured_by'];
        }
        if (array_key_exists('algorithm_name', $validated)) {
            $updates['algorithm_name'] = $validated['algorithm_name'];
            $updates['measured_by'] = $validated['algorithm_name'];
        }
        if (array_key_exists('confidence', $validated)) {
            $updates['confidence'] = $validated['confidence'];
        }
        if (array_key_exists('measured_at', $validated)) {
            $updates['measured_at'] = $validated['measured_at'];
        }

        if (! empty($updates)) {
            $measurement->update($updates);
            $measurement->refresh();
        }

        return ApiResponse::success($this->formatMeasurement($measurement->load('imagingStudy')), 'Measurement updated');
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
            ->with('imagingStudy')
            ->orderBy('measured_at', 'desc');

        if ($request->filled('measurement_type')) {
            $query->where('measurement_type', $request->input('measurement_type'));
        }

        if ($request->filled('body_site')) {
            $filteredStudyIds = ImagingStudy::where('patient_id', $personId)
                ->where('body_part', $request->input('body_site'))
                ->pluck('id');
            $query->where(function ($q) use ($request, $filteredStudyIds) {
                $q->where('body_site', $request->input('body_site'))
                    ->orWhereIn('imaging_study_id', $filteredStudyIds);
            });
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
            $query->where(function ($q) use ($request, $filteredStudyIds) {
                $q->where('body_site', $request->input('body_site'))
                    ->orWhereIn('imaging_study_id', $filteredStudyIds);
            });
        }

        $measurements = $query->with('imagingStudy')->get();

        $trends = $measurements->map(fn (ImagingMeasurement $m) => [
            'measurement_id' => $m->id,
            'study_id' => $m->imaging_study_id,
            'date' => $m->imagingStudy?->study_date?->toDateString() ?? $m->measured_at?->toDateString(),
            'study_date' => $m->imagingStudy?->study_date?->toDateString(),
            'measurement_type' => $m->measurement_type,
            'measurement_name' => $m->measurement_name ?: $m->measurement_type,
            'value_numeric' => (float) $m->value_numeric,
            'value' => (float) $m->value_numeric,
            'unit' => $m->unit,
            'body_site' => $m->body_site ?: $m->imagingStudy?->body_part,
            'is_target_lesion' => $m->target_lesion,
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

        $stored = ImagingResponseAssessment::where('patient_id', $personId)
            ->with(['baselineStudy', 'currentStudy'])
            ->orderByDesc('assessment_date')
            ->orderByDesc('id')
            ->get();

        if ($stored->isNotEmpty()) {
            return ApiResponse::success(
                $stored->map(fn (ImagingResponseAssessment $a) => $this->formatResponseAssessment($a))->values(),
                'Response assessments retrieved'
            );
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

        $baselineStudy = ImagingStudy::where('id', $validated['baseline_study_id'])
            ->where('patient_id', $personId)
            ->first();

        if (! $baselineStudy) {
            return ApiResponse::error('Baseline study not found for this patient', 404);
        }

        $currentStudy = ImagingStudy::where('id', $validated['current_study_id'])
            ->where('patient_id', $personId)
            ->first();

        if (! $currentStudy) {
            return ApiResponse::error('Current study not found for this patient', 404);
        }

        $assessment = ImagingResponseAssessment::create(array_merge($validated, [
            'patient_id' => $personId,
            'assessed_by' => $request->user()?->id,
            'is_confirmed' => $validated['is_confirmed'] ?? false,
            'source_type' => 'manual',
        ]))->load(['baselineStudy', 'currentStudy']);

        return ApiResponse::success($this->formatResponseAssessment($assessment), 'Response assessment created', 201);
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

        $criteriaType = $validated['criteria_type'] ?? 'recist';
        if ($criteriaType === 'auto') {
            $criteriaType = 'recist';
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

        $assessment = ImagingResponseAssessment::updateOrCreate([
            'patient_id' => $personId,
            'criteria_type' => $criteriaType,
            'baseline_study_id' => $baselineStudy->id,
            'current_study_id' => $currentStudy->id,
            'source_type' => 'computed',
        ], [
            'assessment_date' => now()->toDateString(),
            'body_site' => $currentStudy->body_part,
            'baseline_value' => round((float) $baselineSum, 2),
            'nadir_value' => null,
            'current_value' => round((float) $currentSum, 2),
            'percent_change_from_baseline' => $percentChange,
            'percent_change_from_nadir' => null,
            'response_category' => $category,
            'rationale' => "Computed via {$criteriaType}: baseline sum={$baselineSum}, current sum={$currentSum}",
            'is_confirmed' => false,
            'assessed_by' => $request->user()?->id,
            'source_id' => "baseline:{$baselineStudy->id};current:{$currentStudy->id}",
        ])->load(['baselineStudy', 'currentStudy']);

        return ApiResponse::success($this->formatResponseAssessment($assessment), 'Response computed');
    }

    private function computedResponsePayload(
        int $personId,
        ImagingStudy $baseline,
        ImagingStudy $current,
        string $criteriaType,
        string $category,
        float|int $baselineSum,
        float|int $currentSum,
        ?float $percentChange
    ): array {
        return [
            'id' => -$current->id,
            'person_id' => $personId,
            'criteria_type' => $criteriaType,
            'assessment_date' => $current->study_date?->toDateString() ?? now()->toDateString(),
            'body_site' => $current->body_part,
            'baseline_study_id' => $baseline->id,
            'baseline_date' => $baseline->study_date?->toDateString(),
            'current_study_id' => $current->id,
            'current_date' => $current->study_date?->toDateString(),
            'response_category' => $category,
            'baseline_value' => round((float) $baselineSum, 2),
            'nadir_value' => null,
            'current_value' => round((float) $currentSum, 2),
            'percent_change_from_baseline' => $percentChange,
            'percent_change_from_nadir' => null,
            'rationale' => "Computed via {$criteriaType}: baseline sum={$baselineSum}, current sum={$currentSum}",
            'is_confirmed' => false,
            'created_at' => null,
            'criteria' => strtoupper($criteriaType),
            'percent_change' => $percentChange,
            'baseline_sum_diameters' => round((float) $baselineSum, 2),
            'current_sum_diameters' => round((float) $currentSum, 2),
        ];
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
        $aiBaseUrl = rtrim((string) config('services.ai.base_url', 'http://localhost:8100'), '/');

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->post($aiBaseUrl.'/api/ai/imaging/volume', [
                    'study_id' => $study->id,
                    'measurement_type' => $measurementType,
                ]);

            if ($response->failed()) {
                return ApiResponse::error('AI measurement extraction failed', 502, [
                    'status' => $response->status(),
                ]);
            }

            $payload = $response->json();
            $measurements = collect();

            if (is_numeric($payload['volume_cm3'] ?? null)) {
                $measurements->push(ImagingMeasurement::updateOrCreate([
                    'imaging_study_id' => $study->id,
                    'source_type' => 'ai_extraction',
                    'source_id' => 'ai-volume:'.$study->id.':'.$measurementType,
                ], [
                    'measurement_type' => $measurementType,
                    'measurement_name' => $measurementType === 'organ_volume' ? 'Organ volume' : 'Tumor volume',
                    'target_lesion' => $measurementType === 'tumor_volume',
                    'value_numeric' => (float) $payload['volume_cm3'],
                    'unit' => 'cm3',
                    'body_site' => $study->body_part,
                    'measured_by' => 'aurora-ai',
                    'algorithm_name' => 'aurora-ai-volumetric',
                    'confidence' => null,
                    'measured_at' => now(),
                ]));
            }

            if (is_numeric($payload['longest_diameter_mm'] ?? null)) {
                $measurements->push(ImagingMeasurement::updateOrCreate([
                    'imaging_study_id' => $study->id,
                    'source_type' => 'ai_extraction',
                    'source_id' => 'ai-longest-diameter:'.$study->id.':'.$measurementType,
                ], [
                    'measurement_type' => 'longest_diameter',
                    'measurement_name' => 'Longest diameter',
                    'target_lesion' => true,
                    'value_numeric' => (float) $payload['longest_diameter_mm'],
                    'unit' => 'mm',
                    'body_site' => $study->body_part,
                    'measured_by' => 'aurora-ai',
                    'algorithm_name' => 'aurora-ai-volumetric',
                    'confidence' => null,
                    'measured_at' => now(),
                ]));
            }

            if ($measurements->isEmpty()) {
                return ApiResponse::error('AI service did not return extractable measurements for this study', 422, [
                    'ai_payload' => $payload,
                ]);
            }

            $measurementPayloads = $measurements
                ->map(fn (ImagingMeasurement $measurement) => $this->formatMeasurement($measurement->load('imagingStudy')))
                ->values();

            return ApiResponse::success([
                'extracted' => $measurementPayloads->count(),
                'measurement_types' => $measurementPayloads->pluck('measurement_type')->unique()->values(),
                'measurements' => $measurementPayloads,
                'requires_review' => true,
            ], 'AI measurements extracted');
        } catch (\Throwable $e) {
            return ApiResponse::error('Unable to extract AI measurements', 502, [
                'detail' => $e->getMessage(),
            ]);
        }
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

        $modality = strtoupper((string) $study->modality);
        $bodyPart = strtolower((string) $study->body_part);
        $template = 'general';
        $fields = [
            ['type' => 'longest_diameter', 'name' => 'Longest Diameter', 'unit' => 'mm'],
        ];

        if ($modality === 'PT' || str_contains($bodyPart, 'lymph')) {
            $template = 'pet-lymphoma';
            $fields = [
                ['type' => 'suvmax', 'name' => 'SUVmax', 'unit' => 'g/mL'],
                ['type' => 'metabolic_tumor_volume', 'name' => 'Metabolic Tumor Volume', 'unit' => 'cm3'],
                ['type' => 'total_lesion_glycolysis', 'name' => 'Total Lesion Glycolysis', 'unit' => 'g'],
            ];
        } elseif ($modality === 'CT' && str_contains($bodyPart, 'chest')) {
            $template = 'ct-chest-recist';
            $fields = [
                ['type' => 'longest_diameter', 'name' => 'Target Lesion Longest Diameter', 'unit' => 'mm'],
                ['type' => 'perpendicular_diameter', 'name' => 'Perpendicular Diameter', 'unit' => 'mm'],
                ['type' => 'density_hu', 'name' => 'Density', 'unit' => 'HU'],
            ];
        } elseif (in_array($modality, ['MR', 'MRI'], true) && str_contains($bodyPart, 'brain')) {
            $template = 'brain-rano';
            $fields = [
                ['type' => 'longest_diameter', 'name' => 'Enhancing Lesion Longest Diameter', 'unit' => 'mm'],
                ['type' => 'perpendicular_diameter', 'name' => 'Perpendicular Diameter', 'unit' => 'mm'],
                ['type' => 'tumor_volume', 'name' => 'Tumor Volume', 'unit' => 'cm3'],
            ];
        } elseif (str_contains($bodyPart, 'abdomen') || str_contains($bodyPart, 'liver')) {
            $template = 'abdominal-tumor-volumetrics';
            $fields = [
                ['type' => 'tumor_volume', 'name' => 'Tumor Volume', 'unit' => 'cm3'],
                ['type' => 'longest_diameter', 'name' => 'Longest Diameter', 'unit' => 'mm'],
                ['type' => 'enhancement_ratio', 'name' => 'Enhancement Ratio', 'unit' => 'ratio'],
            ];
        }

        return ApiResponse::success([
            'template' => $template,
            'fields' => $fields,
            'rationale' => "Suggested from modality={$study->modality} and body_part={$study->body_part}",
        ], 'Measurement template suggested');
    }

    public function ingestionRuns(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 25)), 100);
        $query = ImagingIngestionRun::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('run_type')) {
            $query->where('run_type', (string) $request->input('run_type'));
        }

        $paginator = $query->paginate($perPage);
        $mapped = new LengthAwarePaginator(
            collect($paginator->items())->map(fn (ImagingIngestionRun $run) => $this->ingestionService->runPayload($run)),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );

        return ApiResponse::paginated($mapped, 'Imaging ingestion runs retrieved');
    }

    public function ingestionRunShow(int $id): JsonResponse
    {
        $run = ImagingIngestionRun::find($id);

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

        $seriesId = $validated['series_id'] ?? null;
        if ($seriesId !== null) {
            $series = ImagingSeries::where('id', $seriesId)
                ->where('imaging_study_id', $studyModel->id)
                ->first();

            if (! $series) {
                return ApiResponse::error('Series not found for this study', 422);
            }
        }

        $algorithmName = $validated['algorithm_name'] ?? $validated['measured_by'] ?? null;
        $value = $validated['value_as_number'] ?? $validated['value_numeric'];

        $measurement = ImagingMeasurement::create([
            'imaging_study_id' => $studyModel->id,
            'imaging_series_id' => $seriesId,
            'measurement_type' => $validated['measurement_type'],
            'measurement_name' => $validated['measurement_name'] ?? $validated['measurement_type'],
            'target_lesion' => $validated['is_target_lesion'] ?? $validated['target_lesion'] ?? false,
            'target_lesion_number' => $validated['target_lesion_number'] ?? null,
            'value_numeric' => $value,
            'unit' => $validated['unit'],
            'body_site' => $validated['body_site'] ?? null,
            'laterality' => $validated['laterality'] ?? null,
            'measured_by' => $algorithmName,
            'algorithm_name' => $algorithmName,
            'confidence' => $validated['confidence'] ?? null,
            'measured_at' => $validated['measured_at'] ?? now(),
            'source_id' => $seriesId,
            'source_type' => $seriesId ? 'series' : 'manual',
        ]);

        return ApiResponse::success($this->formatMeasurement($measurement->load('imagingStudy')), 'Measurement added', 201);
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

            $assessments[] = $this->computedResponsePayload(
                $patientId,
                $baseline,
                $current,
                'recist',
                $category,
                $baselineSum,
                $currentSum,
                $percentChange
            );
        }

        return ApiResponse::success($assessments, 'Response assessments retrieved');
    }
}
