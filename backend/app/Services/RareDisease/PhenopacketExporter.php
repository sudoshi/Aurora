<?php

namespace App\Services\RareDisease;

use App\Models\DiagnosticOdyssey;

/**
 * Emits a GA4GH Phenopackets v2-shaped JSON structure for a diagnostic odyssey.
 * Scope here is phenotype-level interchange; genomic interpretations are added in Plan 3.
 */
class PhenopacketExporter
{
    public function export(DiagnosticOdyssey $odyssey): array
    {
        $odyssey->loadMissing(['phenotypeFeatures']);

        $features = $odyssey->phenotypeFeatures->map(function ($f): array {
            $feature = [
                'type' => ['id' => $f->hpo_id, 'label' => $f->hpo_label],
                'excluded' => (bool) $f->excluded,
            ];

            if ($f->onset_hpo_id) {
                $feature['onset'] = ['ontologyClass' => ['id' => $f->onset_hpo_id, 'label' => '']];
            }
            if ($f->severity_hpo_id) {
                $feature['severity'] = ['id' => $f->severity_hpo_id, 'label' => ''];
            }
            if ($f->frequency_hpo_id) {
                // Phenopackets v2: PhenotypicFeature.frequency is a bare OntologyClass (like severity),
                // not wrapped in an ontologyClass envelope.
                $feature['frequency'] = ['id' => $f->frequency_hpo_id, 'label' => ''];
            }
            if ($f->evidence) {
                $feature['evidence'] = [['evidenceCode' => ['id' => 'ECO:0000033', 'label' => 'author statement supported by traceable reference']]];
            }

            return $feature;
        })->values()->all();

        return [
            'id' => 'aurora-odyssey-'.$odyssey->id,
            'subject' => [
                // Pseudonymous subject id (D2): never emit the internal
                // patient_id on an export that may cross the trust boundary.
                'id' => 'aurora-subject-'.$odyssey->id,
            ],
            'phenotypicFeatures' => $features,
            'metaData' => [
                'created' => now()->toIso8601String(),
                'createdBy' => 'Aurora',
                'phenopacketSchemaVersion' => '2.0',
                'resources' => [[
                    'id' => 'hp',
                    'name' => 'Human Phenotype Ontology',
                    'url' => 'http://purl.obolibrary.org/obo/hp.owl',
                    'version' => 'latest',
                    'namespacePrefix' => 'HP',
                    'iriPrefix' => 'http://purl.obolibrary.org/obo/HP_',
                ]],
            ],
        ];
    }
}
