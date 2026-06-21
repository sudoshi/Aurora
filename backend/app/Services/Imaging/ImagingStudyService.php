<?php

namespace App\Services\Imaging;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingFeature;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Study listing, detail, series indexing, feature extraction, linking, and
 * patient-scoped study/timeline read models. Pure business logic; the
 * controller stays an HTTP adapter. Methods return arrays/collections or
 * structured result tuples — never JsonResponse — so error handling stays in
 * the controller and the response shapes are unchanged.
 */
class ImagingStudyService
{
    public function __construct(private readonly ImagingFormatter $formatter) {}

    public function stats(): array
    {
        $totalStudies = ImagingStudy::count();
        $totalPatients = ImagingStudy::distinct()->count('patient_id');
        $totalMeasurements = \App\Models\Clinical\ImagingMeasurement::count();
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

        return [
            'total_studies' => $totalStudies,
            'total_patients' => $totalPatients,
            'total_measurements' => $totalMeasurements,
            'modality_counts' => $modalityCounts,
            'body_part_counts' => $bodyPartCounts,
            'total_features' => $totalFeatures,
            'persons_with_imaging' => $totalPatients,
            'studies_by_modality' => $modalityCounts,
            'features_by_type' => $featuresByType,
        ];
    }

    public function paginateStudies(?string $modality, ?int $personId, int $perPage): LengthAwarePaginator
    {
        // Eager-load measurement/segmentation counts so formatStudy() reads the
        // *_count attributes instead of issuing a count() per study (N+1).
        $query = ImagingStudy::withCount(['imagingMeasurements', 'segmentations'])
            ->orderBy('study_date', 'desc');

        if ($modality !== null) {
            $query->where('modality', $modality);
        }

        if ($personId !== null) {
            $query->where('patient_id', $personId);
        }

        $paginator = $query->paginate($perPage);

        return new LengthAwarePaginator(
            collect($paginator->items())->map(fn (ImagingStudy $s) => $this->formatter->formatStudy($s)),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );
    }

    public function findStudyDetail(int $id): ?array
    {
        $study = ImagingStudy::with(['series', 'imagingMeasurements', 'segmentations'])->find($id);

        if (! $study) {
            return null;
        }

        $data = $this->formatter->formatStudy($study);
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
            ->map(fn ($m) => $this->formatter->formatMeasurement($m))
            ->values();
        $data['segmentations'] = $study->segmentations->map(fn ($seg) => [
            'id' => $seg->id,
            'segmentation_uid' => $seg->segmentation_uid,
            'algorithm' => $seg->algorithm,
            'label' => $seg->label,
            'volume_mm3' => $seg->volume_mm3,
            'created_at' => $seg->created_at?->toISOString(),
        ])->values();

        return $data;
    }

    public function orthancRequest(): PendingRequest
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

    /**
     * Index series for a study from Orthanc.
     *
     * @return array{ok: bool, status?: int, message?: string, data?: array, detail?: string}
     */
    public function indexSeries(ImagingStudy $study): array
    {
        if (! $study->study_uid) {
            return ['ok' => false, 'status' => 422, 'message' => 'Study has no StudyInstanceUID'];
        }

        try {
            $findResponse = $this->orthancRequest()->post('/tools/find', [
                'Level' => 'Study',
                'Query' => [
                    'StudyInstanceUID' => $study->study_uid,
                ],
            ]);

            if ($findResponse->failed()) {
                return ['ok' => false, 'status' => 502, 'message' => 'Orthanc study lookup failed', 'extra' => ['status' => $findResponse->status()]];
            }

            $orthancStudyIds = $findResponse->json();
            if (! is_array($orthancStudyIds) || empty($orthancStudyIds)) {
                return ['ok' => false, 'status' => 404, 'message' => 'Study not found in Orthanc'];
            }

            $orthancStudyId = (string) $orthancStudyIds[0];
            $studyResponse = $this->orthancRequest()->get('/studies/'.$orthancStudyId);

            if ($studyResponse->failed()) {
                return ['ok' => false, 'status' => 502, 'message' => 'Orthanc study metadata fetch failed', 'extra' => ['status' => $studyResponse->status()]];
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

            return ['ok' => true, 'data' => [
                'indexed' => $indexed,
                'updated' => $updated,
                'errors' => $errors,
                'series_total' => $indexed + $updated,
            ]];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 502, 'message' => 'Unable to index series from Orthanc', 'detail' => $e->getMessage()];
        }
    }

    /**
     * Run NLP feature extraction for a study via the AI service.
     *
     * @return array{ok: bool, status?: int, message?: string, data?: array, detail?: string}
     */
    public function extractNlp(ImagingStudy $study): array
    {
        $aiBaseUrl = rtrim((string) config('services.ai.base_url', 'http://localhost:8100'), '/');

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->post($aiBaseUrl.'/api/ai/imaging/extract-features', [
                    'study_id' => $study->id,
                ]);

            if ($response->failed()) {
                return ['ok' => false, 'status' => 502, 'message' => 'AI feature extraction failed', 'extra' => ['status' => $response->status()]];
            }

            $features = $response->json('features');

            if (! is_array($features)) {
                return ['ok' => false, 'status' => 502, 'message' => 'AI feature extraction returned an invalid payload'];
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

            return ['ok' => true, 'data' => [
                'extracted' => $created->count(),
                'mapped' => $created->count(),
                'errors' => 0,
                'features' => $created->map(fn (ImagingFeature $feature) => $this->formatter->formatFeature($feature))->values(),
            ], 'message' => $created->isEmpty() ? 'No imaging features extracted' : 'Imaging features extracted'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 502, 'message' => 'Unable to extract imaging features', 'detail' => $e->getMessage()];
        }
    }

    public function paginateFeatures(?int $studyId, ?int $personId, ?string $featureType, int $perPage): LengthAwarePaginator
    {
        $query = ImagingFeature::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($studyId !== null) {
            $query->where('imaging_study_id', $studyId);
        }

        if ($personId !== null) {
            $query->where('patient_id', $personId);
        }

        if ($featureType !== null) {
            $query->where('feature_type', $featureType);
        }

        $paginator = $query->paginate($perPage);

        return new LengthAwarePaginator(
            collect($paginator->items())->map(fn (ImagingFeature $feature) => $this->formatter->formatFeature($feature)),
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );
    }

    public function patientTimeline(ClinicalPatient $patient, int $personId): array
    {
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
            ->sortByDesc(fn ($m) => $m->measured_at?->timestamp ?? 0)
            ->values()
            ->map(fn ($m) => $this->formatter->formatMeasurement($m));

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

        return [
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
        ];
    }

    public function patientStudies(int $personId): Collection
    {
        return ImagingStudy::where('patient_id', $personId)
            ->withCount(['imagingMeasurements', 'segmentations'])
            ->orderBy('study_date', 'desc')
            ->get()
            ->map(fn (ImagingStudy $s) => $this->formatter->formatStudy($s));
    }

    public function patientsWithImaging(int $minStudies, ?string $modality, int $perPage): LengthAwarePaginator
    {
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

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
        );
    }

    public function linkStudyToPerson(ImagingStudy $study, int $personId): array
    {
        $study->patient_id = $personId;
        $study->save();

        return $this->formatter->formatStudy($study->fresh());
    }

    public function bulkLinkStudies(array $studyIds, int $personId): int
    {
        return ImagingStudy::whereIn('id', $studyIds)
            ->update(['patient_id' => $personId]);
    }

    public function suggestTemplate(ImagingStudy $study): array
    {
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

        return [
            'template' => $template,
            'fields' => $fields,
            'rationale' => "Suggested from modality={$study->modality} and body_part={$study->body_part}",
        ];
    }

    // ─── Legacy patient-scoped read models ───────────────────────────────

    public function legacyIndex(int $patient, ?string $modality, ?string $bodyPart): Collection
    {
        $query = ImagingStudy::where('patient_id', $patient)
            ->orderBy('study_date', 'desc');

        if ($modality !== null) {
            $query->where('modality', $modality);
        }

        if ($bodyPart !== null) {
            $query->where('body_part', $bodyPart);
        }

        return $query->get()->map(fn (ImagingStudy $study) => [
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
    }

    public function legacyShow(ImagingStudy $studyModel): array
    {
        return [
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
    }
}
