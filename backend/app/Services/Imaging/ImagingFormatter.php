<?php

namespace App\Services\Imaging;

use App\Models\Clinical\ImagingFeature;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingResponseAssessment;
use App\Models\Clinical\ImagingStudy;

/**
 * Shared JSON formatting for imaging resources. Centralises the response
 * shapes so the listing/measurement/assessment services emit identical
 * payloads.
 */
class ImagingFormatter
{
    public function formatStudy(ImagingStudy $study): array
    {
        $isIndexed = $study->dicom_endpoint === 'orthanc'
            || $study->source_type === 'orthanc'
            || str_contains((string) $study->dicom_endpoint, 'dicom-web');

        // Prefer eager-loaded withCount() attributes (set by callers that list
        // many studies) to avoid an N+1 of per-study count() queries. Fall back
        // to a live count only when the attribute was not pre-loaded.
        $measurementCount = $study->imaging_measurements_count
            ?? $study->imagingMeasurements()->count();
        $segmentationCount = $study->segmentations_count
            ?? $study->segmentations()->count();

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
            'measurement_count' => $measurementCount,
            'measurements_count' => $measurementCount,
            'segmentation_count' => $segmentationCount,
            'created_at' => $study->created_at?->toISOString(),
            'updated_at' => $study->updated_at?->toISOString(),
        ];
    }

    public function formatMeasurement(ImagingMeasurement $m): array
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

    public function formatResponseAssessment(ImagingResponseAssessment $assessment): array
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

    public function formatFeature(ImagingFeature $feature): array
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
}
