<?php

namespace App\Services\Matchmaker;

use App\Models\Clinical\GenomicVariant;
use App\Models\DiagnosticOdyssey;

class MmeProfileSerializer
{
    /** @return array{patient: array<string,mixed>} */
    public function serialize(DiagnosticOdyssey $odyssey): array
    {
        $contact = config('services.mme.contact');

        $features = $odyssey->phenotypeFeatures()->get()->map(fn ($f) => array_filter([
            'id' => $f->hpo_id,
            'label' => $f->hpo_label,
            'observed' => $f->excluded ? 'no' : 'yes',
            'ageOfOnset' => $f->onset_hpo_id,
        ], fn ($v) => $v !== null && $v !== ''))->values()->all();

        $genomicFeatures = GenomicVariant::where('patient_id', $odyssey->patient_id)
            ->whereNotNull('gene')
            ->get()
            ->map(fn ($v) => array_filter([
                'gene' => ['id' => $v->gene],
                'variant' => array_filter([
                    'assembly' => 'GRCh38',
                    'referenceName' => $v->chromosome ? ltrim((string) $v->chromosome, 'chr') : null,
                    'start' => $v->position !== null ? (int) $v->position - 1 : null,
                    'referenceBases' => $v->ref_allele,
                    'alternateBases' => $v->alt_allele,
                ], fn ($x) => $x !== null && $x !== ''),
                'zygosity' => str_contains(strtolower((string) $v->zygosity), 'homo') ? 2 : 1,
            ], fn ($x) => $x !== null && $x !== []))->values()->all();

        return [
            'patient' => array_filter([
                // De-identified (D2): no free-text label/title — it can carry a
                // name or MRN, and MME crosses the institution boundary in both
                // directions. Identity is the pseudonymous odyssey id; matching
                // uses HPO features + variants with contact-mediated follow-up.
                'id' => 'aurora-odyssey-'.$odyssey->id,
                'contact' => $contact,
                'species' => 'NCBITaxon:9606',
                'features' => $features,
                'genomicFeatures' => $genomicFeatures,
            ], fn ($v) => $v !== null && $v !== []),
        ];
    }
}
