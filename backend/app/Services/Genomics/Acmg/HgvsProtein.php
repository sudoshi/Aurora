<?php

namespace App\Services\Genomics\Acmg;

/**
 * Minimal HGVS protein-substitution parser, sufficient for ACMG PS1/PM5
 * same-residue logic. Returns single-letter ref/alt amino acids + position,
 * or null when the input is not a simple missense substitution.
 */
class HgvsProtein
{
    private const THREE_TO_ONE = [
        'Ala' => 'A', 'Arg' => 'R', 'Asn' => 'N', 'Asp' => 'D', 'Cys' => 'C',
        'Gln' => 'Q', 'Glu' => 'E', 'Gly' => 'G', 'His' => 'H', 'Ile' => 'I',
        'Leu' => 'L', 'Lys' => 'K', 'Met' => 'M', 'Phe' => 'F', 'Pro' => 'P',
        'Ser' => 'S', 'Thr' => 'T', 'Trp' => 'W', 'Tyr' => 'Y', 'Val' => 'V',
    ];

    /** @return array{ref:string, position:int, alt:string}|null */
    public static function parse(?string $hgvs): ?array
    {
        if ($hgvs === null) {
            return null;
        }

        if (str_contains($hgvs, 'p.')) {
            $hgvs = substr($hgvs, strpos($hgvs, 'p.'));
        }
        $expr = ltrim($hgvs, 'p.()');

        // Three-letter form: ArgNNNHis
        if (preg_match('/^([A-Z][a-z]{2})(\d+)([A-Z][a-z]{2})$/', $expr, $m)) {
            $ref = self::THREE_TO_ONE[$m[1]] ?? null;
            $alt = self::THREE_TO_ONE[$m[3]] ?? null;
            if ($ref && $alt) {
                return ['ref' => $ref, 'position' => (int) $m[2], 'alt' => $alt];
            }

            return null;
        }

        // One-letter form: RNNNH
        if (preg_match('/^([A-Z])(\d+)([A-Z])$/', $expr, $m)) {
            $valid = array_values(self::THREE_TO_ONE);
            if (in_array($m[1], $valid, true) && in_array($m[3], $valid, true)) {
                return ['ref' => $m[1], 'position' => (int) $m[2], 'alt' => $m[3]];
            }
        }

        return null;
    }
}
