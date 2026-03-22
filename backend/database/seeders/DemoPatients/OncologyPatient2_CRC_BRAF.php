<?php

namespace Database\Seeders\DemoPatients;

class OncologyPatient2_CRC_BRAF
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-ON-002',
            'first_name' => 'Margaret',
            'last_name' => 'Okafor',
            'date_of_birth' => '1972-03-09',
            'sex' => 'Female',
            'race' => 'Black or African American',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-MO-44918');
        $this->addIdentifier($patient, 'hospital_mrn', 'ACC-778234', 'Cancer Center');

        // ── Conditions ──────────────────────────────────────────

        $this->addCondition($patient, [
            'concept_name' => 'Adenocarcinoma ascending colon Stage IIIB → metastatic',
            'concept_code' => 'C18.2',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2022-01-01',
            'severity' => 'severe',
            'body_site' => 'Ascending colon',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Liver metastases',
            'concept_code' => 'C78.7',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2022-12-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Peritoneal carcinomatosis',
            'concept_code' => 'C78.6',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2022-12-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Type 2 diabetes mellitus metformin-controlled',
            'concept_code' => 'E11.65',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Iron deficiency anemia',
            'concept_code' => 'D50.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2022-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Immune-mediated thyroiditis',
            'concept_code' => 'E06.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-02-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Port-associated DVT right subclavian',
            'concept_code' => 'I82.A11',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-08-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Malignant ascites',
            'concept_code' => 'R18.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2025-05-01',
        ]);

        // ── Medications ─────────────────────────────────────────

        // Adjuvant CAPOX
        $this->addMedication($patient, [
            'drug_name' => 'Capecitabine 1000mg/m² PO BID d1-14',
            'concept_code' => '194000',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2022-03-01',
            'end_date' => '2022-08-15',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Oxaliplatin 130mg/m² IV Q21d',
            'concept_code' => '32592',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2022-03-01',
            'end_date' => '2022-07-15',
        ]);

        // FOLFIRI + Bevacizumab
        $this->addMedication($patient, [
            'drug_name' => 'Irinotecan 180mg/m² IV Q14d',
            'concept_code' => '51499',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-01-09',
            'end_date' => '2023-09-20',
        ]);

        $this->addMedication($patient, [
            'drug_name' => '5-FU 2400mg/m² 46hr infusion Q14d',
            'concept_code' => '4492',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-01-09',
            'end_date' => '2023-09-20',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Bevacizumab 5mg/kg IV Q14d',
            'concept_code' => '253337',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-01-09',
            'end_date' => '2023-09-20',
        ]);

        // BEACON (Encorafenib + Cetuximab)
        $this->addMedication($patient, [
            'drug_name' => 'Encorafenib 300mg PO daily',
            'concept_code' => '2049106',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-10-16',
            'end_date' => '2024-09-18',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Cetuximab 250mg/m² IV weekly',
            'concept_code' => '318341',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-10-16',
            'end_date' => '2024-09-18',
        ]);

        // Nivolumab (clinical trial)
        $this->addMedication($patient, [
            'drug_name' => 'Nivolumab 240mg IV Q2W',
            'concept_code' => '1597876',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2024-11-04',
            'end_date' => '2025-06-20',
        ]);

        // Supportive medications
        $this->addMedication($patient, [
            'drug_name' => 'Levothyroxine 75mcg PO daily',
            'concept_code' => '10582',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2025-03-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Enoxaparin 1mg/kg SQ BID',
            'concept_code' => '67108',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2023-08-20',
        ]);

        // ── Procedures ──────────────────────────────────────────

        $this->addProcedure($patient, [
            'procedure_name' => 'Right hemicolectomy',
            'concept_code' => '44160',
            'vocabulary' => 'CPT',
            'performed_at' => '2022-02-08',
            'specialty' => 'Surgical Oncology',
            'body_site' => 'Ascending colon',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Port-a-cath placement',
            'concept_code' => '36561',
            'vocabulary' => 'CPT',
            'performed_at' => '2023-01-05',
            'specialty' => 'Interventional Radiology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Liver biopsy (metastatic confirmation)',
            'concept_code' => '47000',
            'vocabulary' => 'CPT',
            'performed_at' => '2022-12-10',
            'specialty' => 'Interventional Radiology',
            'body_site' => 'Liver',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Therapeutic paracentesis',
            'concept_code' => '49083',
            'vocabulary' => 'CPT',
            'performed_at' => '2025-05-15',
            'specialty' => 'Gastroenterology',
            'body_site' => 'Abdomen',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Therapeutic paracentesis',
            'concept_code' => '49083',
            'vocabulary' => 'CPT',
            'performed_at' => '2025-06-10',
            'specialty' => 'Gastroenterology',
            'body_site' => 'Abdomen',
        ]);

        // ── Visits ──────────────────────────────────────────────

        // Surgical oncology — resection
        $surgVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2022-02-08',
            'department' => 'Surgical Oncology',
            'provider_name' => 'Dr. Adaeze Nwosu',
            'reason' => 'Right hemicolectomy for ascending colon adenocarcinoma',
        ]);

        // Post-surgical follow-up
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-02-22',
            'department' => 'Surgical Oncology',
            'provider_name' => 'Dr. Adaeze Nwosu',
            'reason' => 'Post-operative follow-up — wound check, pathology review, referral to medical oncology',
        ]);

        // Medical oncology — adjuvant CAPOX initiation
        $adjuvantVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-03-01',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Adjuvant chemotherapy initiation — CAPOX for Stage IIIB colon cancer',
        ]);

        // Adjuvant monitoring
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-04-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'CAPOX cycle 3 — labs, CEA normalized post-resection',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-07-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'CAPOX cycle 6 — stopping oxaliplatin for grade 2 peripheral neuropathy, continue capecitabine',
        ]);

        // Metastatic recurrence
        $liverBiopsyVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2022-12-10',
            'department' => 'Interventional Radiology',
            'provider_name' => 'Dr. Lisa Huang',
            'reason' => 'CT-guided liver biopsy — rising CEA, new liver lesions on surveillance CT',
        ]);

        $metRecurrenceVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-12-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Metastatic recurrence — liver mets and peritoneal carcinomatosis confirmed, treatment planning',
        ]);

        // FOLFIRI+bev initiation
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-01-09',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Line 1 metastatic — FOLFIRI + bevacizumab initiation',
        ]);

        // Febrile neutropenia — inpatient
        $fnVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2023-03-28',
            'visit_end_date' => '2023-04-01',
            'department' => 'Emergency Medicine',
            'provider_name' => 'Dr. Michael Torres',
            'reason' => 'Febrile neutropenia — ANC 0.4, admitted for IV antibiotics',
        ]);

        // FOLFIRI responding
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-06-16',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Restaging — partial response on FOLFIRI + bevacizumab, CEA declining',
        ]);

        // Port DVT
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-08-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Port-associated right subclavian DVT — start enoxaparin',
        ]);

        // PD1 on FOLFIRI
        $pd1Visit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-09-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Progressive disease on FOLFIRI+bev — new liver lesion, rising CEA, plan BEACON regimen',
        ]);

        // BEACON initiation
        $beaconVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-10-16',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Line 2 metastatic — BEACON regimen initiation (encorafenib + cetuximab) for BRAF V600E CRC',
        ]);

        // BEACON responding
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-01-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Restaging — partial response on BEACON, CEA declining',
        ]);

        // BEACON stable
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-05-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Restaging — stable disease on BEACON',
        ]);

        // PD2 on BEACON
        $pd2Visit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-09-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Progressive disease on BEACON — liver enlarging, new retroperitoneal LN, discuss trial options',
        ]);

        // ctDNA resistance
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-10-05',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'ctDNA resistance report review — acquired KRAS G12D + MAP2K1 K57N',
        ]);

        // Clinical trial enrollment
        $trialVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-11-04',
            'department' => 'Clinical Trials Office',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Line 3 — nivolumab clinical trial enrollment for MSS CRC with prior BRAF-targeted therapy',
        ]);

        // Endocrinology — immune thyroiditis
        $endoVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-02-14',
            'department' => 'Endocrinology',
            'provider_name' => 'Dr. Priya Sharma',
            'reason' => 'New immune-mediated thyroiditis — elevated TSH on nivolumab, start levothyroxine',
        ]);

        // Palliative care
        $palliativeVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-04-10',
            'department' => 'Palliative Care',
            'provider_name' => 'Dr. Karen Mitchell',
            'reason' => 'Goals-of-care discussion — symptom management, advance directive review',
        ]);

        // Paracentesis visits
        $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2025-05-15',
            'department' => 'Gastroenterology',
            'provider_name' => 'Dr. David Chen',
            'reason' => 'Therapeutic paracentesis — malignant ascites, 3.2L drained',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2025-06-10',
            'department' => 'Gastroenterology',
            'provider_name' => 'Dr. David Chen',
            'reason' => 'Therapeutic paracentesis — recurrent malignant ascites, 4.1L drained',
        ]);

        // PD3 / BSC transition
        $bscVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-06-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Thomas Reyes',
            'reason' => 'Progressive disease on nivolumab trial — transition to best supportive care',
        ]);

        // ── Clinical Notes ──────────────────────────────────────

        $this->addNote($patient, [
            'visit_id' => $surgVisit->id,
            'note_type' => 'Pathology Report',
            'note_date' => '2022-02-12',
            'author' => 'Dr. Rebecca Osei',
            'content' => "SURGICAL PATHOLOGY REPORT\n\nSpecimen: Right hemicolectomy\n\nGROSS: Segment of colon 22 cm in length with attached mesentery. Exophytic mass in ascending colon measuring 5.8 × 4.2 × 3.1 cm. Twenty-two lymph nodes identified.\n\nMICROSCOPIC: Moderately differentiated adenocarcinoma with mucinous features (40% mucinous component). Tumor invades through muscularis propria into pericolonic adipose tissue. Lymphovascular invasion (LVI) present. Perineural invasion (PNI) present. Tumor grade: G2-G3.\n\nLYMPH NODES: 4 of 22 lymph nodes positive for metastatic carcinoma (4/22)\n\nMARGINS: Proximal and distal margins negative (>5 cm).\n\nIMMUNOHISTOCHEMISTRY:\n- MLH1: Intact nuclear expression\n- MSH2: Intact nuclear expression\n- MSH6: Intact nuclear expression\n- PMS2: Intact nuclear expression\n- Mismatch repair: Proficient (pMMR) → Microsatellite Stable (MSS)\n- CDX2: Positive\n- CK20: Positive\n- CK7: Negative\n\nSTAGING: pT3 N2a M0 — AJCC Stage IIIB\n\nDIAGNOSIS: Moderately differentiated adenocarcinoma of ascending colon with mucinous features (40%), pMMR/MSS, LVI+, PNI+, 4/22 LN+. Recommend molecular profiling for BRAF, RAS, and microsatellite status.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $adjuvantVisit->id,
            'note_type' => 'Molecular Profiling Report',
            'note_date' => '2022-03-10',
            'author' => 'Tempus xT',
            'content' => "TEMPUS xT — COMPREHENSIVE GENOMIC PROFILING\n\nPatient: Margaret Okafor | DOB: 1972-03-09 | Specimen: Colon, ascending\nTumor Type: Colorectal adenocarcinoma | Specimen received: 2022-02-14\n\nGENOMIC FINDINGS:\n1. BRAF V600E (c.1799T>A, exon 15) — ACTIVATING MUTATION\n   - Variant allele frequency: 42%\n   - FDA-approved therapy: encorafenib + cetuximab (BEACON CRC trial)\n   - Worst prognostic molecular subgroup in mCRC\n\n2. PIK3CA E545K (exon 9) — ACTIVATING MUTATION\n   - Variant allele frequency: 18%\n   - Potential resistance to EGFR-targeted therapy\n\n3. APC R1450* (nonsense) — LOSS OF FUNCTION\n   - Variant allele frequency: 55%\n   - Canonical WNT pathway driver in CRC\n\n4. TP53 R175H — LOSS OF FUNCTION\n   - Variant allele frequency: 48%\n   - Hotspot mutation, associated with poor prognosis\n\nNO ALTERATIONS DETECTED IN: KRAS, NRAS (all-RAS wild-type)\n\nTMB: 6.8 mutations/Mb (low-intermediate)\nMSI: Stable (MSS)\nCIMP: High (CpG island methylator phenotype-high)\n\nCLINICAL SIGNIFICANCE:\nBRAF V600E + MSS + CIMP-high defines the worst prognostic subgroup in colorectal cancer. Median OS for BRAF V600E MSS mCRC is ~12-14 months. All-RAS WT allows EGFR-targeted therapy. Encorafenib + cetuximab (± binimetinib) is the standard targeted approach per BEACON CRC.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $liverBiopsyVisit->id,
            'note_type' => 'Pathology Report',
            'note_date' => '2022-12-14',
            'author' => 'Dr. Rebecca Osei',
            'content' => "SURGICAL PATHOLOGY REPORT — LIVER BIOPSY\n\nSpecimen: CT-guided core needle biopsy, liver segment 6\n\nGROSS: Two core fragments, 1.5 cm aggregate length, tan-pink.\n\nMICROSCOPIC: Metastatic adenocarcinoma consistent with colorectal primary. Mucinous features present. Tumor cells show moderate pleomorphism, glandular architecture with necrosis.\n\nIMMUNOHISTOCHEMISTRY:\n- CDX2: Positive (diffuse, strong)\n- CK20: Positive\n- CK7: Negative\n- SATB2: Positive\n- mismatch repair proteins: intact (pMMR, consistent with primary)\n\nDIAGNOSIS: Metastatic colorectal adenocarcinoma involving liver. Morphology and immunophenotype consistent with known ascending colon primary.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $fnVisit->id,
            'note_type' => 'Emergency Department Note',
            'note_date' => '2023-03-28',
            'author' => 'Dr. Michael Torres',
            'content' => "EMERGENCY DEPARTMENT NOTE\n\nChief Complaint: Fever to 39.2°C, rigors, fatigue × 1 day\n\nHPI: 50F with metastatic BRAF V600E colon adenocarcinoma on FOLFIRI + bevacizumab (cycle 6, day 12), presenting with febrile neutropenia. Temperature 39.2°C at home. Port site non-erythematous. Denies abdominal pain, diarrhea, dysuria. Mild nausea, decreased appetite.\n\nVITALS: T 39.2°C, HR 108, BP 102/68, RR 18, SpO2 97% RA\n\nLABS: WBC 1.9 (ANC 0.4), Hgb 9.4, Plt 145, Lactate 1.4, CRP 68, procalcitonin 0.48\nBlood cultures × 2 drawn (peripheral + port). UA negative. CXR: no infiltrate.\n\nASSESSMENT: Febrile neutropenia, MASCC score 20 (intermediate risk)\n\nPLAN:\n1. Admit to oncology service\n2. Cefepime 2g IV Q8H\n3. IV fluids — NS 1L bolus, then 100 mL/hr\n4. Hold chemotherapy — dose reduce irinotecan upon recovery\n5. Daily CBC, BMP\n6. Infectious disease consult if cultures positive\n7. G-CSF consideration upon ANC recovery",
        ]);

        $this->addNote($patient, [
            'visit_id' => $beaconVisit->id,
            'note_type' => 'Treatment Initiation Note',
            'note_date' => '2023-10-16',
            'author' => 'Dr. Thomas Reyes',
            'content' => "MEDICAL ONCOLOGY — BEACON REGIMEN INITIATION\n\nPatient: Margaret Okafor, 51F\nDiagnosis: BRAF V600E MSS metastatic colorectal adenocarcinoma\nSites of disease: Liver (seg 6, seg 4a, seg 8, seg 2), peritoneal carcinomatosis\n\nMOLECULAR: BRAF V600E (VAF 42%), PIK3CA E545K, APC R1450*, TP53 R175H, KRAS/NRAS WT, TMB 6.8, MSS, CIMP-high\n\nTREATMENT HISTORY:\n- Adjuvant CAPOX (2022-03 to 2022-08) — completed 8 cycles, oxaliplatin stopped C6 for neuropathy\n- 1L metastatic: FOLFIRI + bevacizumab (2023-01 to 2023-09) — best response PR (-38%), PD with new liver lesion\n\nTREATMENT PLAN:\n- Line 2: Encorafenib 300mg PO daily + cetuximab 250mg/m² IV weekly (BEACON doublet)\n- Rationale: BRAF V600E-specific therapy. BEACON CRC trial showed ORR 20%, median PFS 4.3 months, median OS 8.4 months for doublet in 2L+\n- PIK3CA E545K may limit response duration (potential resistance mechanism)\n\nMONITORING: CEA Q4W, CT restaging Q8W, dermatology referral for cetuximab skin toxicity\n\nECOG PS: 1\nGoals of care: Palliative intent. Discussed poor prognosis of BRAF V600E MSS CRC. Patient understands limited options after BEACON.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $pd2Visit->id,
            'note_type' => 'Resistance Analysis / ctDNA Report',
            'note_date' => '2024-10-05',
            'author' => 'Guardant Health / Dr. Thomas Reyes',
            'content' => "LIQUID BIOPSY — GUARDANT360 CDx\n\nPatient: Margaret Okafor | Date collected: 2024-09-25\n\nctDNA FINDINGS:\n1. BRAF V600E — DETECTED (VAF 22%) — original driver, persistent\n2. KRAS G12D — DETECTED (VAF 8%) — ACQUIRED RESISTANCE MUTATION\n   - Reactivation of MAPK pathway downstream of BRAF\n   - Confers resistance to BRAF + EGFR inhibitor combinations\n   - Found in ~20% of BEACON-resistant cases\n3. MAP2K1 K57N (MEK1) — DETECTED (VAF 5%) — ACQUIRED RESISTANCE MUTATION\n   - Parallel MAPK pathway reactivation\n   - Co-occurring with KRAS G12D suggests polyclonal resistance\n4. PIK3CA E545K — DETECTED (VAF 15%) — persistent\n5. TP53 R175H — DETECTED (VAF 38%) — persistent\n\nCLINICAL INTERPRETATION:\nDual MAPK pathway reactivation (KRAS G12D + MAP2K1 K57N) explains disease progression on encorafenib + cetuximab after 11 months (exceeding median PFS of 4.3 months). Polyclonal resistance pattern suggests limited benefit from further MAPK pathway inhibition.\n\nRECOMMENDATION: Consider immunotherapy-based clinical trial (checkpoint inhibitor ± novel agent). Limited standard options for BRAF V600E MSS CRC after BEACON failure. MSS status predicts poor response to single-agent checkpoint inhibitors, but combination approaches under investigation.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $trialVisit->id,
            'note_type' => 'Clinical Trial Enrollment Note',
            'note_date' => '2024-11-04',
            'author' => 'Dr. Thomas Reyes',
            'content' => "CLINICAL TRIAL ENROLLMENT — NIVOLUMAB IN MSS CRC\n\nProtocol: Phase II, open-label study of nivolumab in MSS colorectal cancer with prior BRAF-targeted therapy and high tumor mutational heterogeneity\n\nELIGIBILITY:\n- MSS CRC with prior BRAF-targeted therapy: MET ✓\n- Measurable disease (RECIST 1.1): MET ✓ (liver seg6 28mm, retroperitoneal LN 22mm)\n- ECOG PS ≤ 2: MET ✓ (ECOG 1)\n- Adequate organ function: MET ✓\n- Prior polyclonal resistance (≥2 MAPK alterations): MET ✓\n\nCONSENT: Signed 2024-10-28. Reviewed risks including immune-related adverse events (colitis, hepatitis, thyroiditis, pneumonitis, skin toxicity).\n\nDOSING: Nivolumab 240mg IV Q2W.\nCycle 1 Day 1 administered 2024-11-04 without incident.\n\nCORRELATIVE STUDIES: Serial ctDNA Q4W, tumor tissue for WES, T-cell clonality assays.\n\nMONITORING: CBC, CMP, TSH, lipase Q2W. CT restaging Q8W.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $palliativeVisit->id,
            'note_type' => 'Palliative Care Note',
            'note_date' => '2025-04-10',
            'author' => 'Dr. Karen Mitchell',
            'content' => "PALLIATIVE CARE — GOALS OF CARE\n\nPatient: Margaret Okafor, 53F\nDiagnosis: Metastatic BRAF V600E MSS colorectal adenocarcinoma, on nivolumab trial\n\nREFERRAL: Medical oncology, for symptom management and goals-of-care discussion given declining trajectory\n\nSYMPTOMS:\n- Abdominal distension/discomfort (peritoneal disease, early ascites) — NRS 4/10\n- Fatigue — ECOG declining, limiting ADLs\n- Nausea — intermittent, managed with ondansetron PRN\n- Weight loss — 8 lbs over 3 months, early satiety\n- Mood — reactive sadness, appropriate coping, supportive family\n\nADVANCE DIRECTIVES:\n- Health care proxy: Husband (Chukwuma Okafor)\n- Code status: Full code → discussed transition to DNR/DNI. Patient wishes to remain full code for now.\n- Hospice: Not ready. Wants to continue current trial if possible.\n\nPLAN:\n1. Symptom management: ondansetron scheduled, appetite stimulant (megestrol), optimize nutrition\n2. Paracentesis referral for increasing ascites\n3. Social work for family support\n4. Follow-up in 4-6 weeks or sooner if symptoms escalate\n5. Revisit code status at next visit",
        ]);

        $this->addNote($patient, [
            'visit_id' => $bscVisit->id,
            'note_type' => 'Best Supportive Care Transition Note',
            'note_date' => '2025-06-20',
            'author' => 'Dr. Thomas Reyes',
            'content' => "MEDICAL ONCOLOGY — TRANSITION TO BEST SUPPORTIVE CARE\n\nPatient: Margaret Okafor, 53F\nDiagnosis: Metastatic BRAF V600E MSS colorectal adenocarcinoma\n\nPROGRESSION SUMMARY:\n- CT (2025-06-20): Liver lesions enlarging (sum 73mm, +52% from nadir). New bilateral lung nodules (14mm, 11mm). Increasing ascites. RECIST: Progressive Disease.\n- CEA: 145.8 ng/mL (from 8.4 at diagnosis)\n- ECOG PS: Declined to 2. Increasing fatigue, abdominal distension, early satiety.\n- Liver function deteriorating: AST 92, ALT 88, ALP 264, albumin 2.6, bilirubin 1.8\n\nTREATMENT HISTORY:\n- Adjuvant: CAPOX (8 cycles, 2022)\n- 1L metastatic: FOLFIRI + bevacizumab (9 months, best PR -38%) — PD\n- 2L: Encorafenib + cetuximab/BEACON (11 months, best PR -38%) — PD via KRAS G12D + MAP2K1 K57N\n- 3L: Nivolumab trial (7.5 months, best SD) — PD\n\nDISCUSSION:\n- No standard-of-care options with meaningful benefit remaining\n- Regorafenib/TAS-102: Marginal benefit (OS ~6-7 months), patient declines given PS 2 and QOL concerns\n- Patient and family have decided to transition to best supportive care\n\nPLAN:\n1. Discontinue nivolumab trial\n2. Continue levothyroxine, enoxaparin\n3. Serial paracentesis as needed\n4. Hospice referral initiated\n5. Palliative care to continue symptom management\n6. Patient and family aware of prognosis (weeks to months)",
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Diagnosis (2022-01-18)
        $this->addLabPanel($patient, '2022-01-18', [
            ['CEA',              '2039-6',  8.4,   'ng/mL',  null, 5.0,   'H'],
            ['Hemoglobin',       '718-7',   9.8,   'g/dL',   12.0, 16.0,  'L'],
            ['WBC',              '6690-2',  8.1,   'K/uL',   4.5,  11.0,  null],
            ['AST',              '1920-8',  22,    'U/L',    10,   40,    null],
            ['ALT',              '1742-6',  18,    'U/L',    7,    56,    null],
            ['ALP',              '6768-6',  98,    'U/L',    44,   147,   null],
            ['Albumin',          '1751-7',  3.8,   'g/dL',   3.5,  5.5,   null],
            ['LDH',              '2532-0',  195,   'U/L',    120,  246,   null],
        ]);

        // Post-resection (2022-04-15)
        $this->addLabPanel($patient, '2022-04-15', [
            ['CEA',              '2039-6',  2.1,   'ng/mL',  null, 5.0,   null],
        ]);

        // Metastatic recurrence (2022-12-05)
        $this->addLabPanel($patient, '2022-12-05', [
            ['CEA',              '2039-6',  34.7,  'ng/mL',  null, 5.0,   'H'],
            ['Hemoglobin',       '718-7',   9.8,   'g/dL',   12.0, 16.0,  'L'],
            ['AST',              '1920-8',  45,    'U/L',    10,   40,    'H'],
            ['ALT',              '1742-6',  52,    'U/L',    7,    56,    null],
            ['ALP',              '6768-6',  142,   'U/L',    44,   147,   null],
            ['Albumin',          '1751-7',  3.6,   'g/dL',   3.5,  5.5,   null],
            ['LDH',              '2532-0',  312,   'U/L',    120,  246,   'H'],
        ]);

        // FOLFIRI nadir / febrile neutropenia (2023-03-28)
        $this->addLabPanel($patient, '2023-03-28', [
            ['WBC',              '6690-2',  1.9,   'K/uL',   4.5,  11.0,  'CL'],
            ['ANC',              '751-8',   0.4,   'K/uL',   1.5,  8.0,   'CL'],
            ['Hemoglobin',       '718-7',   9.4,   'g/dL',   12.0, 16.0,  'L'],
            ['Platelet Count',   '777-3',   145,   'K/uL',   150,  400,   'L'],
        ]);

        // FOLFIRI responding (2023-06-16)
        $this->addLabPanel($patient, '2023-06-16', [
            ['CEA',              '2039-6',  11.2,  'ng/mL',  null, 5.0,   'H'],
            ['AST',              '1920-8',  28,    'U/L',    10,   40,    null],
            ['ALT',              '1742-6',  30,    'U/L',    7,    56,    null],
            ['ALP',              '6768-6',  98,    'U/L',    44,   147,   null],
            ['LDH',              '2532-0',  218,   'U/L',    120,  246,   null],
        ]);

        // PD1 (2023-09-20)
        $this->addLabPanel($patient, '2023-09-20', [
            ['CEA',              '2039-6',  48.3,  'ng/mL',  null, 5.0,   'H'],
        ]);

        // BEACON responding (2024-01-15)
        $this->addLabPanel($patient, '2024-01-15', [
            ['CEA',              '2039-6',  14.6,  'ng/mL',  null, 5.0,   'H'],
        ]);

        // BEACON stable (2024-05-20)
        $this->addLabPanel($patient, '2024-05-20', [
            ['CEA',              '2039-6',  15.0,  'ng/mL',  null, 5.0,   'H'],
            ['WBC',              '6690-2',  6.4,   'K/uL',   4.5,  11.0,  null],
            ['Hemoglobin',       '718-7',   11.4,  'g/dL',   12.0, 16.0,  'L'],
            ['Platelet Count',   '777-3',   234,   'K/uL',   150,  400,   null],
        ]);

        // PD2 (2024-09-18)
        $this->addLabPanel($patient, '2024-09-18', [
            ['CEA',              '2039-6',  72.1,  'ng/mL',  null, 5.0,   'H'],
            ['AST',              '1920-8',  68,    'U/L',    10,   40,    'H'],
            ['ALT',              '1742-6',  74,    'U/L',    7,    56,    'H'],
            ['ALP',              '6768-6',  198,   'U/L',    44,   147,   'H'],
            ['Albumin',          '1751-7',  3.0,   'g/dL',   3.5,  5.5,   'L'],
            ['LDH',              '2532-0',  445,   'U/L',    120,  246,   'H'],
        ]);

        // Trial (2025-02-14)
        $this->addLabPanel($patient, '2025-02-14', [
            ['CEA',              '2039-6',  50.0,  'ng/mL',  null, 5.0,   'H'],
            ['WBC',              '6690-2',  3.2,   'K/uL',   4.5,  11.0,  'L'],
            ['Platelet Count',   '777-3',   98,    'K/uL',   150,  400,   'L'],
            ['TSH',              '3016-3',  14.2,  'mIU/L',  0.4,  4.0,   'H'],
        ]);

        // PD3 / BSC (2025-06-20)
        $this->addLabPanel($patient, '2025-06-20', [
            ['CEA',              '2039-6',  145.8, 'ng/mL',  null, 5.0,   'H'],
            ['AST',              '1920-8',  92,    'U/L',    10,   40,    'H'],
            ['ALT',              '1742-6',  88,    'U/L',    7,    56,    'H'],
            ['ALP',              '6768-6',  264,   'U/L',    44,   147,   'H'],
            ['Albumin',          '1751-7',  2.6,   'g/dL',   3.5,  5.5,   'L'],
            ['LDH',              '2532-0',  612,   'U/L',    120,  246,   'H'],
            ['Bilirubin Total',  '1975-2',  1.8,   'mg/dL',  0.1,  1.2,   'H'],
        ]);

        // ── RECIST Imaging ──────────────────────────────────────

        // Baseline CT (2022-12-05)
        $ct1 = $this->addImagingStudy($patient, [
            'study_date' => '2022-12-05',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis with Contrast — Metastatic Baseline',
            'indication' => 'Rising CEA, surveillance post-adjuvant CAPOX',
            'findings' => 'Multiple liver metastases: seg6 32mm, seg4a 21mm, seg8 18mm. Peritoneal nodularity. Sum of target lesions: 71mm. RECIST: Baseline.',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 32,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2022-12-05',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg4a',
            'value_numeric' => 21,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2022-12-05',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg8',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2022-12-05',
        ]);

        // PET-CT (2022-12-08) — no RECIST measurements
        $this->addImagingStudy($patient, [
            'study_date' => '2022-12-08',
            'modality' => 'PT',
            'body_site' => 'Whole Body',
            'description' => 'PET-CT — Metastatic Staging',
            'indication' => 'Staging of metastatic colorectal cancer',
            'findings' => 'FDG-avid liver metastases (seg6 SUVmax 12.4, seg4a SUVmax 9.8, seg8 SUVmax 8.2). Peritoneal carcinomatosis with diffuse FDG uptake. No osseous metastases.',
        ]);

        // CT (2023-03-10) — FOLFIRI responding
        $ct2 = $this->addImagingStudy($patient, [
            'study_date' => '2023-03-10',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on FOLFIRI + bevacizumab',
            'findings' => 'Liver seg6 24mm, seg4a 16mm, seg8 12mm. Sum 52mm (baseline 71mm, -27%). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 24,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-03-10',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg4a',
            'value_numeric' => 16,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-03-10',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg8',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-03-10',
        ]);

        // CT (2023-06-16) — FOLFIRI best response
        $ct3 = $this->addImagingStudy($patient, [
            'study_date' => '2023-06-16',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on FOLFIRI + bevacizumab',
            'findings' => 'Liver seg6 20mm, seg4a 14mm, seg8 10mm. Sum 44mm (baseline 71mm, -38%). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 20,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-06-16',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg4a',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-06-16',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg8',
            'value_numeric' => 10,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-06-16',
        ]);

        // CT (2023-09-20) — PD1 with new lesion
        $ct4 = $this->addImagingStudy($patient, [
            'study_date' => '2023-09-20',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on FOLFIRI + bevacizumab — rising CEA',
            'findings' => 'Liver seg6 26mm, seg4a 19mm, seg8 15mm, NEW seg2 14mm. Sum 60mm + new lesion. RECIST: Progressive Disease.',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 26,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-09-20',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg4a',
            'value_numeric' => 19,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-09-20',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg8',
            'value_numeric' => 15,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-09-20',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg2 (new)',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2023-09-20',
        ]);

        // CT (2024-01-15) — BEACON responding (new baseline)
        $ct5 = $this->addImagingStudy($patient, [
            'study_date' => '2024-01-15',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on encorafenib + cetuximab (BEACON)',
            'findings' => 'Liver seg6 18mm, seg4a 10mm, seg8 9mm. Sum 37mm (new baseline, PR -38% from PD1 sum). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-01-15',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg4a',
            'value_numeric' => 10,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-01-15',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg8',
            'value_numeric' => 9,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-01-15',
        ]);

        // CT (2024-05-20) — BEACON stable
        $ct6 = $this->addImagingStudy($patient, [
            'study_date' => '2024-05-20',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on encorafenib + cetuximab',
            'findings' => 'Liver seg6 19mm, seg4a 11mm, seg8 9mm. Sum 39mm. RECIST: Stable Disease.',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 19,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-05-20',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg4a',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-05-20',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg8',
            'value_numeric' => 9,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-05-20',
        ]);

        // CT (2024-09-18) — PD2 with new lesion
        $ct7 = $this->addImagingStudy($patient, [
            'study_date' => '2024-09-18',
            'modality' => 'CT',
            'body_site' => 'Abdomen/Pelvis',
            'description' => 'CT Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on BEACON — rising CEA, worsening symptoms',
            'findings' => 'Liver seg6 28mm. NEW retroperitoneal lymph node 22mm. Sum 50mm + new lesion. RECIST: Progressive Disease.',
        ]);
        $this->addImagingMeasurement($ct7, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 28,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-09-18',
        ]);
        $this->addImagingMeasurement($ct7, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Retroperitoneal LN (new)',
            'value_numeric' => 22,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2024-09-18',
        ]);

        // PET-CT (2024-10-01) — peritoneal staging, no RECIST
        $this->addImagingStudy($patient, [
            'study_date' => '2024-10-01',
            'modality' => 'PT',
            'body_site' => 'Whole Body',
            'description' => 'PET-CT — Restaging',
            'indication' => 'Staging after BEACON progression',
            'findings' => 'Diffuse peritoneal FDG uptake consistent with carcinomatosis. FDG-avid liver metastases and retroperitoneal lymphadenopathy. No osseous metastases.',
        ]);

        // CT (2025-02-14) — mixed response on trial
        $ct8 = $this->addImagingStudy($patient, [
            'study_date' => '2025-02-14',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on nivolumab trial',
            'findings' => 'Liver seg6 24mm, retroperitoneal LN 24mm. Sum 48mm. Peritoneal disease stable. No lung nodules. RECIST: Stable Disease (mixed response).',
        ]);
        $this->addImagingMeasurement($ct8, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 24,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2025-02-14',
        ]);
        $this->addImagingMeasurement($ct8, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Retroperitoneal LN',
            'value_numeric' => 24,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2025-02-14',
        ]);

        // CT (2025-06-20) — PD3 / BSC
        $ct9 = $this->addImagingStudy($patient, [
            'study_date' => '2025-06-20',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen/Pelvis',
            'description' => 'CT Chest/Abdomen/Pelvis — Restaging',
            'indication' => 'Restaging on nivolumab trial — clinical deterioration',
            'findings' => 'Liver enlarging, seg6 34mm, retroperitoneal LN 25mm. NEW bilateral lung nodules (14mm RLL, 11mm LLL). Moderate ascites. Sum 73mm. RECIST: Progressive Disease.',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver seg6',
            'value_numeric' => 34,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2025-06-20',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Retroperitoneal LN',
            'value_numeric' => 25,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2025-06-20',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RLL lung nodule (new)',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2025-06-20',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'LLL lung nodule (new)',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Lisa Huang',
            'measured_at' => '2025-06-20',
        ]);

        // ── Observations ────────────────────────────────────────

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'concept_code' => '89247-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 0,
            'category' => 'functional_status',
            'observed_at' => '2022-01-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'concept_code' => '89247-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 1,
            'category' => 'functional_status',
            'observed_at' => '2023-01-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'concept_code' => '89247-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 1,
            'category' => 'functional_status',
            'observed_at' => '2024-01-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'concept_code' => '89247-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 2,
            'category' => 'functional_status',
            'observed_at' => '2025-06-20',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Temperature',
            'concept_code' => '8310-5',
            'vocabulary' => 'LOINC',
            'value_numeric' => 39.2,
            'unit' => '°C',
            'category' => 'vital_signs',
            'observed_at' => '2023-03-28',
        ]);

        // ── Genomic Variants ────────────────────────────────────

        $this->addGenomicVariant($patient, [
            'gene_symbol' => 'BRAF',
            'variant_name' => 'p.V600E',
            'hgvs_c' => 'c.1799T>A',
            'variant_type' => 'SNV',
            'chromosome' => 'chr7',
            'allele_frequency' => 0.42,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'FDA_approved_therapy',
            'reported_at' => '2022-03-10',
        ]);

        $this->addGenomicVariant($patient, [
            'gene_symbol' => 'PIK3CA',
            'variant_name' => 'p.E545K',
            'variant_type' => 'SNV',
            'chromosome' => 'chr3',
            'allele_frequency' => 0.18,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'resistance_mechanism',
            'reported_at' => '2022-03-10',
        ]);

        $this->addGenomicVariant($patient, [
            'gene_symbol' => 'APC',
            'variant_name' => 'p.R1450*',
            'variant_type' => 'SNV',
            'chromosome' => 'chr5',
            'allele_frequency' => 0.55,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'none',
            'reported_at' => '2022-03-10',
        ]);

        $this->addGenomicVariant($patient, [
            'gene_symbol' => 'TP53',
            'variant_name' => 'p.R175H',
            'variant_type' => 'SNV',
            'chromosome' => 'chr17',
            'allele_frequency' => 0.48,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'prognostic',
            'reported_at' => '2022-03-10',
        ]);

        $this->addGenomicVariant($patient, [
            'gene_symbol' => 'KRAS',
            'variant_name' => 'p.G12D',
            'variant_type' => 'SNV',
            'chromosome' => 'chr12',
            'allele_frequency' => 0.08,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'resistance_mechanism',
            'reported_at' => '2024-10-05',
        ]);

        $this->addGenomicVariant($patient, [
            'gene_symbol' => 'MAP2K1',
            'variant_name' => 'p.K57N',
            'variant_type' => 'SNV',
            'chromosome' => 'chr15',
            'allele_frequency' => 0.05,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'resistance_mechanism',
            'reported_at' => '2024-10-05',
        ]);

        // ── Condition Eras ──────────────────────────────────────

        $this->addConditionEra($patient, [
            'condition_name' => 'Colorectal cancer era',
            'era_start_date' => '2022-01-01',
            'era_end_date' => null,
            'occurrence_count' => 30,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Liver metastases era',
            'era_start_date' => '2022-12-01',
            'era_end_date' => null,
            'occurrence_count' => 12,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Treatment toxicity era',
            'era_start_date' => '2023-01-01',
            'era_end_date' => '2023-04-01',
            'occurrence_count' => 3,
        ]);

        // ── Drug Eras ───────────────────────────────────────────

        $this->addDrugEra($patient, [
            'drug_name' => 'CAPOX adjuvant',
            'era_start_date' => '2022-03-01',
            'era_end_date' => '2022-08-15',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'FOLFIRI + bevacizumab',
            'era_start_date' => '2023-01-09',
            'era_end_date' => '2023-09-20',
            'gap_days' => 14,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Encorafenib + cetuximab (BEACON)',
            'era_start_date' => '2023-10-16',
            'era_end_date' => '2024-09-18',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Nivolumab (trial)',
            'era_start_date' => '2024-11-04',
            'era_end_date' => '2025-06-20',
            'gap_days' => 0,
        ]);
    }
}
