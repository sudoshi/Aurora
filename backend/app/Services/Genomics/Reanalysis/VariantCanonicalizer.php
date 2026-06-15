<?php

namespace App\Services\Genomics\Reanalysis;

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantCanonicalId;

/**
 * Assigns a canonical identity to a genomic variant: a CAID + ClinVar VariationID
 * from the ClinGen Allele Registry when an HGVS string is available, otherwise a
 * coordinate match against the locally-synced ClinVar table. Also computes a
 * GA4GH VRS Allele ID (ga4gh:VA.…) via AnyVar when ANYVAR_URL is configured.
 * Persists a baseline ClinVar-significance snapshot used by the reanalysis loop.
 */
class VariantCanonicalizer
{
    public function __construct(
        private ClinGenAlleleRegistryService $registry,
        private VrsService $vrs,
    ) {}

    public function canonicalize(GenomicVariant $variant): VariantCanonicalId
    {
        $caid = null;
        $variationId = null;
        $dbsnp = null;

        $hgvs = $this->hgvsFor($variant);
        if ($hgvs !== null) {
            $resolved = $this->registry->resolveByHgvs($hgvs);
            if ($resolved !== null) {
                $caid = $resolved['caid'];
                $variationId = $resolved['clinvar_variation_id'];
                $dbsnp = $resolved['dbsnp_rs'];
            }
        }

        $vrsId = $hgvs !== null ? $this->vrs->computeId($hgvs) : null;

        $clinvar = $this->matchClinVar($variant);
        if ($variationId === null && $clinvar !== null) {
            $variationId = $clinvar->variation_id;
        }

        return VariantCanonicalId::updateOrCreate(
            ['genomic_variant_id' => $variant->id],
            [
                'caid' => $caid,
                'clinvar_variation_id' => $variationId,
                'dbsnp_rs' => $dbsnp,
                'vrs_id' => $vrsId,
                'assembly' => 'GRCh38',
                'baseline_significance' => $clinvar?->clinical_significance,
                'baseline_review_status' => $clinvar?->review_status,
                'baselined_at' => now(),
                'canonicalized_at' => now(),
            ],
        );
    }

    private function hgvsFor(GenomicVariant $variant): ?string
    {
        $v = (string) ($variant->variant ?? '');

        return str_contains($v, ':g.') || str_contains($v, ':c.') ? $v : null;
    }

    private function matchClinVar(GenomicVariant $variant): ?ClinVarVariant
    {
        if (! $variant->chromosome || $variant->position === null) {
            return null;
        }

        return ClinVarVariant::where('chromosome', $variant->chromosome)
            ->where('position', $variant->position)
            ->where('reference_allele', $variant->ref_allele)
            ->where('alternate_allele', $variant->alt_allele)
            ->first();
    }
}
