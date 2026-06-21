<?php

namespace App\Services\Imaging;

use App\Models\Clinical\ImagingResponseAssessment;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Support\Collection;

/**
 * RECIST response assessment: stored listings, manual creation, computed
 * assessments, previews, and the shared baseline-vs-current categorisation.
 * Returns arrays/collections/result tuples; the controller owns validation
 * and HTTP envelopes.
 */
class ImagingResponseAssessmentService
{
    public function __construct(private readonly ImagingFormatter $formatter) {}

    /**
     * Stored assessments for a patient, or null when none exist (caller then
     * falls back to computeRecistAssessments()).
     */
    public function storedAssessments(int $personId): ?Collection
    {
        $stored = ImagingResponseAssessment::where('patient_id', $personId)
            ->with(['baselineStudy', 'currentStudy'])
            ->orderByDesc('assessment_date')
            ->orderByDesc('id')
            ->get();

        if ($stored->isEmpty()) {
            return null;
        }

        return $stored
            ->map(fn (ImagingResponseAssessment $a) => $this->formatter->formatResponseAssessment($a))
            ->values();
    }

    public function createManual(int $personId, array $validated, ?int $assessedBy): array
    {
        $baselineStudy = ImagingStudy::where('id', $validated['baseline_study_id'])
            ->where('patient_id', $personId)
            ->first();

        if (! $baselineStudy) {
            return ['ok' => false, 'status' => 404, 'message' => 'Baseline study not found for this patient'];
        }

        $currentStudy = ImagingStudy::where('id', $validated['current_study_id'])
            ->where('patient_id', $personId)
            ->first();

        if (! $currentStudy) {
            return ['ok' => false, 'status' => 404, 'message' => 'Current study not found for this patient'];
        }

        $assessment = ImagingResponseAssessment::create(array_merge($validated, [
            'patient_id' => $personId,
            'assessed_by' => $assessedBy,
            'is_confirmed' => $validated['is_confirmed'] ?? false,
            'source_type' => 'manual',
        ]))->load(['baselineStudy', 'currentStudy']);

        return ['ok' => true, 'data' => $this->formatter->formatResponseAssessment($assessment)];
    }

    public function computeResponse(int $personId, array $validated, ?int $assessedBy): array
    {
        $currentStudy = ImagingStudy::where('id', $validated['current_study_id'])
            ->where('patient_id', $personId)
            ->with('imagingMeasurements')
            ->first();

        if (! $currentStudy) {
            return ['ok' => false, 'status' => 404, 'message' => 'Current study not found for this patient'];
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
            return ['ok' => false, 'status' => 422, 'message' => 'Need at least two distinct studies for assessment'];
        }

        $criteriaType = $validated['criteria_type'] ?? 'recist';
        if ($criteriaType === 'auto') {
            $criteriaType = 'recist';
        }

        $baselineSum = $baselineStudy->imagingMeasurements->where('target_lesion', true)->sum('value_numeric');
        $currentTargets = $currentStudy->imagingMeasurements->where('target_lesion', true);
        $currentSum = $currentTargets->sum('value_numeric');

        [$category, $percentChange] = $this->categorize($baselineSum, $currentSum, $currentTargets);

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
            'assessed_by' => $assessedBy,
            'source_id' => "baseline:{$baselineStudy->id};current:{$currentStudy->id}",
        ])->load(['baselineStudy', 'currentStudy']);

        return ['ok' => true, 'data' => $this->formatter->formatResponseAssessment($assessment)];
    }

    public function assessPreview(int $personId, array $validated): array
    {
        $criteriaType = $validated['criteria_type'] ?? 'RECIST';

        $currentStudy = ImagingStudy::where('id', $validated['current_study_id'])
            ->where('patient_id', $personId)
            ->with('imagingMeasurements')
            ->first();

        if (! $currentStudy) {
            return ['ok' => false, 'status' => 404, 'message' => 'Current study not found for this patient'];
        }

        $baselineStudy = ImagingStudy::where('patient_id', $personId)
            ->orderBy('study_date', 'asc')
            ->with('imagingMeasurements')
            ->first();

        if (! $baselineStudy || $baselineStudy->id === $currentStudy->id) {
            return ['ok' => true, 'data' => [
                'response_category' => 'NE',
                'criteria_type' => $criteriaType,
                'rationale' => 'Insufficient studies for assessment',
                'baseline_value' => null,
                'nadir_value' => null,
                'current_value' => null,
                'percent_change_from_baseline' => null,
                'percent_change_from_nadir' => null,
            ]];
        }

        $baselineSum = $baselineStudy->imagingMeasurements->where('target_lesion', true)->sum('value_numeric');
        $currentTargets = $currentStudy->imagingMeasurements->where('target_lesion', true);
        $currentSum = $currentTargets->sum('value_numeric');

        [$category, $percentChange] = $this->categorize($baselineSum, $currentSum, $currentTargets);

        return ['ok' => true, 'data' => [
            'response_category' => $category,
            'criteria_type' => $criteriaType,
            'rationale' => "Preview via {$criteriaType}: baseline sum={$baselineSum}, current sum={$currentSum}",
            'baseline_value' => $baselineSum > 0 ? round((float) $baselineSum, 2) : null,
            'nadir_value' => null,
            'current_value' => $currentSum > 0 ? round((float) $currentSum, 2) : null,
            'percent_change_from_baseline' => $percentChange,
            'percent_change_from_nadir' => null,
        ]];
    }

    /**
     * Compute RECIST assessments across every study after the baseline.
     *
     * @return array{ok: bool, message: string, data: array}
     */
    public function computeRecistAssessments(int $patientId): array
    {
        $studies = ImagingStudy::where('patient_id', $patientId)
            ->orderBy('study_date', 'asc')
            ->with('imagingMeasurements')
            ->get();

        if ($studies->count() < 2) {
            return ['ok' => true, 'message' => 'Insufficient studies for response assessment', 'data' => []];
        }

        $assessments = [];
        $baseline = $studies->first();

        foreach ($studies->skip(1) as $current) {
            $baselineSum = $baseline->imagingMeasurements->where('target_lesion', true)->sum('value_numeric');
            $currentMeasurements = $current->imagingMeasurements->where('target_lesion', true);
            $currentSum = $currentMeasurements->sum('value_numeric');

            [$category, $percentChange] = $this->categorize($baselineSum, $currentSum, $currentMeasurements);

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

        return ['ok' => true, 'message' => 'Response assessments retrieved', 'data' => $assessments];
    }

    /**
     * Shared RECIST categorisation.
     *
     * @return array{0: string, 1: float|null} [category, percentChange]
     */
    private function categorize(float|int $baselineSum, float|int $currentSum, Collection $currentTargets): array
    {
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

        return [$category, $percentChange];
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
}
