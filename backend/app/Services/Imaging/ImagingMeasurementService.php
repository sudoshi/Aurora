<?php

namespace App\Services\Imaging;

use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Measurement read/create/update/delete, patient measurement listings,
 * trends, and AI volumetric extraction. Returns arrays/collections or result
 * tuples; the controller maps these onto ApiResponse and owns validation.
 */
class ImagingMeasurementService
{
    public function __construct(private readonly ImagingFormatter $formatter) {}

    public function studyMeasurements(ImagingStudy $study): Collection
    {
        return $study->imagingMeasurements()
            ->with('imagingStudy')
            ->orderBy('measured_at', 'desc')
            ->get()
            ->map(fn (ImagingMeasurement $m) => $this->formatter->formatMeasurement($m));
    }

    /**
     * Create a measurement on a study from the validated /studies/{id}/measurements payload.
     *
     * @return array{ok: bool, status?: int, message?: string, data?: array}
     */
    public function createStudyMeasurement(ImagingStudy $study, array $validated): array
    {
        $seriesId = $validated['series_id'] ?? null;
        if ($seriesId !== null) {
            $series = ImagingSeries::where('id', $seriesId)
                ->where('imaging_study_id', $study->id)
                ->first();

            if (! $series) {
                return ['ok' => false, 'status' => 422, 'message' => 'Series not found for this study'];
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

        return ['ok' => true, 'data' => $this->formatter->formatMeasurement($measurement->load('imagingStudy'))];
    }

    /**
     * Apply a partial update to a measurement from the validated payload.
     *
     * @return array{ok: bool, status?: int, message?: string, data?: array}
     */
    public function updateMeasurement(ImagingMeasurement $measurement, array $validated): array
    {
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
                    return ['ok' => false, 'status' => 422, 'message' => 'Series not found for this study'];
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

        return ['ok' => true, 'data' => $this->formatter->formatMeasurement($measurement->load('imagingStudy'))];
    }

    public function patientMeasurements(int $personId, Request $request): Collection
    {
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

        return $query->get()->map(fn (ImagingMeasurement $m) => $this->formatter->formatMeasurement($m));
    }

    public function measurementTrends(int $personId, string $measurementType, Request $request): Collection
    {
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

        return $measurements->map(fn (ImagingMeasurement $m) => [
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
    }

    /**
     * Extract AI volumetric measurements for a study.
     *
     * @return array{ok: bool, status?: int, message?: string, extra?: array, data?: array, detail?: string}
     */
    public function aiExtractMeasurements(ImagingStudy $study, string $measurementType): array
    {
        $aiBaseUrl = rtrim((string) config('services.ai.base_url', 'http://localhost:8100'), '/');

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->post($aiBaseUrl.'/api/ai/imaging/volume', [
                    'study_id' => $study->id,
                    'measurement_type' => $measurementType,
                ]);

            if ($response->failed()) {
                return ['ok' => false, 'status' => 502, 'message' => 'AI measurement extraction failed', 'extra' => ['status' => $response->status()]];
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
                return ['ok' => false, 'status' => 422, 'message' => 'AI service did not return extractable measurements for this study', 'extra' => ['ai_payload' => $payload]];
            }

            $measurementPayloads = $measurements
                ->map(fn (ImagingMeasurement $measurement) => $this->formatter->formatMeasurement($measurement->load('imagingStudy')))
                ->values();

            return ['ok' => true, 'data' => [
                'extracted' => $measurementPayloads->count(),
                'measurement_types' => $measurementPayloads->pluck('measurement_type')->unique()->values(),
                'measurements' => $measurementPayloads,
                'requires_review' => true,
            ]];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 502, 'message' => 'Unable to extract AI measurements', 'detail' => $e->getMessage()];
        }
    }

    /**
     * Legacy patient-scoped measurement create (/patients/{patient}/imaging/{study}/measurements).
     *
     * @return array{ok: bool, status?: int, message?: string, data?: array}
     */
    public function storeLegacyMeasurement(ImagingStudy $studyModel, array $validated): array
    {
        $seriesId = $validated['series_id'] ?? null;
        if ($seriesId !== null) {
            $series = ImagingSeries::where('id', $seriesId)
                ->where('imaging_study_id', $studyModel->id)
                ->first();

            if (! $series) {
                return ['ok' => false, 'status' => 422, 'message' => 'Series not found for this study'];
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

        return ['ok' => true, 'data' => $this->formatter->formatMeasurement($measurement->load('imagingStudy'))];
    }
}
