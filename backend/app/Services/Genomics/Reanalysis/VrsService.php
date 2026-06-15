<?php

namespace App\Services\Genomics\Reanalysis;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Computes a GA4GH VRS Allele ID (ga4gh:VA.…) for an HGVS variant expression
 * by calling a Dockerized AnyVar REST service. Degrades cleanly to null when
 * ANYVAR_URL is not configured or the service is unavailable — never throws into
 * the caller.
 */
class VrsService
{
    /**
     * Compute the VRS Allele ID for the given HGVS string.
     *
     * Returns null when ANYVAR_URL is unset (no-op guard), when the AnyVar
     * service returns an error, or when the response contains no valid ga4gh:VA
     * identifier.
     */
    public function computeId(string $hgvs): ?string
    {
        $url = (string) config('services.anyvar.url');
        if ($url === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->send('PUT', rtrim($url, '/').'/variation', ['json' => ['definition' => $hgvs]]);

            if (! $response->successful()) {
                return null;
            }

            $id = $response->json('object_id') ?? data_get($response->json(), 'object.id');

            if (! is_string($id) || $id === '' || ! str_starts_with($id, 'ga4gh:VA')) {
                return null;
            }

            return $id;
        } catch (\Throwable $e) {
            Log::warning('AnyVar VRS lookup failed: '.$e->getMessage());

            return null;
        }
    }
}
