<?php

namespace App\Services\Genomics\Reanalysis;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canonicalizes a variant against the ClinGen Allele Registry (reg.clinicalgenome.org),
 * returning the CAID and harvested cross-references (ClinVar VariationID, dbSNP).
 * GET lookups are public. Degrades to null on any failure — never throws into the caller.
 */
class ClinGenAlleleRegistryService
{
    /** @return array{caid:?string, clinvar_variation_id:?string, dbsnp_rs:?string}|null */
    public function resolveByHgvs(string $hgvs): ?array
    {
        $base = (string) config('services.clingen_ar.base');
        try {
            $response = Http::timeout(15)->acceptJson()->get($base.'/allele', ['hgvs' => $hgvs]);
            if (! $response->successful()) {
                return null;
            }

            return $this->parse($response->json());
        } catch (\Throwable $e) {
            Log::warning('ClinGen AR lookup failed: '.$e->getMessage());

            return null;
        }
    }

    /** @param array<string,mixed> $body @return array{caid:?string, clinvar_variation_id:?string, dbsnp_rs:?string} */
    private function parse(array $body): array
    {
        $id = (string) ($body['@id'] ?? '');
        $caid = preg_match('#/allele/(CA\d+)#', $id, $m) ? $m[1] : null;

        $ext = $body['externalRecords'] ?? [];
        $variationId = $ext['ClinVarVariations'][0]['variationId'] ?? null;
        $rs = $ext['dbSNP'][0]['rs'] ?? null;

        return [
            'caid' => $caid,
            'clinvar_variation_id' => $variationId !== null ? (string) $variationId : null,
            'dbsnp_rs' => $rs !== null ? 'rs'.$rs : null,
        ];
    }
}
