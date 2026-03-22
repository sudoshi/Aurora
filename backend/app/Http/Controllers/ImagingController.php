<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImagingController extends Controller
{
    /**
     * GET /api/patients/{patient}/imaging
     *
     * List imaging studies for a patient with optional filters.
     */
    public function index(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (!$patientModel) {
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
     *
     * Show study details with series, measurements, and segmentations.
     */
    public function show(int $patient, int $study): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (!$patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $studyModel = ImagingStudy::where('id', $study)
            ->where('patient_id', $patient)
            ->first();

        if (!$studyModel) {
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
     *
     * Add a measurement to an imaging study.
     */
    public function storeMeasurement(Request $request, int $patient, int $study): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (!$patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $studyModel = ImagingStudy::where('id', $study)
            ->where('patient_id', $patient)
            ->first();

        if (!$studyModel) {
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
     *
     * Get response assessments for a patient by comparing sequential studies.
     */
    public function responseAssessments(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::find($patient);

        if (!$patientModel) {
            return ApiResponse::error('Patient not found', 404);
        }

        $studies = ImagingStudy::where('patient_id', $patient)
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
