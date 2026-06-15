<?php

namespace App\Services\RareDisease;

use App\Models\DiagnosticOdyssey;

/**
 * Imports GA4GH Phenopackets v2 phenotypicFeatures into an odyssey.
 * Idempotent on (odyssey_id, hpo_id): terms already present are skipped.
 * Scope: phenotype-level (genomic interpretations land in Plan 3).
 */
class PhenopacketImporter
{
    /**
     * @param  array<string, mixed>  $packet
     * @return array{imported:int, skipped:int}
     */
    public function importInto(DiagnosticOdyssey $odyssey, array $packet, int $actorId): array
    {
        $features = $packet['phenotypicFeatures'] ?? [];
        if (! is_array($features)) {
            throw new InvalidPhenopacketException('phenotypicFeatures must be an array.');
        }

        $existing = $odyssey->phenotypeFeatures()->pluck('hpo_id')->all();
        $imported = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            $hpoId = $feature['type']['id'] ?? null;
            if (! is_string($hpoId) || ! preg_match('/^HP:\d{7}$/', $hpoId)) {
                throw new InvalidPhenopacketException('Invalid HPO id in phenotypicFeatures: '.json_encode($hpoId));
            }

            if (in_array($hpoId, $existing, true)) {
                $skipped++;

                continue;
            }

            $odyssey->phenotypeFeatures()->create([
                'hpo_id' => $hpoId,
                'hpo_label' => $feature['type']['label'] ?? $hpoId,
                'excluded' => (bool) ($feature['excluded'] ?? false),
                'onset_hpo_id' => $feature['onset']['ontologyClass']['id'] ?? null,
                'severity_hpo_id' => $feature['severity']['id'] ?? null,
                'frequency_hpo_id' => $feature['frequency']['id'] ?? null,
                'evidence' => null,
                'recorded_by' => $actorId,
            ]);

            $existing[] = $hpoId;
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
