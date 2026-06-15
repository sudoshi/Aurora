<?php

namespace App\Services\Genomics\Acmg;

/**
 * Pure ACMG/AMP point-based classifier (Tavtigian 2018 / ClinGen SVI 2020).
 * Input: list of ['code' => string, 'strength' => AcmgStrength].
 */
class AcmgClassifier
{
    /**
     * @param  array<int, array{code:string, strength:AcmgStrength}>  $applied
     * @return array{classification:string, points:int, standalone_benign:bool}
     */
    public function classify(array $applied): array
    {
        $points = 0;
        $standaloneBenign = false;

        foreach ($applied as $c) {
            $category = AcmgCriteriaCatalog::category($c['code']);
            if ($category === 'benign' && AcmgCriteriaCatalog::isStandalone($c['code'])) {
                $standaloneBenign = true;
            }
            $magnitude = $c['strength']->points();
            $points += $category === 'pathogenic' ? $magnitude : -$magnitude;
        }

        return [
            'classification' => $standaloneBenign ? 'benign' : $this->fromPoints($points),
            'points' => $points,
            'standalone_benign' => $standaloneBenign,
        ];
    }

    private function fromPoints(int $points): string
    {
        return match (true) {
            $points >= 10 => 'pathogenic',
            $points >= 6 => 'likely_pathogenic',
            $points >= 0 => 'vus',
            $points >= -6 => 'likely_benign',
            default => 'benign',
        };
    }
}
