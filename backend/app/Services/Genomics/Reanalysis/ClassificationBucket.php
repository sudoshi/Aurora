<?php

namespace App\Services\Genomics\Reanalysis;

/**
 * Normalizes ClinVar germline classification strings + review-status into the
 * ordered buckets and star tiers used by the reanalysis transition rules.
 */
class ClassificationBucket
{
    /** Ordinal pathogenicity rank (higher = more pathogenic). */
    private const RANK = [
        'benign' => 0,
        'likely_benign' => 1,
        'unknown' => 2,
        'vus' => 2,
        'conflicting' => 2,
        'likely_pathogenic' => 3,
        'pathogenic' => 4,
    ];

    private const ACTIONABLE = ['likely_pathogenic', 'pathogenic'];

    public static function normalize(?string $significance): string
    {
        $s = strtolower(trim((string) $significance));
        if ($s === '') {
            return 'unknown';
        }

        return match (true) {
            str_contains($s, 'conflicting') => 'conflicting',
            str_contains($s, 'pathogenic') && str_contains($s, 'likely') && ! str_contains($s, '/') => 'likely_pathogenic',
            str_contains($s, 'pathogenic') => 'pathogenic',
            str_contains($s, 'uncertain') => 'vus',
            str_contains($s, 'benign') && str_contains($s, 'likely') => 'likely_benign',
            str_contains($s, 'benign') => 'benign',
            default => 'unknown',
        };
    }

    public static function rank(string $bucket): int
    {
        return self::RANK[$bucket] ?? 2;
    }

    public static function isActionable(string $bucket): bool
    {
        return in_array($bucket, self::ACTIONABLE, true);
    }

    public static function stars(?string $reviewStatus): int
    {
        $s = strtolower(trim((string) $reviewStatus));

        return match (true) {
            str_contains($s, 'practice guideline') => 4,
            str_contains($s, 'expert panel') => 3,
            str_contains($s, 'multiple submitters') && ! str_contains($s, 'conflicting') => 2,
            str_contains($s, 'criteria provided') && ! str_contains($s, 'no assertion') => 1,
            default => 0,
        };
    }
}
