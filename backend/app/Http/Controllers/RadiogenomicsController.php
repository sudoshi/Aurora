<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Services\RadiogenomicsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RadiogenomicsController extends Controller
{
    public function __construct(
        private readonly RadiogenomicsService $service
    ) {}

    /**
     * GET /radiogenomics/patients/{patientId}
     */
    public function patientPanel(int $patientId): JsonResponse
    {
        $panel = $this->service->getPatientPanel($patientId);
        if (empty($panel)) {
            return ApiResponse::error('Patient not found', 404);
        }
        return ApiResponse::success($panel, 'Radiogenomics panel retrieved');
    }

    /**
     * GET /radiogenomics/variant-drug-interactions
     * Returns known variant-drug interaction database (hardcoded reference).
     */
    public function variantDrugInteractions(Request $request): JsonResponse
    {
        // Hardcoded reference database of gene-drug interactions
        $interactions = [
            ['gene_symbol' => 'BRAF', 'drug_name' => 'Vemurafenib', 'relationship' => 'sensitive', 'mechanism' => 'BRAF V600E kinase inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for BRAF V600E-mutant melanoma'],
            ['gene_symbol' => 'BRAF', 'drug_name' => 'Dabrafenib', 'relationship' => 'sensitive', 'mechanism' => 'BRAF V600 kinase inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for BRAF V600-mutant melanoma and NSCLC'],
            ['gene_symbol' => 'KRAS', 'drug_name' => 'Cetuximab', 'relationship' => 'resistant', 'mechanism' => 'KRAS activation bypasses EGFR blockade', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'KRAS mutations predict resistance to anti-EGFR therapy in CRC'],
            ['gene_symbol' => 'KRAS', 'drug_name' => 'Sotorasib', 'relationship' => 'sensitive', 'mechanism' => 'Covalent KRAS G12C inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for KRAS G12C-mutant NSCLC'],
            ['gene_symbol' => 'EGFR', 'drug_name' => 'Osimertinib', 'relationship' => 'sensitive', 'mechanism' => 'Third-gen EGFR TKI', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for EGFR-mutant NSCLC including T790M'],
            ['gene_symbol' => 'EGFR', 'drug_name' => 'Erlotinib', 'relationship' => 'sensitive', 'mechanism' => 'First-gen EGFR TKI', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for EGFR exon 19del/L858R NSCLC'],
            ['gene_symbol' => 'ALK', 'drug_name' => 'Alectinib', 'relationship' => 'sensitive', 'mechanism' => 'ALK inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for ALK-positive NSCLC'],
            ['gene_symbol' => 'HER2', 'drug_name' => 'Trastuzumab', 'relationship' => 'sensitive', 'mechanism' => 'HER2 monoclonal antibody', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for HER2-positive breast cancer'],
            ['gene_symbol' => 'BRCA1', 'drug_name' => 'Olaparib', 'relationship' => 'sensitive', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for BRCA-mutant ovarian/breast cancer'],
            ['gene_symbol' => 'BRCA2', 'drug_name' => 'Olaparib', 'relationship' => 'sensitive', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for BRCA-mutant ovarian/breast/prostate cancer'],
            ['gene_symbol' => 'PIK3CA', 'drug_name' => 'Alpelisib', 'relationship' => 'sensitive', 'mechanism' => 'PI3K alpha-selective inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for PIK3CA-mutant HR+/HER2- breast cancer'],
            ['gene_symbol' => 'NTRK1', 'drug_name' => 'Larotrectinib', 'relationship' => 'sensitive', 'mechanism' => 'TRK inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for NTRK fusion-positive solid tumors'],
        ];

        // Apply filters
        $gene = $request->input('gene');
        $drug = $request->input('drug');
        $relationship = $request->input('relationship');

        $filtered = collect($interactions);
        if ($gene) {
            $filtered = $filtered->filter(fn ($i) => stripos($i['gene_symbol'], $gene) !== false);
        }
        if ($drug) {
            $filtered = $filtered->filter(fn ($i) => stripos($i['drug_name'], $drug) !== false);
        }
        if ($relationship) {
            $filtered = $filtered->where('relationship', $relationship);
        }

        return ApiResponse::success($filtered->values()->toArray(), 'Variant-drug interactions retrieved');
    }
}
