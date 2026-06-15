<?php

namespace App\Services\Genomics\Acmg;

use App\Models\Clinical\AcmgGeneSpecification;

/**
 * Resolves the effective ACMG ruleset for a gene: baseline ACMG-2015/SVI-2020
 * thresholds, overridden by a ClinGen CSpec/VCEP gene specification when one exists.
 */
class GeneSpecificationResolver
{
    private const BASELINE_BA1 = 0.05;

    private const BASELINE_BS1 = 0.01;

    private const BASELINE_PM2 = 0.0001;

    /**
     * @return array{spec_id:?string, spec_version:?string, overrides:array<string,mixed>, af_threshold_ba1:float, af_threshold_bs1:float, af_threshold_pm2:float}
     */
    public function resolve(string $gene, ?string $disease = null): array
    {
        $query = AcmgGeneSpecification::where('gene_symbol', $gene);
        if ($disease !== null) {
            $query->where(fn ($q) => $q->where('disease', $disease)->orWhereNull('disease'));
        }
        $spec = $query->orderByDesc('spec_version')->first();

        $overrides = $spec?->criteria_overrides ?? [];

        return [
            'spec_id' => $spec?->spec_id,
            'spec_version' => $spec?->spec_version,
            'overrides' => $overrides,
            'af_threshold_ba1' => (float) ($overrides['BA1']['af_threshold'] ?? self::BASELINE_BA1),
            'af_threshold_bs1' => (float) ($overrides['BS1']['af_threshold'] ?? self::BASELINE_BS1),
            'af_threshold_pm2' => (float) ($overrides['PM2']['af_threshold'] ?? self::BASELINE_PM2),
        ];
    }

    /** A criterion is applicable unless a gene spec explicitly disables it. */
    public function isApplicable(array $resolved, string $code): bool
    {
        return (bool) ($resolved['overrides'][$code]['applicable'] ?? true);
    }
}
