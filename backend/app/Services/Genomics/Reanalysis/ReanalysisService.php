<?php

namespace App\Services\Genomics\Reanalysis;

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\Clinical\VariantCanonicalId;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Compares each canonicalized patient variant's CURRENT ClinVar classification
 * (from the synced clinvar_variants table) against its stored baseline; on a
 * qualifying transition, creates a deduplicated KB-change alert + an MDT task,
 * then advances the baseline. Non-device CDS: it alerts, a human decides.
 */
class ReanalysisService
{
    public function run(): int
    {
        $alerts = 0;

        VariantCanonicalId::with('variant')
            ->whereNotNull('clinvar_variation_id')
            ->chunkById(200, function ($canonicals) use (&$alerts) {
                foreach ($canonicals as $canonical) {
                    if ($this->evaluate($canonical)) {
                        $alerts++;
                    }
                }
            });

        return $alerts;
    }

    private function evaluate(VariantCanonicalId $canonical): bool
    {
        $current = ClinVarVariant::where('variation_id', $canonical->clinvar_variation_id)->first();
        if ($current === null) {
            return false;
        }

        $fromBucket = ClassificationBucket::normalize($canonical->baseline_significance);
        $toBucket = ClassificationBucket::normalize($current->clinical_significance);
        $fromStars = ClassificationBucket::stars($canonical->baseline_review_status);
        $toStars = ClassificationBucket::stars($current->review_status);

        $severity = ReanalysisTransition::severity($fromBucket, $toBucket, $fromStars, $toStars);
        if ($severity === null) {
            $canonical->update([
                'baseline_significance' => $current->clinical_significance,
                'baseline_review_status' => $current->review_status,
                'baselined_at' => now(),
            ]);

            return false;
        }

        $deltaHash = hash('sha256', implode('|', [
            $canonical->clinvar_variation_id, $fromBucket, $toBucket, $fromStars, $toStars,
        ]));

        if (KbChangeAlert::where('delta_hash', $deltaHash)->exists()) {
            return false;
        }

        $variant = $canonical->variant;
        $actorId = User::query()->value('id');

        return DB::transaction(function () use ($canonical, $variant, $current, $fromBucket, $toBucket, $fromStars, $toStars, $severity, $deltaHash, $actorId) {
            $task = PatientTask::create([
                'patient_id' => $variant->patient_id,
                'created_by' => $actorId,
                'domain' => 'genomic',
                'record_ref' => 'genomic:'.$variant->id,
                'title' => sprintf('Reanalyze %s — ClinVar %s → %s', $variant->gene, $fromBucket, $toBucket),
                'description' => sprintf(
                    'ClinVar reclassified this variant (VariationID %s) from %s to %s (%d→%d stars) since last review. Review for diagnostic impact.',
                    $canonical->clinvar_variation_id, $fromBucket, $toBucket, $fromStars, $toStars,
                ),
                'priority' => $severity === 'high' ? 'high' : 'normal',
                'status' => 'pending',
            ]);

            KbChangeAlert::create([
                'genomic_variant_id' => $variant->id,
                'patient_id' => $variant->patient_id,
                'source' => 'clinvar',
                'clinvar_variation_id' => $canonical->clinvar_variation_id,
                'from_bucket' => $fromBucket,
                'to_bucket' => $toBucket,
                'from_stars' => $fromStars,
                'to_stars' => $toStars,
                'severity' => $severity,
                'evidence' => [
                    'clinvar_significance' => $current->clinical_significance,
                    'review_status' => $current->review_status,
                    'gene' => $variant->gene,
                    'variation_url' => 'https://www.ncbi.nlm.nih.gov/clinvar/variation/'.$canonical->clinvar_variation_id.'/',
                ],
                'delta_hash' => $deltaHash,
                'status' => 'new',
                'task_id' => $task->id,
            ]);

            $canonical->update([
                'baseline_significance' => $current->clinical_significance,
                'baseline_review_status' => $current->review_status,
                'baselined_at' => now(),
            ]);

            return true;
        });
    }
}
