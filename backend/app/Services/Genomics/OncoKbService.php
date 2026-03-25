<?php

namespace App\Services\Genomics;

use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OncoKbService
{
    private string $baseUrl = 'https://www.oncokb.org/api/v1';
    private ?string $token;

    /**
     * Map OncoKB evidence levels to internal format.
     */
    private const LEVEL_MAP = [
        'LEVEL_1'  => '1',
        'LEVEL_2A' => '2A',
        'LEVEL_2B' => '2B',
        'LEVEL_3A' => '3A',
        'LEVEL_3B' => '3B',
        'LEVEL_4'  => '4',
        'LEVEL_R1' => 'R1',
        'LEVEL_R2' => 'R2',
    ];

    /**
     * Internal evidence levels that indicate resistance.
     */
    private const RESISTANCE_LEVELS = ['R1', 'R2'];

    public function __construct()
    {
        $this->token = config('services.oncokb.token');
    }

    /**
     * Sync therapy annotations for all genes in our interaction table.
     * Calls OncoKB API per gene, parses treatment annotations, and upserts
     * GeneDrugInteraction records.
     *
     * @return array{synced: int, errors: int, upserted: int, skipped?: string}
     */
    public function syncInteractions(): array
    {
        if (!$this->token) {
            Log::warning('OncoKB API token not configured — skipping sync');
            return ['synced' => 0, 'errors' => 0, 'upserted' => 0, 'skipped' => 'no_token'];
        }

        $genes = GeneDrugInteraction::distinct()->pluck('gene')->all();
        $synced = 0;
        $errors = 0;
        $totalUpserted = 0;

        foreach ($genes as $gene) {
            try {
                $response = Http::withToken($this->token)
                    ->acceptJson()
                    ->get("{$this->baseUrl}/genes/{$gene}/variants");

                if ($response->failed()) {
                    Log::warning("OncoKB sync failed for gene {$gene}: HTTP {$response->status()}");
                    $errors++;
                    continue;
                }

                $responseData = $response->json();
                $treatments = $responseData['treatments'] ?? [];

                if (!empty($treatments)) {
                    $result = $this->parseAndUpsertTreatments($gene, $treatments);
                    $totalUpserted += $result['upserted'];
                }

                GeneDrugInteraction::where('gene', $gene)
                    ->update(['oncokb_last_synced_at' => now()]);

                $synced++;
            } catch (\Exception $e) {
                Log::error("OncoKB sync error for gene {$gene}: {$e->getMessage()}");
                $errors++;
            }
        }

        return ['synced' => $synced, 'errors' => $errors, 'upserted' => $totalUpserted];
    }

    /**
     * Parse OncoKB treatment annotations and upsert GeneDrugInteraction records.
     *
     * @param string $gene     The gene symbol (e.g. 'BRAF')
     * @param array  $treatments  Array of treatment objects from OncoKB API
     * @return array{upserted: int, skipped: int}
     */
    public function parseAndUpsertTreatments(string $gene, array $treatments): array
    {
        $upserted = 0;
        $skipped = 0;

        foreach ($treatments as $treatment) {
            $oncoKbLevel = $treatment['level'] ?? '';
            $mappedLevel = $this->mapEvidenceLevel($oncoKbLevel);

            if ($mappedLevel === null) {
                Log::info("OncoKB: skipping treatment for {$gene} with unknown level '{$oncoKbLevel}'");
                $skipped++;
                continue;
            }

            $drugNames = array_map(
                fn(array $drug) => strtolower(trim($drug['drugName'] ?? '')),
                $treatment['drugs'] ?? []
            );
            $drugName = implode(' + ', $drugNames);

            $indication = $treatment['levelAssociatedCancerType']['name']
                ?? $treatment['description']
                ?? null;

            GeneDrugInteraction::updateOrCreate(
                [
                    'gene'            => $gene,
                    'variant_pattern' => '*',
                    'drug'            => $drugName,
                ],
                [
                    'evidence_level'        => $mappedLevel,
                    'relationship'          => $this->mapRelationship($mappedLevel),
                    'indication'            => $indication,
                    'source'                => 'oncokb',
                    'source_url'            => "{$this->baseUrl}/genes/{$gene}/variants",
                    'oncokb_last_synced_at' => now(),
                    'last_verified_at'      => now(),
                ]
            );

            $upserted++;
        }

        return ['upserted' => $upserted, 'skipped' => $skipped];
    }

    /**
     * Map an OncoKB evidence level string to internal format.
     */
    private function mapEvidenceLevel(string $oncoKbLevel): ?string
    {
        return self::LEVEL_MAP[$oncoKbLevel] ?? null;
    }

    /**
     * Map a (already-mapped) evidence level to a relationship type.
     */
    private function mapRelationship(string $mappedLevel): string
    {
        return in_array($mappedLevel, self::RESISTANCE_LEVELS, true) ? 'resistant' : 'sensitive';
    }
}
