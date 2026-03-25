<?php

namespace App\Services\Genomics;

use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OncoKbService
{
    private string $baseUrl = 'https://www.oncokb.org/api/v1';
    private ?string $token;

    public function __construct()
    {
        $this->token = config('services.oncokb.token');
    }

    /**
     * Sync therapy annotations for all genes in our interaction table.
     * v1: Verifies connectivity and updates sync timestamps.
     * TODO: Parse OncoKB response and upsert new interactions.
     *
     * @return array{synced: int, errors: int, skipped?: string}
     */
    public function syncInteractions(): array
    {
        if (!$this->token) {
            Log::warning('OncoKB API token not configured — skipping sync');
            return ['synced' => 0, 'errors' => 0, 'skipped' => 'no_token'];
        }

        $genes = GeneDrugInteraction::distinct()->pluck('gene')->all();
        $synced = 0;
        $errors = 0;

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

                // TODO: Parse OncoKB response and upsert new interactions.
                // For v1, we verify connectivity and update the sync timestamp.
                // Full parsing (creating/updating GeneDrugInteraction records from
                // OncoKB treatment annotations) is a follow-up task.
                GeneDrugInteraction::where('gene', $gene)
                    ->update(['oncokb_last_synced_at' => now()]);

                $synced++;
            } catch (\Exception $e) {
                Log::error("OncoKB sync error for gene {$gene}: {$e->getMessage()}");
                $errors++;
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }
}
