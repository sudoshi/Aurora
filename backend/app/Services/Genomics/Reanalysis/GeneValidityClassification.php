<?php

namespace App\Services\Genomics\Reanalysis;

/**
 * Value class for ClinGen Gene-Disease Validity (GDV) classification semantics.
 *
 * Rank reflects evidence strength as defined by ClinGen's SOP framework.
 * Severity encodes the clinical urgency of a classification change for
 * variant re-review triage.
 */
class GeneValidityClassification
{
    /**
     * Map a classification label to a numeric rank (higher = stronger validity).
     * Case-insensitive; unrecognised / null / empty strings map to 0.
     */
    public static function rank(?string $c): int
    {
        return match (strtolower(trim((string) $c))) {
            'definitive' => 6,
            'strong' => 5,
            'moderate' => 4,
            'limited' => 3,
            'disputed' => 2,
            'refuted' => 1,
            default => 0, // No Known Disease Relationship, Animal Model Only, null, ''
        };
    }

    /**
     * Determine the clinical severity of a classification change.
     *
     * Returns:
     *   null   – no actionable change (same classification, first observation, or $to is empty)
     *   'high' – moving INTO Disputed/Refuted (existing interpretations invalidated)
     *             OR upgrade from weak (≤3) to strong (≥5) validity (previously dismissed
     *             variants may now be actionable)
     *   'medium' – any other real change between recognised classifications
     */
    public static function severity(?string $from, ?string $to): ?string
    {
        $fromNorm = strtolower(trim((string) $from));
        $toNorm = strtolower(trim((string) $to));

        // No destination → nothing to alert on
        if ($toNorm === '') {
            return null;
        }

        // First observation (no prior baseline)
        if ($fromNorm === '') {
            return null;
        }

        // No actual change
        if ($fromNorm === $toNorm) {
            return null;
        }

        $fromRank = self::rank($from);
        $toRank = self::rank($to);

        // Moving INTO Disputed or Refuted — existing variant interpretations are
        // contradicted; this warrants urgent re-review.
        if (in_array($toRank, [1, 2], true) && ! in_array($fromRank, [1, 2], true)) {
            return 'high';
        }

        // Upgrade from weak evidence (≤3) to strong evidence (≥5) — a gene-disease
        // link that was previously uncertain is now well-validated; previously
        // dismissed variants may be clinically actionable.
        if ($toRank >= 5 && $fromRank <= 3) {
            return 'high';
        }

        // Any other recognised change
        return 'medium';
    }
}
