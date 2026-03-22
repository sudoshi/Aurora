<?php

namespace Database\Seeders\DemoPatients;

class OncologyPatient3_TNBC_BRCA1
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-ON-003',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'date_of_birth' => '1985-04-14',
            'sex' => 'Female',
            'race' => 'Asian',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-PS-63291');
        $this->addIdentifier($patient, 'hospital_mrn', 'WBC-445128', 'Breast Center');

        // ── Conditions ──────────────────────────────────────────

        $this->addCondition($patient, [
            'concept_name' => 'Invasive carcinoma left breast, triple-negative',
            'concept_code' => 'C50.912',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2021-09-01',
            'severity' => 'severe',
            'body_site' => 'Left breast',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Left axillary lymph node metastasis',
            'concept_code' => 'C77.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'resolved',
            'onset_date' => '2021-09-01',
            'resolved_date' => '2022-03-28',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Lung metastasis',
            'concept_code' => 'C78.01',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Liver metastasis',
            'concept_code' => 'C78.7',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Adrenal metastasis',
            'concept_code' => 'C79.71',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2025-12-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Immune-mediated hypothyroidism',
            'concept_code' => 'E03.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2021-12-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Left upper extremity lymphedema',
            'concept_code' => 'I89.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2022-05-01',
            'laterality' => 'left',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'BRCA1 carrier status',
            'concept_code' => 'Z15.01',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2021-10-01',
        ]);

        // ── Medications ─────────────────────────────────────────

        // Neoadjuvant Phase 1 (weeks 1-12)
        $this->addMedication($patient, [
            'drug_name' => 'Pembrolizumab 200mg IV Q3W',
            'concept_code' => '1547545',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2021-10-18',
            'end_date' => '2022-03-14',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Paclitaxel 80mg/m² IV weekly',
            'concept_code' => '56946',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2021-10-18',
            'end_date' => '2022-01-03',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Carboplatin AUC5 IV Q3W',
            'concept_code' => '40048',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2021-10-18',
            'end_date' => '2022-01-03',
        ]);

        // Neoadjuvant Phase 2 (weeks 13-24)
        $this->addMedication($patient, [
            'drug_name' => 'Doxorubicin 60mg/m² IV Q3W',
            'concept_code' => '3639',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2022-01-10',
            'end_date' => '2022-03-07',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Cyclophosphamide 600mg/m² IV Q3W',
            'concept_code' => '3002',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2022-01-10',
            'end_date' => '2022-03-07',
        ]);

        // Adjuvant
        $this->addMedication($patient, [
            'drug_name' => 'Pembrolizumab 200mg IV Q3W (adjuvant, completing 1yr total)',
            'concept_code' => '1547545',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2022-05-02',
            'end_date' => '2023-02-20',
        ]);

        // Metastatic Line 1
        $this->addMedication($patient, [
            'drug_name' => 'Olaparib 300mg PO BID',
            'concept_code' => '1597561',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2024-07-08',
            'end_date' => '2025-12-15',
        ]);

        // Metastatic Line 2
        $this->addMedication($patient, [
            'drug_name' => 'Sacituzumab govitecan 10mg/kg IV D1,8 Q21d (dose reduced to 7.5mg/kg after FN)',
            'concept_code' => '2390755',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2026-01-12',
        ]);

        // Supportive
        $this->addMedication($patient, [
            'drug_name' => 'Levothyroxine 50mcg PO daily',
            'concept_code' => '10582',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2022-01-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Ondansetron 8mg PO PRN',
            'concept_code' => '26225',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2021-10-18',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Pegfilgrastim 6mg SQ',
            'concept_code' => '301667',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2021-12-10',
            'end_date' => '2022-03-07',
        ]);

        // ── Procedures ──────────────────────────────────────────

        $this->addProcedure($patient, [
            'procedure_name' => 'Core needle biopsy left breast',
            'concept_code' => '19083',
            'vocabulary' => 'CPT',
            'performed_at' => '2021-09-22',
            'specialty' => 'Breast Surgery',
            'body_site' => 'Left breast',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Axillary FNA',
            'concept_code' => '10021',
            'vocabulary' => 'CPT',
            'performed_at' => '2021-09-24',
            'specialty' => 'Breast Surgery',
            'laterality' => 'left',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Oocyte cryopreservation',
            'concept_code' => '89258',
            'vocabulary' => 'CPT',
            'performed_at' => '2021-10-10',
            'specialty' => 'Reproductive Endocrinology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Left modified radical mastectomy + ALND',
            'concept_code' => '19307',
            'vocabulary' => 'CPT',
            'performed_at' => '2022-03-28',
            'specialty' => 'Breast Surgery',
            'laterality' => 'left',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Sentinel lymph node biopsy',
            'concept_code' => '38525',
            'vocabulary' => 'CPT',
            'performed_at' => '2022-03-28',
            'specialty' => 'Breast Surgery',
        ]);

        // ── Visits ──────────────────────────────────────────────

        // Breast Surgery — biopsy
        $biopsyVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2021-09-22',
            'department' => 'Breast Surgery',
            'provider_name' => 'Dr. Anita Desai',
            'reason' => 'Core needle biopsy of palpable left breast mass and axillary lymphadenopathy',
        ]);

        // Genetic Counseling
        $geneticVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-10-05',
            'department' => 'Genetic Counseling',
            'provider_name' => 'Sarah Kim, MS CGC',
            'reason' => 'Germline genetic testing — young-onset TNBC, family history of breast and ovarian cancer',
        ]);

        // Reproductive Endocrinology — fertility preservation
        $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2021-10-10',
            'department' => 'Reproductive Endocrinology',
            'provider_name' => 'Dr. Maya Patel',
            'reason' => 'Oocyte cryopreservation prior to gonadotoxic chemotherapy',
        ]);

        // Medical Oncology — neoadjuvant initiation
        $neoInitVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-10-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Neoadjuvant KEYNOTE-522 regimen initiation — pembrolizumab + paclitaxel + carboplatin',
        ]);

        // Medical Oncology — mid-neoadjuvant
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-01-14',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Mid-neoadjuvant assessment — breast MRI showing 50% reduction, transitioning to AC',
        ]);

        // Medical Oncology — pre-surgery
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-03-14',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Pre-surgical restaging — breast MRI 69% reduction, plan mastectomy',
        ]);

        // Breast Surgery — mastectomy (inpatient)
        $surgeryVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2022-03-28',
            'visit_end_date' => '2022-03-31',
            'department' => 'Breast Surgery',
            'provider_name' => 'Dr. Anita Desai',
            'reason' => 'Left modified radical mastectomy with axillary lymph node dissection',
        ]);

        // Physical Therapy — lymphedema
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-05-15',
            'department' => 'Physical Therapy',
            'provider_name' => 'Dr. Laura Chen, PT',
            'reason' => 'Left upper extremity lymphedema evaluation and management post-ALND',
        ]);

        // Medical Oncology — adjuvant pembrolizumab
        $adjuvantVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-05-02',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Adjuvant pembrolizumab initiation — completing 1yr total per KEYNOTE-522',
        ]);

        // ED — immune colitis (inpatient)
        $colitisVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2022-09-15',
            'visit_end_date' => '2022-09-20',
            'department' => 'Emergency Medicine',
            'provider_name' => 'Dr. James Walker',
            'reason' => 'Immune-mediated colitis — grade 2 diarrhea 6-8x/day, pembrolizumab held',
        ]);

        // Endocrinology — hypothyroidism
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-06-10',
            'department' => 'Endocrinology',
            'provider_name' => 'Dr. Nisha Mehta',
            'reason' => 'Immune-mediated hypothyroidism — TSH elevated on pembrolizumab, levothyroxine titration',
        ]);

        // Medical Oncology — metastatic recurrence
        $metRecurrenceVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-06-14',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Distant recurrence — lung, liver metastases on surveillance imaging, plan staging workup',
        ]);

        // Medical Oncology — olaparib initiation
        $olaparibVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-07-08',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Line 1 metastatic — olaparib initiation for BRCA1-mutated metastatic TNBC',
        ]);

        // Medical Oncology — olaparib responding
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-09-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Restaging — partial response on olaparib, CA 15-3 declining',
        ]);

        // Medical Oncology — olaparib deep response
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-06-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Restaging — near-complete response on olaparib, CA 15-3 normalized',
        ]);

        // Medical Oncology — olaparib PD
        $olaparibPDVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-12-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Progressive disease on olaparib — lung enlarging, new adrenal met, discuss ctDNA and next line',
        ]);

        // Medical Oncology — sacituzumab govitecan initiation
        $sgInitVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-12',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Line 2 metastatic — sacituzumab govitecan initiation for metastatic TNBC post-PARP inhibitor',
        ]);

        // ED — febrile neutropenia on SG (inpatient)
        $fnVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2026-02-08',
            'visit_end_date' => '2026-02-12',
            'department' => 'Emergency Medicine',
            'provider_name' => 'Dr. Kevin Park',
            'reason' => 'Febrile neutropenia — ANC 0.2, admitted for IV antibiotics, sacituzumab govitecan held',
        ]);

        // Medical Oncology — SG responding post dose reduction
        $sgRespondingVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-03-10',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Rajesh Gupta',
            'reason' => 'Restaging — partial response on sacituzumab govitecan at reduced dose, CA 15-3 declining',
        ]);

        // ── Clinical Notes ──────────────────────────────────────

        $this->addNote($patient, [
            'visit_id' => $biopsyVisit->id,
            'note_type' => 'Pathology Report',
            'note_date' => '2021-09-26',
            'author' => 'Dr. Meena Chakraborty',
            'content' => "CORE NEEDLE BIOPSY — LEFT BREAST\n\nSpecimen: Left breast, 10 o'clock, 4 cm from nipple — 14-gauge core ×4\n\nMICROSCOPIC: Invasive carcinoma of no special type (NST), Nottingham grade 3 (tubules 3, nuclear pleomorphism 3, mitotic count 3 = score 9/9). High-grade DCIS component present. Extensive tumor-infiltrating lymphocytes (TILs, stromal ~60%).\n\nIMMUNOHISTOCHEMISTRY:\n- ER: Negative (0%, Allred 0)\n- PR: Negative (0%, Allred 0)\n- HER2: IHC 0 (no staining)\n- Ki-67: 78%\n- PD-L1 (22C3): CPS 18 (positive, ≥10 threshold for pembrolizumab)\n- CK5/6: Positive\n- EGFR: Positive\n- Androgen receptor: Negative\n\nFNA LEFT AXILLA (2021-09-24): Positive for metastatic carcinoma consistent with breast primary.\n\nDIAGNOSIS: Invasive carcinoma NST, Nottingham grade 3 (score 9), triple-negative (ER-/PR-/HER2 IHC 0), PD-L1 CPS 18, Ki-67 78%. Basal-like immunophenotype. cT2N1 (clinical 34mm mass + positive axillary node). Eligible for KEYNOTE-522 neoadjuvant regimen.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $geneticVisit->id,
            'note_type' => 'Genetic Testing Report',
            'note_date' => '2021-10-15',
            'author' => 'Invitae / Sarah Kim, MS CGC',
            'content' => "INVITAE — 84-GENE HEREDITARY CANCER PANEL\n\nPatient: Priya Sharma | DOB: 1985-04-14 | Specimen: Saliva\nOrdering provider: Sarah Kim, MS CGC | Indication: Young-onset triple-negative breast cancer\n\nRESULTS:\n1. BRCA1 c.5266dupC (p.Gln1756Profs*74) — PATHOGENIC\n   - Exon 20, frameshift insertion\n   - Ashkenazi Jewish founder mutation (also seen in South Asian populations)\n   - Associated with: breast (60-72% lifetime risk), ovarian (39-44%), pancreatic, prostate\n   - Heterozygous (germline)\n\nNO ADDITIONAL PATHOGENIC VARIANTS in remaining 83 genes\nVUS: None\n\nFAMILY HISTORY:\n- Mother: Breast cancer age 48 (deceased age 54)\n- Maternal aunt: Ovarian cancer age 52 (deceased age 56)\n- Maternal grandmother: Breast cancer age 62\n\nRECOMMENDATIONS:\n1. Risk-reducing bilateral salpingo-oophorectomy (BSO) after completion of cancer treatment and childbearing\n2. Cascade testing for first-degree relatives\n3. Platinum-based chemotherapy and PARP inhibitor eligibility in treatment setting\n4. Enhanced screening for contralateral breast cancer\n5. Consider olaparib per OlympiA (adjuvant) or OlympiAD (metastatic) if applicable",
        ]);

        $this->addNote($patient, [
            'visit_id' => $surgeryVisit->id,
            'note_type' => 'Surgical Pathology Report',
            'note_date' => '2022-04-02',
            'author' => 'Dr. Meena Chakraborty',
            'content' => "SURGICAL PATHOLOGY REPORT — LEFT MODIFIED RADICAL MASTECTOMY\n\nSpecimen: Left breast — modified radical mastectomy with axillary lymph node dissection (levels I-II)\n\nGROSS: Mastectomy specimen 22 × 18 × 5 cm. Residual tumor bed identified in upper outer quadrant, 3.2 × 2.8 cm area of fibrosis with scattered firm foci.\n\nMICROSCOPIC:\n- Residual invasive carcinoma NST: 1.8 cm maximal dimension (ypT1c)\n- Nottingham grade 3 maintained\n- Approximately 45% cellularity reduction from neoadjuvant therapy (55% residual cellularity)\n- Extensive treatment effect: fibrosis, chronic inflammation, tumor bed changes\n- TILs: Decreased to ~20% (from 60% pre-treatment)\n- DCIS: Focal residual\n- Lymphovascular invasion: Not identified post-treatment\n- Margins: All negative (closest 8mm, deep)\n\nLYMPH NODES:\n- Sentinel nodes: 1/3 positive (micrometastasis, 1.2mm)\n- Non-sentinel nodes (ALND): 0/14 positive\n- Total: 1/17 positive — ypN1a (micrometastasis)\n\nRCB ASSESSMENT:\n- Primary tumor bed: 3.2 × 2.8 cm, cellularity 55%\n- Positive lymph nodes: 1, largest metastasis 1.2mm\n- RCB Index: 2.58\n- RCB Class: II (partial response)\n\nFINAL STAGING: ypT1c N1a (1/17, micromet) M0 — RCB-II\n\nCOMMENT: Significant but incomplete pathologic response. RCB-II in TNBC after KEYNOTE-522 neoadjuvant therapy indicates intermediate prognosis. Adjuvant pembrolizumab to complete 1 year recommended per KEYNOTE-522 protocol. BRCA1 carrier status supports consideration of adjuvant olaparib per OlympiA, though RCB-II (vs RCB-III) benefit is debated.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $colitisVisit->id,
            'note_type' => 'Inpatient Management Note',
            'note_date' => '2022-09-16',
            'author' => 'Dr. James Walker',
            'content' => "INPATIENT NOTE — IMMUNE-MEDIATED COLITIS\n\nPatient: Priya Sharma, 37F\nDiagnosis: Grade 2 immune-mediated colitis secondary to pembrolizumab\n\nHPI: Presents with 5-day history of worsening diarrhea, now 6-8 watery stools/day. No bloody stools. Mild crampy abdominal pain. On adjuvant pembrolizumab (cycle 7 of planned 17). Last infusion 10 days ago.\n\nVITALS: T 37.4°C, HR 92, BP 118/72, SpO2 98% RA\n\nLABS: WBC 8.4, CRP 42, ESR 38. Stool studies negative (C. diff, O&P, culture). Calprotectin 680 (elevated).\n\nCT ABDOMEN: Diffuse colonic wall thickening, no perforation.\n\nASSESSMENT: Grade 2 immune-mediated colitis (CTCAE v5.0)\n- 6-8 stools/day over baseline = Grade 2\n- No hemodynamic compromise\n\nMANAGEMENT:\n1. HOLD pembrolizumab (held for 21 days until resolution)\n2. Methylprednisolone 1mg/kg IV × 3 days, then prednisone taper over 4 weeks\n3. IV fluids for hydration\n4. Bland diet\n5. GI follow-up for possible colonoscopy if not improving\n6. Resume pembrolizumab if resolves to grade ≤1 within 12 weeks\n\nOUTCOME: Diarrhea resolved to grade 1 by day 4. Discharged on oral prednisone taper. Pembrolizumab successfully resumed after 21-day hold.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $metRecurrenceVisit->id,
            'note_type' => 'Somatic NGS Report',
            'note_date' => '2024-06-28',
            'author' => 'Foundation Medicine / Dr. Rajesh Gupta',
            'content' => "FOUNDATIONONE CDx — COMPREHENSIVE GENOMIC PROFILING\n\nPatient: Priya Sharma | DOB: 1985-04-14 | Specimen: Lung metastasis (CT-guided core biopsy)\nTumor Type: Breast — triple-negative, metastatic | Specimen received: 2024-06-20\n\nGENOMIC FINDINGS:\n1. BRCA1 c.5266dupC (p.Gln1756Profs*74) — PATHOGENIC, LOSS OF HETEROZYGOSITY (LOH)\n   - Variant allele frequency: 0.50 (germline) + somatic LOH → biallelic inactivation\n   - BRCA1 loss of function confirmed at somatic level\n   - FDA-approved therapy: Olaparib (OlympiAD), Talazoparib\n\n2. TP53 p.Y220C (c.659A>G, exon 6) — PATHOGENIC\n   - Variant allele frequency: 0.38\n   - Loss of function. Structurally destabilizing mutation\n   - Investigational: PC14028 (Y220C-specific stabilizer)\n\n3. MYC amplification (chr8q24) — PATHOGENIC\n   - Copy number: 14 copies\n   - Associated with aggressive biology, poor prognosis in TNBC\n   - Actionability: Prognostic (adverse)\n\nHRD SCORE: 62 (high, threshold ≥42)\nTMB: 4.2 mutations/Mb (low)\nMSI: Stable\nPD-L1 (SP142): IC 2+ (≥5%)\n\nTHERAPEUTIC IMPLICATIONS:\n- Biallelic BRCA1 loss + HRD 62 strongly predicts PARP inhibitor sensitivity\n- Olaparib recommended per OlympiAD (median PFS 7.0 vs 4.2 months)\n- MYC amplification may limit depth/duration of response\n- Consider pembrolizumab if needed (PD-L1 IC 2+, prior exposure tolerated)",
        ]);

        $this->addNote($patient, [
            'visit_id' => $olaparibVisit->id,
            'note_type' => 'Treatment Initiation Note',
            'note_date' => '2024-07-08',
            'author' => 'Dr. Rajesh Gupta',
            'content' => "MEDICAL ONCOLOGY — OLAPARIB INITIATION\n\nPatient: Priya Sharma, 39F\nDiagnosis: Metastatic TNBC, BRCA1-mutated (germline pathogenic + somatic LOH)\nSites of disease: Left lung (28mm), right axillary LN (14mm), liver seg5 (18mm), liver seg7 (12mm)\n\nMOLECULAR RATIONALE:\n- Germline BRCA1 c.5266dupC (pathogenic) with somatic LOH → biallelic inactivation\n- HRD score 62 (high) — synthetic lethal vulnerability to PARP inhibition\n- OlympiAD trial: Olaparib vs chemotherapy in gBRCA HER2- mBC → PFS 7.0 vs 4.2 months (HR 0.58)\n- No prior PARP inhibitor exposure\n\nTREATMENT PLAN:\n- Olaparib 300mg PO BID (standard dose)\n- No prior platinum in metastatic setting (neoadjuvant carboplatin 2.5 years ago — not a resistance signal)\n- Concurrent levothyroxine, ondansetron PRN\n\nMONITORING:\n- CBC Q2W × 8 weeks, then Q4W (monitor for anemia, neutropenia, thrombocytopenia)\n- LFTs Q4W\n- CA 15-3 Q6W\n- CT restaging Q12W\n- ctDNA Q12W (monitor for BRCA1 reversion mutations)\n\nECOG PS: 0\nGoals: Disease control, QOL preservation. Patient understands eventual resistance likely.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $olaparibPDVisit->id,
            'note_type' => 'ctDNA Resistance Report',
            'note_date' => '2025-12-20',
            'author' => 'Guardant Health / Dr. Rajesh Gupta',
            'content' => "LIQUID BIOPSY — GUARDANT360 CDx\n\nPatient: Priya Sharma | Date collected: 2025-12-15\n\nctDNA FINDINGS:\n1. BRCA1 c.5266dupC (germline, persistent) — VAF 0.50\n2. BRCA1 c.5264_5266del (REVERSION MUTATION) — VAF 0.15 — ACQUIRED\n   - In-frame deletion restoring BRCA1 reading frame\n   - Restores homologous recombination repair proficiency\n   - Known mechanism of PARP inhibitor resistance\n   - Confers cross-resistance to platinum-based chemotherapy\n3. TP53 p.Y220C — VAF 0.42 (persistent)\n4. MYC amplification — persistent\n\nCLINICAL INTERPRETATION:\nBRCA1 reversion mutation (c.5264_5266del) restores the open reading frame disrupted by the germline c.5266dupC insertion. This secondary mutation is the canonical mechanism of acquired PARP inhibitor resistance, occurring in ~25% of patients progressing on olaparib. The restored BRCA1 function re-enables homologous recombination, eliminating the synthetic lethal vulnerability.\n\nTHERAPEUTIC IMPLICATIONS:\n- PARP inhibitor resistance confirmed — discontinue olaparib\n- Platinum rechallenge unlikely to benefit (cross-resistance via same mechanism)\n- Sacituzumab govitecan: recommended 2L option (ASCENT trial, BRCA1 status-independent)\n- Pembrolizumab rechallenge: possible (PD-L1 CPS 18), but prior immune colitis is relative contraindication\n- Clinical trials targeting HR-proficient TNBC should be considered",
        ]);

        $this->addNote($patient, [
            'visit_id' => $fnVisit->id,
            'note_type' => 'Emergency Department / Dose Reduction Note',
            'note_date' => '2026-02-08',
            'author' => 'Dr. Kevin Park / Dr. Rajesh Gupta',
            'content' => "EMERGENCY DEPARTMENT + ONCOLOGY CONSULT NOTE\n\nChief Complaint: Fever 39.5°C, rigors × 6 hours\n\nHPI: 40F with metastatic TNBC on sacituzumab govitecan (cycle 2, day 10), presenting with febrile neutropenia. Temperature 39.5°C at home, rigors, myalgias. No cough, no localizing source of infection.\n\nVITALS: T 39.5°C, HR 118, BP 96/62, RR 20, SpO2 96% RA\n\nLABS:\n- WBC 0.9 K/uL (CRITICAL LOW)\n- ANC 0.2 K/uL (CRITICAL LOW — grade 4 neutropenia)\n- Hgb 8.8 g/dL\n- Plt 88 K/uL\n- Lactate 2.1, CRP 124, procalcitonin 1.8\n- Blood cultures × 2 drawn (peripheral + port)\n\nASSESSMENT: Febrile neutropenia, high-risk (ANC <0.1 anticipated >7 days, hemodynamically borderline)\n\nMANAGEMENT:\n1. Meropenem 1g IV Q8H (upgraded from cefepime given hemodynamic instability)\n2. IV fluids — NS 2L bolus, then 150 mL/hr\n3. G-CSF (filgrastim 5mcg/kg daily) starting day 2\n4. Hold sacituzumab govitecan\n5. Blood cultures: NEGATIVE at 48 hours\n\nANC RECOVERY: 0.2 → 0.8 (day 3) → 2.8 (day 10)\n\nDOSE MODIFICATION (per Dr. Gupta):\n- Sacituzumab govitecan dose reduced from 10mg/kg to 7.5mg/kg (one dose-level reduction per package insert)\n- Add prophylactic G-CSF (pegfilgrastim) with subsequent cycles\n- If recurrent grade 4 neutropenia at 7.5mg/kg, further reduce to 5mg/kg",
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Baseline (2021-09-20)
        $this->addLabPanel($patient, '2021-09-20', [
            ['CA 15-3',          '6875-9',  24,    'U/mL',   null, 30.0,  null],
            ['WBC',              '6690-2',  7.8,   'K/uL',   4.5,  11.0,  null],
            ['ANC',              '751-8',   5.2,   'K/uL',   1.5,  8.0,   null],
            ['Hemoglobin',       '718-7',   12.8,  'g/dL',   12.0, 16.0,  null],
            ['Platelet Count',   '777-3',   262,   'K/uL',   150,  400,   null],
            ['AST',              '1920-8',  20,    'U/L',    10,   40,    null],
            ['ALT',              '1742-6',  16,    'U/L',    7,    56,    null],
            ['Creatinine',       '2160-0',  0.7,   'mg/dL',  0.6,  1.2,   null],
        ]);

        // AC nadir (2021-12-15)
        $this->addLabPanel($patient, '2021-12-15', [
            ['WBC',              '6690-2',  2.4,   'K/uL',   4.5,  11.0,  'L'],
            ['ANC',              '751-8',   0.9,   'K/uL',   1.5,  8.0,   'L'],
            ['Hemoglobin',       '718-7',   10.1,  'g/dL',   12.0, 16.0,  'L'],
            ['Platelet Count',   '777-3',   134,   'K/uL',   150,  400,   'L'],
        ]);

        // Post-surgery (2022-05-20)
        $this->addLabPanel($patient, '2022-05-20', [
            ['WBC',              '6690-2',  5.1,   'K/uL',   4.5,  11.0,  null],
            ['Hemoglobin',       '718-7',   10.8,  'g/dL',   12.0, 16.0,  'L'],
            ['AST',              '1920-8',  18,    'U/L',    10,   40,    null],
            ['ALT',              '1742-6',  22,    'U/L',    7,    56,    null],
            ['Creatinine',       '2160-0',  0.8,   'mg/dL',  0.6,  1.2,   null],
            ['TSH',              '3016-3',  8.2,   'mIU/L',  0.4,  4.0,   'H'],
        ]);

        // Metastatic recurrence (2024-06-14)
        $this->addLabPanel($patient, '2024-06-14', [
            ['CA 15-3',          '6875-9',  88,    'U/mL',   null, 30.0,  'H'],
            ['WBC',              '6690-2',  6.8,   'K/uL',   4.5,  11.0,  null],
            ['Hemoglobin',       '718-7',   11.8,  'g/dL',   12.0, 16.0,  'L'],
            ['AST',              '1920-8',  24,    'U/L',    10,   40,    null],
            ['ALT',              '1742-6',  28,    'U/L',    7,    56,    null],
        ]);

        // Olaparib responding (2024-09-18)
        $this->addLabPanel($patient, '2024-09-18', [
            ['CA 15-3',          '6875-9',  42,    'U/mL',   null, 30.0,  'H'],
            ['WBC',              '6690-2',  6.2,   'K/uL',   4.5,  11.0,  null],
            ['Hemoglobin',       '718-7',   11.4,  'g/dL',   12.0, 16.0,  'L'],
        ]);

        // Olaparib deep response (2025-06-18)
        $this->addLabPanel($patient, '2025-06-18', [
            ['CA 15-3',          '6875-9',  22,    'U/mL',   null, 30.0,  null],
            ['WBC',              '6690-2',  5.4,   'K/uL',   4.5,  11.0,  null],
            ['Hemoglobin',       '718-7',   10.6,  'g/dL',   12.0, 16.0,  'L'],
        ]);

        // Olaparib PD (2025-12-15)
        $this->addLabPanel($patient, '2025-12-15', [
            ['CA 15-3',          '6875-9',  67,    'U/mL',   null, 30.0,  'H'],
        ]);

        // SG nadir / FN (2026-02-08)
        $this->addLabPanel($patient, '2026-02-08', [
            ['WBC',              '6690-2',  0.9,   'K/uL',   4.5,  11.0,  'CL'],
            ['ANC',              '751-8',   0.2,   'K/uL',   1.5,  8.0,   'CL'],
            ['Hemoglobin',       '718-7',   8.8,   'g/dL',   12.0, 16.0,  'L'],
            ['Platelet Count',   '777-3',   88,    'K/uL',   150,  400,   'L'],
        ]);

        // SG recovery (2026-02-18)
        $this->addLabPanel($patient, '2026-02-18', [
            ['WBC',              '6690-2',  4.8,   'K/uL',   4.5,  11.0,  null],
            ['ANC',              '751-8',   2.8,   'K/uL',   1.5,  8.0,   null],
            ['Hemoglobin',       '718-7',   10.0,  'g/dL',   12.0, 16.0,  'L'],
            ['Platelet Count',   '777-3',   156,   'K/uL',   150,  400,   null],
        ]);

        // SG responding (2026-03-10)
        $this->addLabPanel($patient, '2026-03-10', [
            ['CA 15-3',          '6875-9',  38,    'U/mL',   null, 30.0,  'H'],
        ]);

        // ── RECIST Imaging ──────────────────────────────────────

        // Breast MRI — Baseline (2021-09-25)
        $mri1 = $this->addImagingStudy($patient, [
            'study_date' => '2021-09-25',
            'modality' => 'MR',
            'body_site' => 'Left breast / Bilateral axillae',
            'description' => 'Breast MRI with Contrast — Baseline Staging',
            'indication' => 'Newly diagnosed left breast TNBC, staging',
            'findings' => 'Left breast: Irregular enhancing mass at 10 o\'clock, 34mm maximal dimension. Left axillary lymph node: 18mm short axis, morphologically abnormal. Sum of target lesions: 52mm. RECIST: Baseline.',
        ]);
        $this->addImagingMeasurement($mri1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left breast mass',
            'value_numeric' => 34,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2021-09-25',
        ]);
        $this->addImagingMeasurement($mri1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left axillary lymph node',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2021-09-25',
        ]);

        // Breast MRI — Mid-neoadjuvant (2022-01-14)
        $mri2 = $this->addImagingStudy($patient, [
            'study_date' => '2022-01-14',
            'modality' => 'MR',
            'body_site' => 'Left breast / Bilateral axillae',
            'description' => 'Breast MRI — Mid-Neoadjuvant Assessment',
            'indication' => 'Mid-treatment response assessment after paclitaxel/carboplatin/pembrolizumab',
            'findings' => 'Left breast mass decreased to 18mm (from 34mm). Left axillary LN decreased to 8mm (from 18mm). Sum 26mm (baseline 52mm, -50%). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($mri2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left breast mass',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2022-01-14',
        ]);
        $this->addImagingMeasurement($mri2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left axillary lymph node',
            'value_numeric' => 8,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2022-01-14',
        ]);

        // Breast MRI — Pre-surgery (2022-03-14)
        $mri3 = $this->addImagingStudy($patient, [
            'study_date' => '2022-03-14',
            'modality' => 'MR',
            'body_site' => 'Left breast / Bilateral axillae',
            'description' => 'Breast MRI — Pre-Surgical Assessment',
            'indication' => 'Pre-operative assessment after neoadjuvant completion',
            'findings' => 'Left breast mass decreased to 16mm (from 34mm baseline). Left axillary LN: not measurable (below 10mm threshold). Sum 16mm (baseline 52mm, -69%). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($mri3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left breast mass',
            'value_numeric' => 16,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2022-03-14',
        ]);

        // Metastatic CT — Baseline (2024-06-14)
        $ct1 = $this->addImagingStudy($patient, [
            'study_date' => '2024-06-14',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis with Contrast — Metastatic Baseline',
            'indication' => 'New symptoms, surveillance imaging — metastatic workup',
            'findings' => 'Left lung: Spiculated nodule 28mm. Right axillary lymph node: 14mm short axis. Liver seg5: 18mm. Liver seg7: 12mm. Sum of target lesions: 72mm. RECIST: Baseline. No bone metastases.',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left lung nodule',
            'value_numeric' => 28,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-06-14',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right axillary lymph node',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-06-14',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg5',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-06-14',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg7',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-06-14',
        ]);

        // Brain MRI — Negative (2024-06-16)
        $this->addImagingStudy($patient, [
            'study_date' => '2024-06-16',
            'modality' => 'MR',
            'body_site' => 'Brain',
            'description' => 'Brain MRI with Contrast — Metastatic Screening',
            'indication' => 'TNBC metastatic workup — rule out brain metastases',
            'findings' => 'No intracranial metastases. No enhancing lesions. No leptomeningeal disease. Normal ventricular size.',
        ]);

        // PET-CT — Staging (2024-06-18)
        $this->addImagingStudy($patient, [
            'study_date' => '2024-06-18',
            'modality' => 'PT',
            'body_site' => 'Whole Body',
            'description' => 'PET-CT — Metastatic Staging',
            'indication' => 'Metastatic TNBC staging',
            'findings' => 'FDG-avid left lung nodule (SUVmax 8.6). FDG-avid right axillary LN (SUVmax 5.2). FDG-avid liver seg5 (SUVmax 6.8) and seg7 (SUVmax 4.4). No skeletal metastases. No brain involvement.',
        ]);

        // CT — Olaparib responding (2024-09-18)
        $ct2 = $this->addImagingStudy($patient, [
            'study_date' => '2024-09-18',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging on Olaparib',
            'indication' => 'Restaging after 10 weeks olaparib',
            'findings' => 'Left lung nodule: 14mm (from 28mm). Right axillary LN: 7mm (from 14mm, below measurable). Liver seg5: 9mm (from 18mm). Sum 30mm (baseline 72mm, -58%). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left lung nodule',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-09-18',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right axillary lymph node',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-09-18',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg5',
            'value_numeric' => 9,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2024-09-18',
        ]);

        // CT — Olaparib continued response (2025-01-20)
        $ct3 = $this->addImagingStudy($patient, [
            'study_date' => '2025-01-20',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging on Olaparib',
            'indication' => 'Restaging on olaparib',
            'findings' => 'Left lung nodule: 12mm. Liver seg5: 7mm. Sum 19mm (baseline 72mm, -74%). RECIST: Partial Response (continued).',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left lung nodule',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-01-20',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg5',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-01-20',
        ]);

        // CT — Olaparib deep response (2025-06-18)
        $ct4 = $this->addImagingStudy($patient, [
            'study_date' => '2025-06-18',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging on Olaparib',
            'indication' => 'Restaging on olaparib — near-complete response assessment',
            'findings' => 'Left lung nodule: 10mm. Liver seg5: 6mm. Sum 16mm (baseline 72mm, -78%). RECIST: Partial Response (near-CR). Right axillary LN: not visible.',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left lung nodule',
            'value_numeric' => 10,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-06-18',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg5',
            'value_numeric' => 6,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-06-18',
        ]);

        // CT — Olaparib PD (2025-12-15)
        $ct5 = $this->addImagingStudy($patient, [
            'study_date' => '2025-12-15',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging, Progressive Disease',
            'indication' => 'Rising CA 15-3, symptom assessment',
            'findings' => 'Left lung nodule: ENLARGED to 18mm (from 10mm nadir). NEW left adrenal mass: 16mm. Liver seg5: 12mm (from 6mm nadir). Sum 46mm + new lesion. RECIST: Progressive Disease (unequivocal progression with new lesion).',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left lung nodule',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-12-15',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left adrenal mass (NEW)',
            'value_numeric' => 16,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-12-15',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg5',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2025-12-15',
        ]);

        // CT — SG responding (2026-03-10)
        $ct6 = $this->addImagingStudy($patient, [
            'study_date' => '2026-03-10',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging on Sacituzumab Govitecan',
            'indication' => 'Restaging after 2 cycles sacituzumab govitecan (dose-reduced)',
            'findings' => 'Left lung nodule: 12mm (from 18mm). Left adrenal mass: 10mm (from 16mm). Liver seg5: 8mm (from 12mm). Sum 30mm (new baseline 46mm, -35%). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left lung nodule',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2026-03-10',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left adrenal mass',
            'value_numeric' => 10,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2026-03-10',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg5',
            'value_numeric' => 8,
            'unit' => 'mm',
            'measured_by' => 'Dr. Sunita Rao',
            'measured_at' => '2026-03-10',
        ]);

        // ── Observations ────────────────────────────────────────

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_text' => '0',
            'observed_at' => '2021-09-22',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_text' => '0',
            'observed_at' => '2024-06-14',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_text' => '1',
            'observed_at' => '2025-12-15',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_text' => '1',
            'observed_at' => '2026-03-10',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'RCB Score',
            'category' => 'pathology_score',
            'value_text' => 'II (RCB index 2.58)',
            'observed_at' => '2022-03-28',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Immune-mediated colitis — Grade 2 diarrhea 6-8 episodes/day',
            'category' => 'adverse_event',
            'value_text' => 'Grade 2 (CTCAE v5.0)',
            'observed_at' => '2022-09-15',
        ]);

        // ── Genomic Variants ────────────────────────────────────

        $this->addGenomicVariant($patient, [
            'gene' => 'BRCA1',
            'variant_description' => 'c.5266dupC (p.Gln1756Profs*74)',
            'variant_type' => 'indel',
            'chromosome' => 'chr17',
            'zygosity' => 'heterozygous',
            'origin' => 'germline',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'PARP_inhibitor',
            'reported_at' => '2021-10-15',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'TP53',
            'variant_description' => 'p.Y220C (c.659A>G)',
            'variant_type' => 'SNV',
            'chromosome' => 'chr17',
            'allele_frequency' => 0.38,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'investigational',
            'reported_at' => '2024-06-28',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'MYC',
            'variant_description' => 'Amplification (14 copies)',
            'variant_type' => 'CNV',
            'chromosome' => 'chr8',
            'clinical_significance' => 'pathogenic',
            'actionability' => 'prognostic',
            'reported_at' => '2024-06-28',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'BRCA1',
            'variant_description' => 'c.5264_5266del (reversion mutation, acquired resistance)',
            'variant_type' => 'indel',
            'chromosome' => 'chr17',
            'allele_frequency' => 0.15,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'PARP_resistance',
            'reported_at' => '2025-12-20',
        ]);

        // ── Condition Eras ──────────────────────────────────────

        $this->addConditionEra($patient, [
            'era_name' => 'Breast cancer era',
            'start_date' => '2021-09-01',
            'end_date' => null,
            'occurrence_count' => 25,
        ]);

        $this->addConditionEra($patient, [
            'era_name' => 'BRCA1 carrier management era',
            'start_date' => '2021-10-01',
            'end_date' => null,
            'occurrence_count' => 6,
        ]);

        $this->addConditionEra($patient, [
            'era_name' => 'Treatment toxicity era (neoadjuvant)',
            'start_date' => '2021-10-01',
            'end_date' => '2022-04-30',
            'occurrence_count' => 5,
        ]);

        // ── Drug Eras ───────────────────────────────────────────

        $this->addDrugEra($patient, [
            'era_name' => 'KEYNOTE-522 neoadjuvant',
            'start_date' => '2021-10-18',
            'end_date' => '2022-03-14',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'era_name' => 'Pembrolizumab adjuvant',
            'start_date' => '2022-05-02',
            'end_date' => '2023-02-20',
            'gap_days' => 21,
        ]);

        $this->addDrugEra($patient, [
            'era_name' => 'Olaparib',
            'start_date' => '2024-07-08',
            'end_date' => '2025-12-15',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'era_name' => 'Sacituzumab govitecan',
            'start_date' => '2026-01-12',
            'end_date' => null,
            'gap_days' => 7,
        ]);
    }
}
