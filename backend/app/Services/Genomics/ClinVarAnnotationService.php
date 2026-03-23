<?php

namespace App\Services\Genomics;

use App\Models\Clinical\GenomicVariant;
use Illuminate\Support\Facades\DB;

/**
 * Cross-references uploaded variants against the local ClinVar cache
 * and fills in clinvar significance, disease, and review status.
 */
class ClinVarAnnotationService
{
    /**
     * Annotate all genomic variants for a patient that lack ClinVar data.
     *
     * @return array{annotated: int, skipped: int}
     */
    public function annotateByPatient(int $patientId): array
    {
        $annotated = DB::update("
            UPDATE clinical.genomic_variants gv
            SET
                clinical_significance = cv.clinical_significance,
                clinvar_disease = cv.disease_name,
                clinvar_review_status = cv.review_status,
                updated_at = NOW()
            FROM clinical.clinvar_variants cv
            WHERE gv.patient_id = ?
              AND gv.chromosome = cv.chromosome
              AND gv.position = cv.position
              AND gv.ref_allele = cv.reference_allele
              AND gv.alt_allele = cv.alternate_allele
              AND gv.clinical_significance IS NULL
        ", [$patientId]);

        $total = GenomicVariant::where('patient_id', $patientId)->count();

        return ['annotated' => $annotated, 'skipped' => $total - $annotated];
    }

    /**
     * Annotate all variants across all patients that lack ClinVar data.
     *
     * @return array{annotated: int, skipped: int}
     */
    public function annotateAll(): array
    {
        $annotated = DB::update("
            UPDATE clinical.genomic_variants gv
            SET
                clinical_significance = cv.clinical_significance,
                clinvar_disease = cv.disease_name,
                clinvar_review_status = cv.review_status,
                updated_at = NOW()
            FROM clinical.clinvar_variants cv
            WHERE gv.chromosome = cv.chromosome
              AND gv.position = cv.position
              AND gv.ref_allele = cv.reference_allele
              AND gv.alt_allele = cv.alternate_allele
              AND gv.clinical_significance IS NULL
        ");

        $total = GenomicVariant::count();

        return ['annotated' => $annotated, 'skipped' => $total - $annotated];
    }
}
