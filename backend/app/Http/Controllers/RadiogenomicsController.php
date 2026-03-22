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
            // Non-oncology pharmacogenomics
            ['gene_symbol' => 'TTR', 'drug_name' => 'Tafamidis', 'relationship' => 'sensitive', 'mechanism' => 'TTR tetramer stabilization', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for ATTR cardiomyopathy'],
            ['gene_symbol' => 'TTR', 'drug_name' => 'Patisiran', 'relationship' => 'sensitive', 'mechanism' => 'TTR mRNA silencing via siRNA', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for hATTR polyneuropathy'],
            ['gene_symbol' => 'TSC2', 'drug_name' => 'Everolimus', 'relationship' => 'sensitive', 'mechanism' => 'mTOR inhibition downstream of TSC1/TSC2', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for TSC-associated SEGA and renal AML'],
            ['gene_symbol' => 'VHL', 'drug_name' => 'Belzutifan', 'relationship' => 'sensitive', 'mechanism' => 'HIF-2α inhibition in VHL-deficient cells', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for VHL-associated RCC, hemangioblastoma, pNET'],
            ['gene_symbol' => 'VHL', 'drug_name' => 'Sunitinib', 'relationship' => 'sensitive', 'mechanism' => 'Multi-kinase VEGFR inhibition', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for VHL-associated clear cell RCC'],
            ['gene_symbol' => 'ENG', 'drug_name' => 'Bevacizumab', 'relationship' => 'sensitive', 'mechanism' => 'Anti-VEGF reduces AVM bleeding in HHT', 'evidence_level' => 'Level 2A', 'evidence_summary' => 'Off-label for HHT epistaxis and GI bleeding'],
            ['gene_symbol' => 'UBA1', 'drug_name' => 'Azacitidine', 'relationship' => 'sensitive', 'mechanism' => 'Hypomethylating agent targets clonal hematopoiesis', 'evidence_level' => 'Level 2B', 'evidence_summary' => 'Emerging treatment for VEXAS syndrome'],
            ['gene_symbol' => 'PCSK9', 'drug_name' => 'Evolocumab', 'relationship' => 'sensitive', 'mechanism' => 'PCSK9 inhibition increases LDL receptor recycling', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for familial hypercholesterolemia'],
            ['gene_symbol' => 'LDLR', 'drug_name' => 'Evolocumab', 'relationship' => 'sensitive', 'mechanism' => 'PCSK9 inhibition preserves residual LDLR function', 'evidence_level' => 'Level 1A', 'evidence_summary' => 'FDA-approved for heterozygous FH with LDLR mutations'],
            ['gene_symbol' => 'BTNL2', 'drug_name' => 'Infliximab', 'relationship' => 'sensitive', 'mechanism' => 'Anti-TNFα for refractory sarcoidosis', 'evidence_level' => 'Level 3', 'evidence_summary' => 'Off-label for cardiac and neurosarcoidosis'],
            ['gene_symbol' => 'MAP2K1', 'drug_name' => 'Trametinib', 'relationship' => 'sensitive', 'mechanism' => 'MEK1/2 inhibition', 'evidence_level' => 'Level 2A', 'evidence_summary' => 'FDA-approved with dabrafenib for BRAF V600E; active in MAP2K1-mutant histiocytosis'],
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
