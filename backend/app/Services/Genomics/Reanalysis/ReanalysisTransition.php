<?php

namespace App\Services\Genomics\Reanalysis;

/**
 * Decides whether a ClinVar classification change between two reviews is
 * alert-worthy, and at what severity. Suppresses clinically-cosmetic churn
 * (P<->LP, B<->LB, star decreases, sub-3-star bumps) to control VUS noise.
 */
class ReanalysisTransition
{
    /** @return 'high'|'medium'|null */
    public static function severity(string $fromBucket, string $toBucket, int $fromStars, int $toStars): ?string
    {
        $fromActionable = ClassificationBucket::isActionable($fromBucket);
        $toActionable = ClassificationBucket::isActionable($toBucket);

        // Upgrade into, or downgrade out of, the actionable (LP/P) zone.
        if ($fromActionable !== $toActionable) {
            return 'high';
        }

        // De-prioritization: VUS resolved benign.
        if ($fromBucket === 'vus' && in_array($toBucket, ['benign', 'likely_benign'], true)) {
            return 'medium';
        }

        // Same clinical bucket but confidence jumped to expert-panel/practice-guideline.
        if ($fromBucket === $toBucket && $toStars >= 3 && $fromStars < 3) {
            return 'medium';
        }

        return null;
    }
}
