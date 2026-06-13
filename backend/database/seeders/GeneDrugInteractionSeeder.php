<?php

namespace Database\Seeders;

use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Database\Seeder;

class GeneDrugInteractionSeeder extends Seeder
{
    public function run(): void
    {
        $interactions = [
            // --- Oncology (Level 1A / FDA-approved) ---
            ['gene' => 'BRAF', 'drug' => 'Vemurafenib', 'drug_class' => 'BRAF inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'BRAF V600E kinase inhibition', 'indication' => 'FDA-approved for BRAF V600E-mutant melanoma', 'source' => 'oncokb'],
            ['gene' => 'BRAF', 'drug' => 'Dabrafenib', 'drug_class' => 'BRAF inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'BRAF V600 kinase inhibition', 'indication' => 'FDA-approved for BRAF V600-mutant melanoma and NSCLC', 'source' => 'oncokb'],
            ['gene' => 'KRAS', 'drug' => 'Cetuximab', 'drug_class' => 'Anti-EGFR antibody', 'relationship' => 'resistant', 'evidence_level' => '1A', 'mechanism' => 'KRAS activation bypasses EGFR blockade', 'indication' => 'KRAS mutations predict resistance to anti-EGFR therapy in CRC', 'source' => 'oncokb'],
            ['gene' => 'KRAS', 'drug' => 'Panitumumab', 'drug_class' => 'Anti-EGFR antibody', 'relationship' => 'resistant', 'evidence_level' => '1A', 'mechanism' => 'KRAS activation bypasses EGFR blockade', 'indication' => 'KRAS mutations predict resistance in CRC', 'source' => 'oncokb'],
            ['gene' => 'KRAS', 'drug' => 'Sotorasib', 'drug_class' => 'KRAS G12C inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'Covalent KRAS G12C inhibition', 'indication' => 'FDA-approved for KRAS G12C-mutant NSCLC', 'source' => 'oncokb', 'variant_pattern' => 'G12C'],
            ['gene' => 'EGFR', 'drug' => 'Osimertinib', 'drug_class' => 'EGFR TKI (3rd gen)', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'Third-gen EGFR TKI', 'indication' => 'FDA-approved for EGFR-mutant NSCLC including T790M', 'source' => 'oncokb'],
            ['gene' => 'EGFR', 'drug' => 'Erlotinib', 'drug_class' => 'EGFR TKI (1st gen)', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'First-gen EGFR TKI', 'indication' => 'FDA-approved for EGFR exon 19del/L858R NSCLC', 'source' => 'oncokb'],
            ['gene' => 'EGFR', 'drug' => 'Gefitinib', 'drug_class' => 'EGFR TKI (1st gen)', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'First-gen EGFR TKI', 'indication' => 'FDA-approved for EGFR-mutant NSCLC', 'source' => 'oncokb'],
            ['gene' => 'ALK', 'drug' => 'Alectinib', 'drug_class' => 'ALK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'ALK inhibition', 'indication' => 'FDA-approved for ALK-positive NSCLC', 'source' => 'oncokb'],
            ['gene' => 'ALK', 'drug' => 'Crizotinib', 'drug_class' => 'ALK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'ALK/ROS1/MET inhibition', 'indication' => 'FDA-approved for ALK-positive NSCLC', 'source' => 'oncokb'],
            ['gene' => 'HER2', 'drug' => 'Trastuzumab', 'drug_class' => 'Anti-HER2 antibody', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'HER2 monoclonal antibody', 'indication' => 'FDA-approved for HER2-positive breast cancer', 'source' => 'oncokb'],
            ['gene' => 'HER2', 'drug' => 'Pertuzumab', 'drug_class' => 'Anti-HER2 antibody', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'HER2 dimerization inhibitor', 'indication' => 'FDA-approved for HER2-positive breast cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA1', 'drug' => 'Olaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian/breast cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA1', 'drug' => 'Rucaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA2', 'drug' => 'Olaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian/breast/prostate cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA2', 'drug' => 'Rucaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian cancer', 'source' => 'oncokb'],
            ['gene' => 'PIK3CA', 'drug' => 'Alpelisib', 'drug_class' => 'PI3K inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PI3K alpha-selective inhibition', 'indication' => 'FDA-approved for PIK3CA-mutant HR+/HER2- breast cancer', 'source' => 'oncokb'],
            ['gene' => 'NTRK1', 'drug' => 'Larotrectinib', 'drug_class' => 'TRK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TRK inhibition', 'indication' => 'FDA-approved for NTRK fusion-positive solid tumors', 'source' => 'oncokb'],
            ['gene' => 'NTRK1', 'drug' => 'Entrectinib', 'drug_class' => 'TRK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TRK/ROS1/ALK inhibition', 'indication' => 'FDA-approved for NTRK fusion-positive solid tumors', 'source' => 'oncokb'],
            // --- Oncology (Level 2+) ---
            ['gene' => 'TP53', 'drug' => 'Cisplatin', 'drug_class' => 'Platinum agent', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'TP53 loss may increase platinum sensitivity in some contexts', 'indication' => 'Context-dependent — varies by tumor type', 'source' => 'manual'],
            ['gene' => 'MAP2K1', 'drug' => 'Trametinib', 'drug_class' => 'MEK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'MEK1/2 inhibition', 'indication' => 'FDA-approved with dabrafenib for BRAF V600E; active in MAP2K1-mutant histiocytosis', 'source' => 'oncokb'],
            ['gene' => 'MAP2K1', 'drug' => 'Cobimetinib', 'drug_class' => 'MEK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'MEK1 inhibition', 'indication' => 'FDA-approved with vemurafenib for BRAF V600-mutant melanoma', 'source' => 'oncokb'],
            ['gene' => 'DNMT3A', 'drug' => 'Azacitidine', 'drug_class' => 'Hypomethylating agent', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'Hypomethylating agent targets clonal hematopoiesis', 'indication' => 'AML/MDS with DNMT3A mutations', 'source' => 'manual'],
            ['gene' => 'DNMT3A', 'drug' => 'Decitabine', 'drug_class' => 'Hypomethylating agent', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'DNA methyltransferase inhibition', 'indication' => 'AML/MDS with DNMT3A mutations', 'source' => 'manual'],
            ['gene' => 'VHL', 'drug' => 'Bevacizumab', 'drug_class' => 'Anti-VEGF antibody', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'Anti-VEGF reduces angiogenesis in VHL-deficient tumors', 'indication' => 'VHL-associated RCC', 'source' => 'oncokb'],
            ['gene' => 'ENG', 'drug' => 'Bevacizumab', 'drug_class' => 'Anti-VEGF antibody', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'Anti-VEGF reduces AVM bleeding in HHT', 'indication' => 'Off-label for HHT epistaxis and GI bleeding', 'source' => 'manual'],
            ['gene' => 'ENG', 'drug' => 'Thalidomide', 'drug_class' => 'Immunomodulator', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'Anti-angiogenic for HHT', 'indication' => 'Off-label for HHT', 'source' => 'manual'],
            // --- Non-oncology pharmacogenomics ---
            ['gene' => 'TTR', 'drug' => 'Tafamidis', 'drug_class' => 'TTR stabilizer', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TTR tetramer stabilization', 'indication' => 'FDA-approved for ATTR cardiomyopathy', 'source' => 'fda'],
            ['gene' => 'TTR', 'drug' => 'Patisiran', 'drug_class' => 'siRNA', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TTR mRNA silencing via siRNA', 'indication' => 'FDA-approved for hATTR polyneuropathy', 'source' => 'fda'],
            ['gene' => 'TTR', 'drug' => 'Diflunisal', 'drug_class' => 'NSAID/TTR stabilizer', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'TTR tetramer stabilization (off-label)', 'indication' => 'Off-label for ATTR', 'source' => 'manual'],
            ['gene' => 'TSC2', 'drug' => 'Everolimus', 'drug_class' => 'mTOR inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'mTOR inhibition downstream of TSC1/TSC2', 'indication' => 'FDA-approved for TSC-associated SEGA and renal AML', 'source' => 'fda'],
            ['gene' => 'TSC2', 'drug' => 'Sirolimus', 'drug_class' => 'mTOR inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'mTOR inhibition', 'indication' => 'TSC-associated lymphangioleiomyomatosis', 'source' => 'fda'],
            ['gene' => 'VHL', 'drug' => 'Belzutifan', 'drug_class' => 'HIF-2a inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'HIF-2a inhibition in VHL-deficient cells', 'indication' => 'FDA-approved for VHL-associated RCC, hemangioblastoma, pNET', 'source' => 'fda'],
            ['gene' => 'VHL', 'drug' => 'Sunitinib', 'drug_class' => 'Multi-kinase inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'Multi-kinase VEGFR inhibition', 'indication' => 'FDA-approved for VHL-associated clear cell RCC', 'source' => 'fda'],
            ['gene' => 'UBA1', 'drug' => 'Azacitidine', 'drug_class' => 'Hypomethylating agent', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'Hypomethylating agent targets clonal hematopoiesis', 'indication' => 'Emerging treatment for VEXAS syndrome', 'source' => 'manual'],
            ['gene' => 'UBA1', 'drug' => 'Ruxolitinib', 'drug_class' => 'JAK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '3', 'mechanism' => 'JAK1/2 inhibition for inflammatory component', 'indication' => 'Emerging for VEXAS', 'source' => 'manual'],
            ['gene' => 'PCSK9', 'drug' => 'Evolocumab', 'drug_class' => 'PCSK9 inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PCSK9 inhibition increases LDL receptor recycling', 'indication' => 'FDA-approved for familial hypercholesterolemia', 'source' => 'fda'],
            ['gene' => 'PCSK9', 'drug' => 'Alirocumab', 'drug_class' => 'PCSK9 inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PCSK9 inhibition', 'indication' => 'FDA-approved for familial hypercholesterolemia', 'source' => 'fda'],
            ['gene' => 'LDLR', 'drug' => 'Evolocumab', 'drug_class' => 'PCSK9 inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PCSK9 inhibition preserves residual LDLR function', 'indication' => 'FDA-approved for heterozygous FH with LDLR mutations', 'source' => 'fda'],
            ['gene' => 'LDLR', 'drug' => 'Atorvastatin', 'drug_class' => 'Statin', 'relationship' => 'dose_adjustment', 'evidence_level' => '1A', 'mechanism' => 'Partial LDL reduction; depends on residual LDLR', 'indication' => 'First-line for FH but response varies with mutation', 'source' => 'nccn'],
            ['gene' => 'BTNL2', 'drug' => 'Infliximab', 'drug_class' => 'Anti-TNFa', 'relationship' => 'sensitive', 'evidence_level' => '3', 'mechanism' => 'Anti-TNFa for refractory sarcoidosis', 'indication' => 'Off-label for cardiac and neurosarcoidosis', 'source' => 'manual'],
            ['gene' => 'BTNL2', 'drug' => 'Methotrexate', 'drug_class' => 'Antimetabolite', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'Immunosuppression for sarcoidosis', 'indication' => 'Second-line for sarcoidosis', 'source' => 'manual'],
        ];

        foreach ($interactions as $entry) {
            GeneDrugInteraction::updateOrCreate(
                [
                    'gene' => $entry['gene'],
                    'variant_pattern' => $entry['variant_pattern'] ?? '*',
                    'drug' => $entry['drug'],
                ],
                array_merge(
                    ['variant_pattern' => '*'],
                    $entry,
                    ['last_verified_at' => now()]
                )
            );
        }

        $this->command->info('Seeded '.count($interactions).' gene-drug interactions.');
    }
}
