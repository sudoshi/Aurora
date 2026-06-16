<?php

namespace App\Services\Beacon;

use App\Models\Clinical\GenomicVariant;

class BeaconService
{
    private function meta(string $returnedGranularity = 'boolean', array $received = []): array
    {
        return [
            'beaconId' => config('services.beacon.id'),
            'apiVersion' => 'v2.0.0',
            'returnedSchemas' => [],
            'returnedGranularity' => $returnedGranularity,
            'receivedRequestSummary' => $received,
        ];
    }

    public function queryGVariants(array $params, string $granularity): array
    {
        $chr = preg_replace('/^refseq:NC_0+(\d+)\..*$/', '$1', (string) ($params['referenceName'] ?? ''));
        $chr = ltrim($chr, 'chr');

        $start = isset($params['start']) ? (int) $params['start'] : null;

        $query = GenomicVariant::query()
            ->where('chromosome', $chr)
            ->when($start !== null, fn ($q) => $q->where('position', $start + 1))
            ->when(! empty($params['referenceBases']), fn ($q) => $q->where('ref_allele', $params['referenceBases']))
            ->when(! empty($params['alternateBases']), fn ($q) => $q->where('alt_allele', $params['alternateBases']));

        $count = (clone $query)->count();
        $exists = $count > 0;

        $summary = ['exists' => $exists];
        if ($granularity === 'count') {
            $summary['numTotalResults'] = $count;
        }

        return [
            'meta' => $this->meta($granularity, $params),
            'responseSummary' => $summary,
        ];
    }

    public function info(): array
    {
        return [
            'meta' => $this->meta(),
            'response' => [
                'id' => config('services.beacon.id'),
                'name' => config('services.beacon.name'),
                'apiVersion' => 'v2.0.0',
                'environment' => 'prod',
                'organization' => [
                    'id' => config('services.beacon.org_id'),
                    'name' => config('services.beacon.org_name'),
                ],
                'welcomeUrl' => config('services.beacon.welcome_url'),
            ],
        ];
    }

    public function serviceInfo(): array
    {
        return [
            'id' => config('services.beacon.id'),
            'name' => config('services.beacon.name'),
            'type' => [
                'group' => 'org.ga4gh',
                'artifact' => 'beacon',
                'version' => '2.0.0',
            ],
            'organization' => [
                'name' => config('services.beacon.org_name'),
                'url' => config('services.beacon.welcome_url'),
            ],
            'version' => '2.0.0',
        ];
    }

    public function configuration(): array
    {
        return [
            'meta' => $this->meta(),
            'response' => [
                '$schema' => 'https://raw.githubusercontent.com/ga4gh-beacon/beacon-v2/main/framework/json/configuration/beaconConfigurationSchema.json',
                'maturityAttributes' => ['productionStatus' => 'DEV'],
                'entryTypes' => $this->entryTypeDefs(),
            ],
        ];
    }

    public function map(): array
    {
        return [
            'meta' => $this->meta(),
            'response' => [
                'endpointSets' => [
                    'genomicVariant' => [
                        'entryType' => 'genomicVariant',
                        'rootUrl' => url('/api/beacon/g_variants'),
                    ],
                ],
            ],
        ];
    }

    public function entryTypes(): array
    {
        return [
            'meta' => $this->meta(),
            'response' => [
                'entryTypes' => $this->entryTypeDefs(),
            ],
        ];
    }

    private function entryTypeDefs(): array
    {
        return [
            'genomicVariant' => [
                'id' => 'genomicVariant',
                'name' => 'Genomic Variant',
                'ontologyTermForThisType' => [
                    'id' => 'ENSGLOSSARY:0000092',
                    'label' => 'Genomic Variant',
                ],
                'defaultSchema' => [
                    'id' => 'ga4gh-beacon-variant-v2.0.0',
                    'name' => 'Default schema for a genomic variant',
                ],
            ],
        ];
    }

    public function filteringTerms(): array
    {
        return [
            'meta' => $this->meta(),
            'response' => [
                'filteringTerms' => [],
            ],
        ];
    }
}
