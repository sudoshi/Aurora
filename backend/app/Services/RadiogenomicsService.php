<?php

namespace App\Services;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ClinicalPatient;
use Illuminate\Support\Collection;

class RadiogenomicsService
{
    /**
     * Build a unified radiogenomics panel for a patient.
     */
    public function getPatientPanel(int $patientId): array
    {
        $patient = ClinicalPatient::find($patientId);
        if (!$patient) {
            return [];
        }

        // Fetch genomic variants - ordered by clinical significance
        $variants = GenomicVariant::where('patient_id', $patientId)
            ->orderByRaw("CASE WHEN clinical_significance = 'pathogenic' THEN 0 WHEN clinical_significance = 'likely_pathogenic' THEN 1 WHEN clinical_significance = 'VUS' THEN 2 ELSE 3 END")
            ->orderBy('gene')
            ->get();

        // Fetch imaging studies
        $studies = ImagingStudy::where('patient_id', $patientId)
            ->orderBy('study_date', 'desc')
            ->with('imagingMeasurements')
            ->get();

        // Classify variants
        $actionable = $variants->filter(fn ($v) => in_array($v->clinical_significance, ['pathogenic', 'likely_pathogenic']));
        $vus = $variants->filter(fn ($v) => $v->clinical_significance === 'VUS');

        // Build drug exposure timeline from drug_eras
        $drugExposures = \DB::table('clinical.drug_eras')
            ->where('patient_id', $patientId)
            ->orderBy('era_start')
            ->get()
            ->map(fn ($d) => [
                'drug_name' => $d->drug_name,
                'start_date' => $d->era_start,
                'end_date' => $d->era_end,
                'total_days' => $d->era_end ? (int) round((strtotime($d->era_end) - strtotime($d->era_start)) / 86400) : null,
            ])
            ->toArray();

        // Build correlations (simplified - no VariantDrugInteraction table)
        $correlations = $this->buildCorrelations($variants, $drugExposures);
        $recommendations = $this->buildRecommendations($variants, $correlations);

        return [
            'patient_id' => $patientId,
            'demographics' => [
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'date_of_birth' => $patient->date_of_birth,
                'gender' => $patient->gender,
            ],
            'variants' => [
                'all' => $variants->toArray(),
                'actionable' => $actionable->pluck('gene', 'id')->toArray(),
                'vus' => $vus->pluck('gene', 'id')->toArray(),
                'total' => $variants->count(),
                'pathogenic_count' => $actionable->count(),
                'vus_count' => $vus->count(),
            ],
            'imaging' => [
                'studies' => $studies->map(fn ($s) => [
                    'id' => $s->id,
                    'modality' => $s->modality,
                    'study_date' => $s->study_date?->toDateString(),
                    'description' => $s->description,
                    'body_part' => $s->body_part,
                    'measurement_count' => $s->imagingMeasurements->count(),
                ])->toArray(),
                'summary' => [
                    'total_studies' => $studies->count(),
                    'modalities' => $studies->pluck('modality')->unique()->values()->toArray(),
                ],
            ],
            'drug_exposures' => $drugExposures,
            'correlations' => $correlations,
            'recommendations' => $recommendations,
        ];
    }

    private function buildCorrelations(Collection $variants, array $drugExposures): array
    {
        // Known gene-drug relationships (hardcoded reference database)
        $knownInteractions = [
            'BRAF' => [['drug' => 'Vemurafenib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Dabrafenib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'KRAS' => [['drug' => 'Cetuximab', 'relationship' => 'resistant', 'evidence' => 'Level 1A'], ['drug' => 'Panitumumab', 'relationship' => 'resistant', 'evidence' => 'Level 1A'], ['drug' => 'Sotorasib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'EGFR' => [['drug' => 'Erlotinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Osimertinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Gefitinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'ALK' => [['drug' => 'Crizotinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Alectinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'HER2' => [['drug' => 'Trastuzumab', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Pertuzumab', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'BRCA1' => [['drug' => 'Olaparib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Rucaparib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'BRCA2' => [['drug' => 'Olaparib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Rucaparib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'TP53' => [['drug' => 'Cisplatin', 'relationship' => 'sensitive', 'evidence' => 'Level 2B']],
            'PIK3CA' => [['drug' => 'Alpelisib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
            'NTRK1' => [['drug' => 'Larotrectinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A'], ['drug' => 'Entrectinib', 'relationship' => 'sensitive', 'evidence' => 'Level 1A']],
        ];

        $correlations = [];
        foreach ($variants as $variant) {
            $interactions = $knownInteractions[strtoupper($variant->gene)] ?? [];
            foreach ($interactions as $interaction) {
                $matchedDrug = collect($drugExposures)->first(
                    fn ($d) => str_contains(strtolower($d['drug_name']), strtolower($interaction['drug']))
                );
                $correlations[] = [
                    'variant_id' => $variant->id,
                    'gene_symbol' => $variant->gene,
                    'variant' => $variant->variant,
                    'clinical_significance' => $variant->clinical_significance,
                    'drug_name' => $interaction['drug'],
                    'relationship' => $interaction['relationship'],
                    'evidence_level' => $interaction['evidence'],
                    'patient_received_drug' => $matchedDrug !== null,
                    'drug_start' => $matchedDrug['start_date'] ?? null,
                    'drug_end' => $matchedDrug['end_date'] ?? null,
                ];
            }
        }
        return $correlations;
    }

    private function buildRecommendations(Collection $variants, array $correlations): array
    {
        $recommendations = [];
        $pathogenic = $variants->filter(fn ($v) => in_array($v->clinical_significance, ['pathogenic', 'likely_pathogenic']));

        foreach ($pathogenic as $variant) {
            $variantCorrelations = collect($correlations)->where('variant_id', $variant->id);
            $drugsAvoid = $variantCorrelations->where('relationship', 'resistant')->pluck('drug_name')->unique()->values()->toArray();
            $drugsConsider = $variantCorrelations->whereIn('relationship', ['sensitive', 'partial_response'])->pluck('drug_name')->unique()->values()->toArray();

            if (empty($drugsAvoid) && empty($drugsConsider)) {
                continue;
            }

            $recommendations[] = [
                'gene' => $variant->gene,
                'variant' => $variant->variant ?? $variant->variant_type,
                'recommendation_type' => !empty($drugsAvoid) ? 'avoid_and_consider' : 'consider',
                'drugs_avoid' => $drugsAvoid,
                'drugs_consider' => $drugsConsider,
                'rationale' => $this->buildRationale($variant, $drugsAvoid, $drugsConsider),
            ];
        }
        return $recommendations;
    }

    private function buildRationale($variant, array $avoid, array $consider): string
    {
        $parts = [];
        if (!empty($avoid)) {
            $parts[] = sprintf('%s %s confers resistance to %s.', $variant->gene, $variant->variant ?? '', implode(', ', $avoid));
        }
        if (!empty($consider)) {
            $parts[] = sprintf('Consider %s (potential sensitivity via %s pathway).', implode(', ', $consider), $variant->gene);
        }
        return implode(' ', $parts);
    }
}
