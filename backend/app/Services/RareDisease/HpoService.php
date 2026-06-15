<?php

namespace App\Services\RareDisease;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxies the Human Phenotype Ontology search API (ontology.jax.org) with caching.
 * The legacy hpo.jax.org host is deprecated; the current API is ontology.jax.org/api/hp.
 */
class HpoService
{
    private const BASE = 'https://ontology.jax.org/api/hp';

    /**
     * @return array<int, array{id:string,label:string,definition:?string,synonyms:array<int,string>}>
     */
    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $limit = max(1, min($limit, 25));
        $cacheKey = 'hpo:search:'.md5(mb_strtolower($query).':'.$limit);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $limit) {
            $response = Http::timeout(10)->acceptJson()->get(self::BASE.'/search', [
                'q' => $query,
                'limit' => $limit,
            ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('terms', []))
                ->map(fn (array $t): array => [
                    'id' => $t['id'] ?? '',
                    'label' => $t['name'] ?? '',
                    'definition' => $t['definition'] ?? null,
                    'synonyms' => array_values($t['synonyms'] ?? []),
                ])
                ->filter(fn (array $t): bool => (bool) preg_match('/^HP:\d{7}$/', $t['id']))
                ->values()
                ->all();
        });
    }
}
