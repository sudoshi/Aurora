<?php

namespace App\Services\Genomics\Acmg;

/**
 * Conservative auto-population of the SAFE ACMG criteria from data Aurora has
 * (population AF, calibrated in-silico score). Returns proposed criteria as
 * ['code','strength','data_source','evidence_value']; everything stays overridable.
 */
class AcmgAutoEvidence
{
    // Pejaver 2022 calibrated REVEL thresholds.
    private const PP3_STRONG = 0.932;

    private const PP3_MODERATE = 0.773;

    private const PP3_SUPPORTING = 0.644;

    private const BP4_STRONG = 0.016;

    private const BP4_MODERATE = 0.183;

    private const BP4_SUPPORTING = 0.290;

    public function __construct(private GeneSpecificationResolver $resolver) {}

    /**
     * @return array<int, array{code:string,strength:string,data_source:string,evidence_value:string}>
     */
    public function fromFrequency(string $gene, float $populationAf, ?string $disease = null): array
    {
        $spec = $this->resolver->resolve($gene, $disease);
        $ev = sprintf('gnomAD AF=%g', $populationAf);
        $out = [];

        if ($populationAf > $spec['af_threshold_ba1']) {
            $out[] = ['code' => 'BA1', 'strength' => 'strong', 'data_source' => 'auto:gnomad', 'evidence_value' => $ev];
        } elseif ($populationAf > $spec['af_threshold_bs1']) {
            $out[] = ['code' => 'BS1', 'strength' => 'strong', 'data_source' => 'auto:gnomad', 'evidence_value' => $ev];
        } elseif ($populationAf <= $spec['af_threshold_pm2']) {
            $out[] = ['code' => 'PM2', 'strength' => 'supporting', 'data_source' => 'auto:gnomad', 'evidence_value' => $ev];
        }

        return $out;
    }

    /**
     * @return array<int, array{code:string,strength:string,data_source:string,evidence_value:string}>
     */
    public function fromInSilico(float $revel): array
    {
        $ev = sprintf('REVEL=%g', $revel);

        $strength = match (true) {
            $revel >= self::PP3_STRONG => 'strong',
            $revel >= self::PP3_MODERATE => 'moderate',
            $revel >= self::PP3_SUPPORTING => 'supporting',
            default => null,
        };
        if ($strength !== null) {
            return [['code' => 'PP3', 'strength' => $strength, 'data_source' => 'auto:insilico', 'evidence_value' => $ev]];
        }

        $strength = match (true) {
            $revel <= self::BP4_STRONG => 'strong',
            $revel <= self::BP4_MODERATE => 'moderate',
            $revel <= self::BP4_SUPPORTING => 'supporting',
            default => null,
        };
        if ($strength !== null) {
            return [['code' => 'BP4', 'strength' => $strength, 'data_source' => 'auto:insilico', 'evidence_value' => $ev]];
        }

        return [];
    }
}
