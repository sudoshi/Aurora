<?php

namespace App\Services\Genomics\Reanalysis;

use App\Models\Clinical\ClinGenGeneValidity;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Compares each patient gene's CURRENT ClinGen Gene-Disease Validity classification
 * against its stored baseline; on a qualifying transition, creates a deduplicated
 * KB-change alert + an MDT task per affected patient, then advances the baseline.
 * Non-device CDS: it alerts, a human decides.
 */
class GeneValidityReanalysisService
{
    public function __construct(private ClinGenGdvService $gdv) {}

    public function run(): int
    {
        $alerts = 0;

        $genes = GenomicVariant::whereNotNull('patient_id')
            ->whereNotNull('gene')
            ->distinct()
            ->pluck('gene');

        foreach ($genes as $gene) {
            foreach ($this->gdv->byGene($gene) as $curation) {
                $alerts += $this->evaluateCuration($gene, $curation);
            }
        }

        return $alerts;
    }

    /**
     * @param  array<string, string>  $curation
     */
    private function evaluateCuration(string $gene, array $curation): int
    {
        $diseaseLabel = $curation['disease_label'];

        $existing = ClinGenGeneValidity::where([
            'gene_symbol' => $gene,
            'disease_label' => $diseaseLabel,
        ])->first();

        $baseline = $existing?->baseline_classification;

        // Parse ISO date; store null on failure
        $classificationDate = null;
        try {
            $rawDate = $curation['classification_date'] ?? null;
            if ($rawDate !== null && $rawDate !== '') {
                $classificationDate = \Illuminate\Support\Carbon::parse($rawDate);
            }
        } catch (\Throwable $e) {
            Log::warning('GeneValidityReanalysisService: failed to parse classification_date', [
                'gene' => $gene,
                'raw' => $curation['classification_date'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        $updatePayload = [
            'classification' => $curation['classification'],
            'disease_id' => $curation['disease_id'] ?? null,
            'moi' => $curation['moi'] ?? null,
            'classification_date' => $classificationDate,
            'report_url' => $curation['report_url'] ?? null,
            'last_checked_at' => now(),
        ];

        // First observation — set baseline to current classification and never alert
        if ($existing === null || $baseline === null) {
            $updatePayload['baseline_classification'] = $curation['classification'];
            ClinGenGeneValidity::updateOrCreate(
                ['gene_symbol' => $gene, 'disease_label' => $diseaseLabel],
                $updatePayload
            );

            return 0;
        }

        // Upsert without advancing baseline yet
        ClinGenGeneValidity::updateOrCreate(
            ['gene_symbol' => $gene, 'disease_label' => $diseaseLabel],
            $updatePayload
        );

        $severity = GeneValidityClassification::severity($baseline, $curation['classification']);

        if ($severity === null) {
            // No actionable change — advance baseline
            ClinGenGeneValidity::where([
                'gene_symbol' => $gene,
                'disease_label' => $diseaseLabel,
            ])->update(['baseline_classification' => $curation['classification']]);

            return 0;
        }

        // Qualifying change — raise an alert per affected patient
        $newAlerts = 0;

        $variantsByPatient = GenomicVariant::where('gene', $gene)
            ->whereNotNull('patient_id')
            ->get()
            ->groupBy('patient_id');

        foreach ($variantsByPatient as $patientId => $variants) {
            $variant = $variants->first();

            $deltaHash = hash('sha256', implode('|', [
                $gene,
                $diseaseLabel,
                $baseline,
                $curation['classification'],
                $patientId,
            ]));

            if (KbChangeAlert::where('delta_hash', $deltaHash)->exists()) {
                continue;
            }

            $actorId = User::query()->value('id');

            DB::transaction(function () use (
                $gene, $curation, $diseaseLabel, $baseline, $severity,
                $variant, $patientId, $deltaHash, $actorId, &$newAlerts
            ) {
                $task = PatientTask::create([
                    'patient_id' => $patientId,
                    'created_by' => $actorId,
                    'domain' => 'genomic',
                    'record_ref' => 'genomic:'.$variant->id,
                    'title' => sprintf(
                        'Re-review %s — ClinGen validity %s → %s',
                        $gene,
                        $baseline,
                        $curation['classification']
                    ),
                    'description' => sprintf(
                        'ClinGen reclassified the gene-disease relationship for %s / %s from "%s" to "%s". '
                        .'Variants in this gene for this patient may require re-review for diagnostic impact.',
                        $gene,
                        $diseaseLabel,
                        $baseline,
                        $curation['classification']
                    ),
                    'priority' => $severity === 'high' ? 'high' : 'normal',
                    'status' => 'pending',
                ]);

                KbChangeAlert::create([
                    'genomic_variant_id' => $variant->id,
                    'patient_id' => $patientId,
                    'source' => 'clingen_gdv',
                    'clinvar_variation_id' => null,
                    'from_bucket' => $baseline,
                    'to_bucket' => $curation['classification'],
                    'from_stars' => GeneValidityClassification::rank($baseline),
                    'to_stars' => GeneValidityClassification::rank($curation['classification']),
                    'severity' => $severity,
                    'evidence' => [
                        'gene' => $gene,
                        'disease' => $diseaseLabel,
                        'mondo' => $curation['disease_id'] ?? null,
                        'moi' => $curation['moi'] ?? null,
                        'classification' => $curation['classification'],
                        'baseline_classification' => $baseline,
                        'report_url' => $curation['report_url'] ?? null,
                    ],
                    'delta_hash' => $deltaHash,
                    'status' => 'new',
                    'task_id' => $task->id,
                ]);

                $newAlerts++;
            });
        }

        // Advance baseline once after all patients have been processed for this curation
        ClinGenGeneValidity::where([
            'gene_symbol' => $gene,
            'disease_label' => $diseaseLabel,
        ])->update(['baseline_classification' => $curation['classification']]);

        return $newAlerts;
    }
}
