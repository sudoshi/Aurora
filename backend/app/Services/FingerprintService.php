<?php

namespace App\Services;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\FusionWeightConfig;
use App\Models\Clinical\PatientFingerprint;
use App\Models\Clinical\SimilaritySearch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FingerprintService
{
    private string $aiBaseUrl;

    public function __construct()
    {
        $this->aiBaseUrl = rtrim(config('services.ai.base_url', 'http://localhost:8100'), '/');
    }

    /**
     * Encode (or re-encode) a patient's fingerprint across all available dimensions.
     */
    public function encodePatient(int $patientId): PatientFingerprint
    {
        $patient = ClinicalPatient::with([
            'genomicVariants', 'conditions', 'medications', 'drugEras',
            'measurements', 'procedures', 'visits', 'conditionEras',
            'imagingStudies.imagingMeasurements', 'imagingStudies.segmentations',
        ])->findOrFail($patientId);

        $fingerprint = PatientFingerprint::firstOrCreate(
            ['patient_id' => $patientId],
            ['encoder_version' => 'v1.0']
        );

        // Encode each dimension independently — failures don't block others
        $this->encodeGenomicDimension($patient, $fingerprint);
        $this->encodeVolumetricDimension($patient, $fingerprint);
        $this->encodeClinicalDimension($patient, $fingerprint);

        $fingerprint->save();

        return $fingerprint->fresh();
    }

    /**
     * Search for similar patients using dimensional fingerprint fusion.
     */
    public function searchSimilar(
        int $patientId,
        array $weights = [],
        int $limit = 10,
        string $context = 'point_of_care',
    ): array {
        $fingerprint = PatientFingerprint::where('patient_id', $patientId)->first();

        if (! $fingerprint || $fingerprint->available_dimension_count === 0) {
            return ['results' => [], 'meta' => ['error' => 'Patient has no fingerprint data']];
        }

        // Resolve weights: custom overrides or active default
        $resolvedWeights = $this->resolveWeights($weights, $fingerprint);
        $isCustom = ! empty($weights);

        // Build pgvector similarity query per available dimension
        $results = $this->executeSimilarityQuery($fingerprint, $resolvedWeights, $limit);

        // Generate explanations for top results
        $results = $this->enrichWithExplanations($patientId, $results);

        return [
            'results' => $results,
            'meta' => [
                'query_patient_id' => $patientId,
                'weights_used' => $resolvedWeights,
                'weights_customized' => $isCustom,
                'dimensions_available' => $fingerprint->dimension_mask,
                'result_count' => count($results),
            ],
        ];
    }

    /**
     * Execute the multi-dimensional pgvector similarity query.
     *
     * Uses parameterized queries throughout to prevent SQL injection.
     * Weights are cast to float and clamped before use.
     */
    private function executeSimilarityQuery(
        PatientFingerprint $fingerprint,
        array $weights,
        int $limit,
    ): array {
        $patientId = (int) $fingerprint->patient_id;

        // Cast and clamp weights to safe float values
        $gw = max(0.0, min(1.0, (float) ($weights['genomic'] ?? 0)));
        $vw = max(0.0, min(1.0, (float) ($weights['volumetric'] ?? 0)));
        $cw = max(0.0, min(1.0, (float) ($weights['clinical'] ?? 0)));

        $selectParts = [];
        $weightSum = 0.0;

        if ($fingerprint->genomic_available && $gw > 0) {
            $selectParts[] = "COALESCE((1 - (pf.genomic_vector <=> qf.genomic_vector)) * {$gw}, 0) AS genomic_sim";
            $weightSum += $gw;
        }

        if ($fingerprint->volumetric_available && $vw > 0) {
            $selectParts[] = "COALESCE((1 - (pf.volumetric_vector <=> qf.volumetric_vector)) * {$vw}, 0) AS volumetric_sim";
            $weightSum += $vw;
        }

        if ($fingerprint->clinical_available && $cw > 0) {
            $selectParts[] = "COALESCE((1 - (pf.clinical_vector <=> qf.clinical_vector)) * {$cw}, 0) AS clinical_sim";
            $weightSum += $cw;
        }

        if (empty($selectParts) || $weightSum === 0.0) {
            return [];
        }

        $simColumns = implode(",\n                ", $selectParts);
        $compositeTerms = implode(' + ', array_map(
            fn ($part) => explode(' AS ', $part)[0],
            $selectParts
        ));

        // Use a CTE to fetch the query patient's vectors once (parameterized)
        $sql = "
            WITH qf AS (
                SELECT genomic_vector, volumetric_vector, clinical_vector
                FROM clinical.patient_fingerprints
                WHERE patient_id = :query_pid
                LIMIT 1
            )
            SELECT
                pf.patient_id,
                {$simColumns},
                ({$compositeTerms}) / {$weightSum} AS composite_score,
                pf.genomic_confidence,
                pf.volumetric_confidence,
                pf.clinical_confidence,
                pf.genomic_available,
                pf.volumetric_available,
                pf.clinical_available
            FROM clinical.patient_fingerprints pf, qf
            WHERE pf.patient_id != :exclude_pid
              AND (pf.genomic_available OR pf.volumetric_available OR pf.clinical_available)
            ORDER BY composite_score DESC
            LIMIT :lim
        ";

        $rows = DB::connection('pgsql')->select($sql, [
            'query_pid' => $patientId,
            'exclude_pid' => $patientId,
            'lim' => $limit,
        ]);

        return array_map(function ($row) {
            return [
                'patient_id' => $row->patient_id,
                'composite_score' => round((float) $row->composite_score, 4),
                'genomic_similarity' => isset($row->genomic_sim) ? round((float) $row->genomic_sim, 4) : null,
                'volumetric_similarity' => isset($row->volumetric_sim) ? round((float) $row->volumetric_sim, 4) : null,
                'clinical_similarity' => isset($row->clinical_sim) ? round((float) $row->clinical_sim, 4) : null,
                'dimensions_matched' => array_filter([
                    $row->genomic_available ? 'genomic' : null,
                    $row->volumetric_available ? 'volumetric' : null,
                    $row->clinical_available ? 'clinical' : null,
                ]),
            ];
        }, $rows);
    }

    /**
     * Resolve weights from user input or active default.
     */
    private function resolveWeights(array $customWeights, PatientFingerprint $fingerprint): array
    {
        if (! empty($customWeights)) {
            $sum = array_sum($customWeights);

            return $sum > 0 ? array_map(fn ($w) => $w / $sum, $customWeights) : $customWeights;
        }

        $active = FusionWeightConfig::active()->first();

        $weights = $active
            ? $active->dimension_weights
            : ['genomic' => 0.34, 'volumetric' => 0.33, 'clinical' => 0.33];

        // Zero out weights for missing dimensions and renormalize
        if (! $fingerprint->genomic_available) {
            $weights['genomic'] = 0;
        }
        if (! $fingerprint->volumetric_available) {
            $weights['volumetric'] = 0;
        }
        if (! $fingerprint->clinical_available) {
            $weights['clinical'] = 0;
        }

        $sum = array_sum($weights);
        if ($sum > 0) {
            $weights = array_map(fn ($w) => $w / $sum, $weights);
        }

        return $weights;
    }

    /**
     * Call Python AI service to encode genomic dimension.
     */
    private function encodeGenomicDimension(ClinicalPatient $patient, PatientFingerprint $fingerprint): void
    {
        $variants = $patient->genomicVariants;
        if ($variants->isEmpty()) {
            $fingerprint->genomic_available = false;

            return;
        }

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/encode/genomic", [
                'patient_id' => $patient->id,
                'variants' => $variants->map(fn ($v) => [
                    'gene' => $v->gene,
                    'variant' => $v->variant,
                    'variant_type' => $v->variant_type,
                    'allele_frequency' => $v->allele_frequency,
                    'clinical_significance' => $v->clinical_significance,
                    'zygosity' => $v->zygosity,
                    'actionability' => $v->actionability,
                ])->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                DB::connection('clinical')->statement(
                    'UPDATE clinical.patient_fingerprints SET genomic_vector = :vector WHERE patient_id = :id',
                    ['vector' => $data['vector'], 'id' => $patient->id]
                );
                $fingerprint->genomic_available = true;
                $fingerprint->genomic_confidence = $data['confidence'] ?? 0.5;
                $fingerprint->genomic_encoded_at = now();
            }
        } catch (\Exception $e) {
            \Log::warning("Genomic encoding failed for patient {$patient->id}: {$e->getMessage()}");
            // Leave dimension unchanged on failure
        }
    }

    /**
     * Call Python AI service to encode volumetric dimension.
     */
    private function encodeVolumetricDimension(ClinicalPatient $patient, PatientFingerprint $fingerprint): void
    {
        $studies = $patient->imagingStudies;
        if ($studies->isEmpty()) {
            $fingerprint->volumetric_available = false;

            return;
        }

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/encode/volumetric", [
                'patient_id' => $patient->id,
                'studies' => $studies->map(fn ($s) => [
                    'modality' => $s->modality,
                    'body_part' => $s->body_part,
                    'study_date' => $s->study_date,
                    'measurements' => $s->imagingMeasurements->map(fn ($m) => [
                        'measurement_type' => $m->measurement_type,
                        'value_numeric' => $m->value_numeric,
                        'unit' => $m->unit,
                        'target_lesion' => $m->target_lesion,
                        'measured_at' => $m->measured_at,
                    ])->toArray(),
                    'segmentations' => $s->segmentations->map(fn ($seg) => [
                        'volume_mm3' => $seg->volume_mm3,
                        'label' => $seg->label,
                    ])->toArray(),
                ])->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                DB::connection('clinical')->statement(
                    'UPDATE clinical.patient_fingerprints SET volumetric_vector = :vector WHERE patient_id = :id',
                    ['vector' => $data['vector'], 'id' => $patient->id]
                );
                $fingerprint->volumetric_available = true;
                $fingerprint->volumetric_confidence = $data['confidence'] ?? 0.5;
                $fingerprint->volumetric_encoded_at = now();
            }
        } catch (\Exception $e) {
            \Log::warning("Volumetric encoding failed for patient {$patient->id}: {$e->getMessage()}");
        }
    }

    /**
     * Call Python AI service to encode clinical dimension.
     */
    private function encodeClinicalDimension(ClinicalPatient $patient, PatientFingerprint $fingerprint): void
    {
        $hasData = $patient->conditions->isNotEmpty()
                || $patient->medications->isNotEmpty()
                || $patient->measurements->isNotEmpty();

        if (! $hasData) {
            $fingerprint->clinical_available = false;

            return;
        }

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/encode/clinical", [
                'patient_id' => $patient->id,
                'conditions' => $patient->conditions->map(fn ($c) => [
                    'concept_name' => $c->concept_name,
                    'concept_code' => $c->concept_code,
                    'domain' => $c->domain,
                    'status' => $c->status,
                    'severity' => $c->severity,
                ])->toArray(),
                'medications' => $patient->medications->map(fn ($m) => [
                    'drug_name' => $m->drug_name,
                    'dose_value' => $m->dose_value,
                    'dose_unit' => $m->dose_unit,
                    'frequency' => $m->frequency,
                    'status' => $m->status,
                    'start_date' => $m->start_date,
                    'end_date' => $m->end_date,
                ])->toArray(),
                'drug_eras' => $patient->drugEras->map(fn ($d) => [
                    'drug_name' => $d->drug_name,
                    'era_start' => $d->era_start,
                    'era_end' => $d->era_end,
                    'gap_days' => $d->gap_days,
                ])->toArray(),
                'measurements' => $patient->measurements->map(fn ($m) => [
                    'measurement_name' => $m->measurement_name,
                    'value_numeric' => $m->value_numeric,
                    'unit' => $m->unit,
                    'measured_at' => $m->measured_at,
                ])->toArray(),
                'visits' => $patient->visits->map(fn ($v) => [
                    'visit_type' => $v->visit_type,
                    'admission_date' => $v->admission_date,
                    'discharge_date' => $v->discharge_date,
                ])->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                DB::connection('clinical')->statement(
                    'UPDATE clinical.patient_fingerprints SET clinical_vector = :vector WHERE patient_id = :id',
                    ['vector' => $data['vector'], 'id' => $patient->id]
                );
                $fingerprint->clinical_available = true;
                $fingerprint->clinical_confidence = $data['confidence'] ?? 0.5;
                $fingerprint->clinical_encoded_at = now();
            }
        } catch (\Exception $e) {
            \Log::warning("Clinical encoding failed for patient {$patient->id}: {$e->getMessage()}");
        }
    }

    /**
     * Call Python AI to generate explanation for each similar patient pair.
     */
    private function enrichWithExplanations(int $queryPatientId, array $results): array
    {
        if (empty($results)) {
            return $results;
        }

        try {
            $response = Http::timeout(60)->post("{$this->aiBaseUrl}/api/ai/fingerprint/explain", [
                'query_patient_id' => $queryPatientId,
                'similar_patient_ids' => array_column($results, 'patient_id'),
            ]);

            if ($response->successful()) {
                $explanations = $response->json('explanations') ?? [];
                foreach ($results as $i => &$result) {
                    $result['explanation'] = $explanations[$i] ?? null;
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Explanation generation failed: {$e->getMessage()}");
        }

        return $results;
    }

    /**
     * Log a similarity search for audit.
     */
    public function logSearch(
        int $queryPatientId,
        int $searchedBy,
        array $weightsUsed,
        bool $weightsCustomized,
        string $context,
        array $results,
    ): void {
        SimilaritySearch::create([
            'query_patient_id' => $queryPatientId,
            'searched_by' => $searchedBy,
            'weights_used' => $weightsUsed,
            'weights_customized' => $weightsCustomized,
            'context' => $context,
            'result_patient_ids' => array_column($results, 'patient_id'),
            'result_scores' => array_map(fn ($r) => [
                'composite' => $r['composite_score'],
                'genomic' => $r['genomic_similarity'] ?? null,
                'volumetric' => $r['volumetric_similarity'] ?? null,
                'clinical' => $r['clinical_similarity'] ?? null,
            ], $results),
            'result_count' => count($results),
        ]);
    }

    /**
     * Get fingerprint stats.
     */
    public function getStats(): array
    {
        $total = PatientFingerprint::count();
        $genomic = PatientFingerprint::where('genomic_available', true)->count();
        $volumetric = PatientFingerprint::where('volumetric_available', true)->count();
        $clinical = PatientFingerprint::where('clinical_available', true)->count();
        $full = PatientFingerprint::where('genomic_available', true)
            ->where('volumetric_available', true)
            ->where('clinical_available', true)
            ->count();

        return [
            'total_fingerprinted' => $total,
            'genomic_coverage' => $genomic,
            'volumetric_coverage' => $volumetric,
            'clinical_coverage' => $clinical,
            'full_coverage' => $full,
            'outcomes_annotated' => \App\Models\Clinical\OutcomeTrajectory::whereNotNull('clinician_rating')->count(),
        ];
    }
}
