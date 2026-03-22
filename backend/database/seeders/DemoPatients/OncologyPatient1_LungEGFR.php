<?php

namespace Database\Seeders\DemoPatients;

class OncologyPatient1_LungEGFR
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-ON-001',
            'first_name' => 'James',
            'last_name' => 'Whitfield',
            'date_of_birth' => '1959-08-22',
            'sex' => 'Male',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-JW-71834');
        $this->addIdentifier($patient, 'hospital_mrn', 'NCI-556723', 'Cancer Institute');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Lung adenocarcinoma right upper lobe Stage IVB',
            'concept_code' => 'C34.11',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2021-04-01',
            'severity' => 'severe',
            'body_site' => 'Right upper lobe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Brain metastases',
            'concept_code' => 'C79.31',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2021-04-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hypertension',
            'concept_code' => 'I10',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2010-01-01',
            'severity' => 'mild',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hyperlipidemia',
            'concept_code' => 'E78.5',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2012-01-01',
            'severity' => 'mild',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'DVT right lower extremity',
            'concept_code' => 'I82.41',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-11-01',
        ]);

        // ── Medications ─────────────────────────────────────────

        // Line 1: Osimertinib (EGFR TKI, 23 months)
        $this->addMedication($patient, [
            'drug_name' => 'Osimertinib 80mg PO daily',
            'concept_code' => '1946821',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2021-05-03',
            'end_date' => '2023-04-12',
        ]);

        // Line 2: Amivantamab + Lazertinib
        $this->addMedication($patient, [
            'drug_name' => 'Amivantamab 1400mg IV Q2W',
            'concept_code' => '2591409',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-05-15',
            'end_date' => '2024-07-15',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Lazertinib 240mg PO daily',
            'concept_code' => '2660001',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2023-05-15',
            'end_date' => '2024-07-15',
        ]);

        // Line 3: Carboplatin/Pemetrexed induction + maintenance
        $this->addMedication($patient, [
            'drug_name' => 'Carboplatin AUC5 IV Day 1 Q21d',
            'concept_code' => '40048',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2024-08-12',
            'end_date' => '2024-12-15',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Pemetrexed 500mg/m² IV Day 1 Q21d',
            'concept_code' => '337523',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2024-08-12',
            'end_date' => '2025-07-18',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Pegfilgrastim 6mg SQ',
            'concept_code' => '338036',
            'vocabulary' => 'RxNorm',
            'status' => 'completed',
            'start_date' => '2024-09-20',
            'end_date' => '2024-12-15',
        ]);

        // Line 4: Investigational Trop-2 ADC (clinical trial)
        $this->addMedication($patient, [
            'drug_name' => 'Trop-2 ADC (investigational, clinical trial)',
            'concept_code' => 'TRIAL-ADC-001',
            'vocabulary' => 'local',
            'status' => 'active',
            'start_date' => '2026-02-01',
        ]);

        // Supportive / comorbidity
        $this->addMedication($patient, [
            'drug_name' => 'Lisinopril 10mg PO daily',
            'concept_code' => '104377',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2010-06-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Apixaban 5mg PO BID',
            'concept_code' => '1364430',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2024-11-10',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'CT-guided core needle biopsy right upper lobe',
            'concept_code' => '32405',
            'vocabulary' => 'CPT',
            'performed_at' => '2021-04-14',
            'specialty' => 'Interventional Radiology',
            'body_site' => 'Right upper lobe',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Port-a-cath placement right subclavian',
            'concept_code' => '36561',
            'vocabulary' => 'CPT',
            'performed_at' => '2021-04-28',
            'specialty' => 'Interventional Radiology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Stereotactic radiosurgery (SRS) right temporal brain met 24Gy',
            'concept_code' => '77372',
            'vocabulary' => 'CPT',
            'performed_at' => '2023-05-01',
            'specialty' => 'Radiation Oncology',
            'body_site' => 'Brain',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Molecular tumor board review',
            'concept_code' => '0250U',
            'vocabulary' => 'CPT',
            'performed_at' => '2026-01-20',
            'specialty' => 'Medical Oncology',
        ]);

        // ── Visits ──────────────────────────────────────────────

        // Initial workup & diagnosis
        $diagnosisVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-04-10',
            'department' => 'Pulmonology',
            'provider_name' => 'Dr. Alan Foster',
            'reason' => 'Persistent cough, hemoptysis — initial workup, CT chest ordered',
        ]);

        $biopsyVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2021-04-14',
            'department' => 'Interventional Radiology',
            'provider_name' => 'Dr. Nina Zhao',
            'reason' => 'CT-guided core needle biopsy of RUL mass',
        ]);

        $oncologyInitial = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-04-28',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'New diagnosis — EGFR-mutant lung adenocarcinoma Stage IVB, treatment planning',
        ]);

        $portVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2021-04-28',
            'department' => 'Interventional Radiology',
            'provider_name' => 'Dr. Nina Zhao',
            'reason' => 'Port-a-cath placement',
        ]);

        $neuroVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-05-05',
            'department' => 'Neuro-oncology',
            'provider_name' => 'Dr. Steven Liu',
            'reason' => 'Brain metastases evaluation — 3 lesions, monitor on osimertinib',
        ]);

        // Treatment monitoring (Line 1)
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-07-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — first response assessment on osimertinib',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2021-10-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — continued response on osimertinib',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-04-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — stable disease, monitor LFTs on osimertinib',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2022-10-14',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — stable disease, continue osimertinib',
        ]);

        // Progression and Line 2
        $pdVisit1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-04-12',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Progressive disease on osimertinib — ctDNA shows C797S + MET amp, plan Line 2',
        ]);

        $srsVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2023-05-01',
            'department' => 'Radiation Oncology',
            'provider_name' => 'Dr. Michael Torres',
            'reason' => 'SRS to new right temporal brain metastasis — 24Gy single fraction',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-05-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Line 2 initiation — amivantamab + lazertinib',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2023-06-22',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — partial response on amivantamab + lazertinib',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-01-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — stable disease on Line 2',
        ]);

        // Progression and Line 3
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-07-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Progressive disease on amivantamab + lazertinib — new liver met, plan Line 3',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-08-12',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Line 3 initiation — carboplatin/pemetrexed cycle 1',
        ]);

        // Neutropenic fever — inpatient
        $neutropenicVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2024-09-18',
            'department' => 'Emergency Medicine',
            'provider_name' => 'Dr. Rachel Kim',
            'reason' => 'Neutropenic fever — ANC 0.8, admitted for IV antibiotics',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2024-10-02',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Post-neutropenic fever follow-up — counts recovering, add pegfilgrastim',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-01-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — partial response on carboplatin/pemetrexed, transition to pemetrexed maintenance',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-07-18',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Restaging — stable on pemetrexed maintenance',
        ]);

        // DVT
        $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'visit_date' => '2024-11-10',
            'department' => 'Emergency Medicine',
            'provider_name' => 'Dr. James Wong',
            'reason' => 'Right leg swelling — DVT confirmed on duplex, start apixaban',
        ]);

        // Progression and Line 4
        $pdVisit3 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-15',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Progressive disease — liver growth, new peritoneal nodule, discuss trial options',
        ]);

        $tumorBoardVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Molecular tumor board — identified Trop-2 ADC trial',
        ]);

        $trialVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-01',
            'department' => 'Clinical Trials Office',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'Line 4 — Trop-2 ADC clinical trial enrollment and first dose',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-03-20',
            'department' => 'Medical Oncology',
            'provider_name' => 'Dr. Catherine Park',
            'reason' => 'ADC trial cycle 3 — monitoring labs, early assessment',
        ]);

        // ── Clinical Notes ──────────────────────────────────────

        $this->addNote($patient, [
            'visit_id' => $biopsyVisit->id,
            'note_type' => 'Pathology Report',
            'note_date' => '2021-04-16',
            'author' => 'Dr. Patricia Mendez',
            'content' => "SURGICAL PATHOLOGY REPORT\n\nSpecimen: CT-guided core needle biopsy, right upper lobe lung mass\n\nGROSS: Three core fragments, 1.2 cm aggregate length, tan-white, firm.\n\nMICROSCOPIC: Adenocarcinoma, moderately differentiated, with acinar and lepidic growth patterns. Tumor cells show enlarged nuclei with prominent nucleoli, moderate cytoplasm with mucin vacuoles.\n\nIMMUNOHISTOCHEMISTRY:\n- TTF-1: Positive (diffuse, strong)\n- Napsin A: Positive\n- CK7: Positive\n- CK20: Negative\n- p40: Negative\n- PD-L1 (22C3): TPS 15%\n- Ki-67: 35%\n\nDIAGNOSIS: Invasive adenocarcinoma, lung primary, moderately differentiated. PD-L1 TPS 15% (low positive). Recommend molecular profiling for targetable alterations.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $biopsyVisit->id,
            'note_type' => 'Molecular Profiling Report',
            'note_date' => '2021-04-25',
            'author' => 'Foundation Medicine',
            'content' => "FOUNDATIONONE CDx — COMPREHENSIVE GENOMIC PROFILING\n\nPatient: James Whitfield | DOB: 1959-08-22 | Specimen: Lung, RUL\nTumor Type: Lung adenocarcinoma | Specimen received: 2021-04-16\n\nGENOMIC FINDINGS:\n1. EGFR L858R (exon 21) — ACTIVATING MUTATION\n   - Variant allele frequency: 35%\n   - FDA-approved therapies: osimertinib (Tagrisso), erlotinib, gefitinib, afatinib\n   - NCCN Category 1 recommendation for first-line osimertinib\n\n2. TP53 R248W (exon 7) — LOSS OF FUNCTION\n   - Prognostic significance: associated with shorter PFS on EGFR TKI\n\nNO ALTERATIONS DETECTED IN: ALK, ROS1, RET, MET exon 14, BRAF V600E, KRAS, NTRK, HER2\n\nTMB: 4.2 mutations/Mb (low)\nMSI: Stable (MSS)\n\nRECOMMENDATION: First-line osimertinib per NCCN guidelines. TP53 co-mutation associated with inferior outcomes on EGFR TKI therapy.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $oncologyInitial->id,
            'note_type' => 'Treatment Initiation Note',
            'note_date' => '2021-04-28',
            'author' => 'Dr. Catherine Park',
            'content' => "MEDICAL ONCOLOGY — TREATMENT INITIATION\n\nPatient: James Whitfield, 61M\nDiagnosis: EGFR L858R-mutant lung adenocarcinoma, Stage IVB (cT2a N2 M1c)\nSites of disease: RUL primary (3.8 cm), subcarinal lymphadenopathy, right adrenal metastasis, 3 brain metastases (R frontal 12mm, L parietal 8mm, R cerebellar 6mm)\n\nMOLECULAR: EGFR L858R (VAF 35%), TP53 R248W, TMB-low (4.2), PD-L1 TPS 15%\n\nTREATMENT PLAN:\n- Line 1: Osimertinib 80mg PO daily (NCCN Category 1, FLAURA trial)\n- Brain mets: Osimertinib has CNS penetrance — will monitor with brain MRI Q3 months\n- Port-a-cath placed today for future IV access\n- Baseline labs, CEA trending\n- Restaging CT Q3 months, brain MRI Q3 months\n\nGOALS OF CARE: Palliative intent. Discussed expected PFS of 18-20 months on osimertinib. TP53 co-mutation may shorten response duration.\n\nECOG PS: 1\nStarting osimertinib 2021-05-03.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $pdVisit1->id,
            'note_type' => 'Resistance Analysis / ctDNA Report',
            'note_date' => '2023-04-20',
            'author' => 'Guardant Health / Dr. Catherine Park',
            'content' => "LIQUID BIOPSY — GUARDANT360 CDx\n\nPatient: James Whitfield | Date collected: 2023-04-14\n\nctDNA FINDINGS:\n1. EGFR L858R — DETECTED (VAF 8.2%) — original driver\n2. EGFR C797S (exon 20) — DETECTED (VAF 12%) — ACQUIRED RESISTANCE MUTATION\n   - Classic resistance to osimertinib, found in ~15% of resistant cases\n   - cis configuration with L858R (confirmed by phasing)\n3. MET amplification — DETECTED (copy number 8)\n   - Bypass resistance mechanism, found in ~25% of osimertinib resistance\n\nCLINICAL INTERPRETATION:\nDual resistance mechanism (C797S + MET amp) explains disease progression on osimertinib after 23 months. The cis C797S configuration precludes 1st-gen EGFR TKI combination. MET amplification provides rationale for bispecific EGFR-MET targeting.\n\nRECOMMENDATION: Amivantamab (bispecific EGFR-MET) + lazertinib per MARIPOSA-2 data. Consider clinical trial if available.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $srsVisit->id,
            'note_type' => 'SRS Treatment Planning Note',
            'note_date' => '2023-05-01',
            'author' => 'Dr. Michael Torres',
            'content' => "STEREOTACTIC RADIOSURGERY — TREATMENT NOTE\n\nPatient: James Whitfield, 63M\nIndication: New right temporal lobe brain metastasis, 14mm, symptomatic (headaches)\n\nPrior brain mets (diagnosed 2021-04): R frontal, L parietal, R cerebellar — all near CR on osimertinib.\nNew R temporal met identified on surveillance brain MRI (2023-04-14), concurrent with systemic progression.\n\nTREATMENT DELIVERED:\n- Target: Right temporal lobe metastasis\n- Dose: 24 Gy in single fraction (ASTRO guideline for lesion 2-3 cm)\n- Technique: Frameless SRS, cone-beam CT verification\n- GTV: 14mm × 12mm × 11mm\n- PTV margin: 1mm\n- Critical structures spared: brainstem, optic apparatus, cochlea\n\nPLAN: Follow-up brain MRI in 6-8 weeks. Continue systemic therapy with amivantamab + lazertinib (CNS activity uncertain, may need additional SRS).",
        ]);

        $this->addNote($patient, [
            'visit_id' => $neutropenicVisit->id,
            'note_type' => 'Emergency Department Note',
            'note_date' => '2024-09-18',
            'author' => 'Dr. Rachel Kim',
            'content' => "EMERGENCY DEPARTMENT NOTE\n\nChief Complaint: Fever to 39.2°C, rigors, fatigue × 2 days\n\nHPI: 65M with metastatic EGFR-mutant lung adenocarcinoma on carboplatin/pemetrexed (cycle 2, day 10), presenting with febrile neutropenia. Temperature 39.2°C at home. Denies cough worsening, chest pain, dysuria. Mild nausea, poor PO intake.\n\nVITALS: T 39.4°C, HR 112, BP 98/62, RR 20, SpO2 96% RA\n\nLABS: WBC 2.1 (ANC 0.8), Hgb 9.8, Plt 112, Lactate 1.8, CRP 84, procalcitonin 0.62\nBlood cultures × 2 drawn. UA negative. CXR: no new infiltrate.\n\nASSESSMENT: Febrile neutropenia, MASCC score 19 (intermediate risk)\n\nPLAN:\n1. Admit to oncology service\n2. Piperacillin-tazobactam 4.5g IV Q6H\n3. IV fluids — NS 1L bolus, then 125 mL/hr\n4. Hold chemotherapy\n5. Daily CBC, BMP\n6. Oncology consult for G-CSF initiation",
        ]);

        $this->addNote($patient, [
            'visit_id' => $pdVisit3->id,
            'note_type' => 'Progression Note',
            'note_date' => '2026-01-15',
            'author' => 'Dr. Catherine Park',
            'content' => "MEDICAL ONCOLOGY — DISEASE PROGRESSION\n\nPatient: James Whitfield, 66M | EGFR L858R lung adenocarcinoma Stage IVB\n\nPROGRESSION SUMMARY:\n- CT (2026-01-15): Liver metastasis increased from 11mm to 24mm. NEW peritoneal nodule identified. RUL primary stable. Sum of target lesions 42mm (prior 31mm) — RECIST: Progressive Disease.\n- CEA trending up: 19.6 ng/mL (from 8.1 on maintenance pemetrexed)\n- ECOG PS: Declined from 1 to 2. Increased fatigue, early satiety (peritoneal disease).\n\nTREATMENT HISTORY:\n- Line 1: Osimertinib (23 months, best response PR -69%) — PD via C797S + MET amp\n- Line 2: Amivantamab + lazertinib (14 months, best response PR) — PD\n- Line 3: Carboplatin/pemetrexed → pemetrexed maintenance (11 months, best response PR -42%) — PD\n\nPLAN: Molecular tumor board review 2026-01-20. Evaluate clinical trial options (Trop-2 ADC, HER3 ADC). Repeat ctDNA. Palliative care referral for symptom management.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $tumorBoardVisit->id,
            'note_type' => 'Molecular Tumor Board Note',
            'note_date' => '2026-01-20',
            'author' => 'Molecular Tumor Board',
            'content' => "MOLECULAR TUMOR BOARD — CASE REVIEW\n\nPatient: James Whitfield, 66M | EGFR L858R lung adenocarcinoma, 4th line\n\nGENOMIC REVIEW:\n- Original: EGFR L858R, TP53 R248W\n- Acquired resistance: EGFR C797S (cis), MET amplification (CN 8)\n- TMB-low (4.2), MSS — immunotherapy unlikely to benefit\n- PD-L1 TPS 15% — marginal, insufficient for single-agent checkpoint inhibitor\n\nTREATMENT OPTIONS DISCUSSED:\n1. Trop-2 ADC (datopotamab deruxtecan) — Phase II trial open, TROP2 IHC 2+ on archival tissue\n   → RECOMMENDED: Enrolled in institutional trial NCT-XXXX\n2. HER3-directed ADC (patritumab deruxtecan) — HER3 expression unknown, would need re-biopsy\n3. Docetaxel + ramucirumab — standard option, modest benefit\n4. Re-challenge with osimertinib + savolitinib — C797S cis configuration limits benefit\n\nDECISION: Proceed with Trop-2 ADC clinical trial. Start date targeted for 2026-02-01.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $trialVisit->id,
            'note_type' => 'Clinical Trial Enrollment Note',
            'note_date' => '2026-02-01',
            'author' => 'Dr. Catherine Park',
            'content' => "CLINICAL TRIAL ENROLLMENT — TROP-2 ADC\n\nProtocol: Phase II, open-label, single-arm study of Trop-2 ADC in EGFR-mutant NSCLC after progression on EGFR TKI and platinum-based chemotherapy\n\nELIGIBILITY:\n- EGFR-mutant NSCLC with prior EGFR TKI and platinum: MET ✓\n- Measurable disease (RECIST 1.1): MET ✓ (liver 24mm, peritoneal nodule)\n- ECOG PS ≤ 2: MET ✓ (ECOG 2)\n- Adequate organ function: MET ✓\n- No active brain mets requiring treatment: MET ✓ (prior SRS, stable)\n\nTROP-2 IHC (archival tissue): 2+ (moderate expression)\n\nCONSENT: Signed 2026-01-28. Reviewed risks including interstitial lung disease, neutropenia, nausea, ocular toxicity.\n\nDOSING: Per protocol, IV Q3W. Cycle 1 Day 1 administered 2026-02-01 without incident.\nPre-medications: dexamethasone, ondansetron, diphenhydramine.\n\nMONITORING: CBC weekly × 2 cycles, LFTs Q3W, ophthalmology baseline and Q6W, CT restaging Q6W.",
        ]);

        $this->addNote($patient, [
            'note_type' => 'Radiology Report',
            'note_date' => '2021-04-12',
            'author' => 'Dr. Robert Chen',
            'content' => "CT CHEST/ABDOMEN/PELVIS WITH CONTRAST — BASELINE STAGING\n\nINDICATION: Newly diagnosed lung mass, staging\n\nFINDINGS:\nCHEST:\n- Right upper lobe: Spiculated mass measuring 38 × 32 mm (axial). Abutting the visceral pleura without definite chest wall invasion.\n- Subcarinal lymphadenopathy: 21 × 18 mm, FDG-avid on recent PET (SUV 8.4).\n- No pleural effusion. No additional pulmonary nodules.\n\nABDOMEN:\n- Right adrenal gland: 15 × 12 mm enhancing nodule, suspicious for metastasis.\n- Left adrenal: Normal.\n- Liver, spleen, pancreas, kidneys: Unremarkable.\n- No retroperitoneal lymphadenopathy.\n\nIMPRESSION:\n1. RUL spiculated mass (38mm) — primary lung malignancy\n2. Subcarinal lymphadenopathy (21mm) — metastatic\n3. Right adrenal metastasis (15mm)\n4. Stage IVB (cT2a N2 M1c)",
        ]);

        $this->addNote($patient, [
            'note_type' => 'Neuro-oncology Consultation',
            'note_date' => '2021-05-05',
            'author' => 'Dr. Steven Liu',
            'content' => "NEURO-ONCOLOGY CONSULTATION\n\nReferral: Brain metastases in setting of new EGFR-mutant lung adenocarcinoma\n\nBrain MRI (2021-04-13) Review:\n- Right frontal lobe: 12mm enhancing lesion with surrounding edema\n- Left parietal lobe: 8mm enhancing lesion, minimal edema\n- Right cerebellar hemisphere: 6mm enhancing lesion\n- No leptomeningeal enhancement. No midline shift.\n\nASSESSMENT: Three brain metastases, asymptomatic, no mass effect.\n\nPLAN:\n1. Defer upfront SRS — osimertinib has demonstrated CNS response rate ~70% (FLAURA CNS subanalysis)\n2. Start dexamethasone 2mg BID × 2 weeks if any neurologic symptoms\n3. Surveillance brain MRI Q3 months\n4. SRS reserved for progression or symptomatic lesions\n5. Neurocognitive baseline assessment completed",
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Baseline (2021-04-12)
        $this->addLabPanel($patient, '2021-04-12', [
            ['CEA',              '2039-6',  18.4, 'ng/mL',  null, 5.0,  'H'],
            ['WBC',              '6690-2',  7.2,  'K/uL',   4.5,  11.0, null],
            ['ANC',              '751-8',   4.8,  'K/uL',   1.5,  8.0,  null],
            ['Hemoglobin',       '718-7',   14.1, 'g/dL',   13.5, 17.5, null],
            ['Platelet Count',   '777-3',   245,  'K/uL',   150,  400,  null],
            ['AST',              '1920-8',  22,   'U/L',    10,   40,   null],
            ['ALT',              '1742-6',  18,   'U/L',    7,    56,   null],
            ['Creatinine',       '2160-0',  0.9,  'mg/dL',  0.7,  1.3,  null],
        ]);

        // Responding on osimertinib (2021-07-15)
        $this->addLabPanel($patient, '2021-07-15', [
            ['CEA',              '2039-6',  5.2,  'ng/mL',  null, 5.0,  'H'],
            ['WBC',              '6690-2',  6.8,  'K/uL',   4.5,  11.0, null],
            ['Hemoglobin',       '718-7',   13.5, 'g/dL',   13.5, 17.5, null],
            ['Platelet Count',   '777-3',   218,  'K/uL',   150,  400,  null],
        ]);

        // Stable on osimertinib (2022-04-18)
        $this->addLabPanel($patient, '2022-04-18', [
            ['CEA',              '2039-6',  4.8,  'ng/mL',  null, 5.0,  null],
            ['AST',              '1920-8',  34,   'U/L',    10,   40,   null],
            ['ALT',              '1742-6',  42,   'U/L',    7,    56,   null],
        ]);

        // PD1 — progression on osimertinib (2023-04-12)
        $this->addLabPanel($patient, '2023-04-12', [
            ['CEA',              '2039-6',  12.7, 'ng/mL',  null, 5.0,  'H'],
        ]);

        // PD2 — progression on amivantamab + lazertinib (2024-07-15)
        $this->addLabPanel($patient, '2024-07-15', [
            ['CEA',              '2039-6',  22.3, 'ng/mL',  null, 5.0,  'H'],
        ]);

        // Chemo nadir — neutropenic fever (2024-09-15)
        $this->addLabPanel($patient, '2024-09-15', [
            ['WBC',              '6690-2',  2.1,  'K/uL',   4.5,  11.0, 'L'],
            ['ANC',              '751-8',   0.8,  'K/uL',   1.5,  8.0,  'CL'],
            ['Hemoglobin',       '718-7',   9.8,  'g/dL',   13.5, 17.5, 'L'],
            ['Platelet Count',   '777-3',   112,  'K/uL',   150,  400,  'L'],
        ]);

        // Chemo recovery (2024-10-02)
        $this->addLabPanel($patient, '2024-10-02', [
            ['WBC',              '6690-2',  4.5,  'K/uL',   4.5,  11.0, null],
            ['ANC',              '751-8',   2.9,  'K/uL',   1.5,  8.0,  null],
            ['Hemoglobin',       '718-7',   10.4, 'g/dL',   13.5, 17.5, 'L'],
            ['Platelet Count',   '777-3',   156,  'K/uL',   150,  400,  null],
        ]);

        // Responding Line 3 (2025-01-20)
        $this->addLabPanel($patient, '2025-01-20', [
            ['CEA',              '2039-6',  8.1,  'ng/mL',  null, 5.0,  'H'],
        ]);

        // PD3 — progression (2026-01-15)
        $this->addLabPanel($patient, '2026-01-15', [
            ['CEA',              '2039-6',  19.6, 'ng/mL',  null, 5.0,  'H'],
        ]);

        // On ADC trial (2026-03-20)
        $this->addLabPanel($patient, '2026-03-20', [
            ['WBC',              '6690-2',  5.1,  'K/uL',   4.5,  11.0, null],
            ['ANC',              '751-8',   3.2,  'K/uL',   1.5,  8.0,  null],
            ['Hemoglobin',       '718-7',   11.2, 'g/dL',   13.5, 17.5, 'L'],
            ['Platelet Count',   '777-3',   198,  'K/uL',   150,  400,  null],
            ['AST',              '1920-8',  48,   'U/L',    10,   40,   'H'],
            ['ALT',              '1742-6',  55,   'U/L',    7,    56,   null],
        ]);

        // ── RECIST Imaging — CT studies with target lesion measurements ──

        // Baseline CT (2021-04-12)
        $ct1 = $this->addImagingStudy($patient, [
            'study_date' => '2021-04-12',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen/Pelvis with Contrast — Baseline Staging',
            'indication' => 'Newly diagnosed lung mass, staging',
            'findings' => 'RUL spiculated mass 38mm. Subcarinal LN 21mm. Right adrenal metastasis 15mm. Sum of target lesions: 74mm. RECIST: Baseline.',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 38,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-04-12',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 21,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-04-12',
        ]);
        $this->addImagingMeasurement($ct1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right adrenal metastasis',
            'value_numeric' => 15,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-04-12',
        ]);

        // CT — first response (2021-07-15)
        $ct2 = $this->addImagingStudy($patient, [
            'study_date' => '2021-07-15',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on osimertinib, first assessment',
            'findings' => 'RUL mass decreased to 19mm (from 38mm). Subcarinal LN 10mm. R adrenal 8mm. Sum 37mm (baseline 74mm). RECIST: Partial Response (-50%).',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 19,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-07-15',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 10,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-07-15',
        ]);
        $this->addImagingMeasurement($ct2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right adrenal metastasis',
            'value_numeric' => 8,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-07-15',
        ]);

        // CT — continued response (2021-10-20)
        $ct3 = $this->addImagingStudy($patient, [
            'study_date' => '2021-10-20',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on osimertinib',
            'findings' => 'RUL mass 15mm. Subcarinal LN 8mm. R adrenal too small to measure. Sum 23mm (baseline 74mm). RECIST: Partial Response (-69%).',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 15,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-10-20',
        ]);
        $this->addImagingMeasurement($ct3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 8,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2021-10-20',
        ]);

        // CT — stable (2022-04-18)
        $ct4 = $this->addImagingStudy($patient, [
            'study_date' => '2022-04-18',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on osimertinib',
            'findings' => 'RUL mass 14mm. Subcarinal LN 7mm. Sum 21mm. RECIST: Stable Disease.',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2022-04-18',
        ]);
        $this->addImagingMeasurement($ct4, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2022-04-18',
        ]);

        // CT — stable (2022-10-14)
        $ct5 = $this->addImagingStudy($patient, [
            'study_date' => '2022-10-14',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on osimertinib',
            'findings' => 'RUL mass 15mm. Subcarinal LN 7mm. Sum 22mm. RECIST: Stable Disease.',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 15,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2022-10-14',
        ]);
        $this->addImagingMeasurement($ct5, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2022-10-14',
        ]);

        // CT — PD1 with new lesion (2023-04-12)
        $ct6 = $this->addImagingStudy($patient, [
            'study_date' => '2023-04-12',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on osimertinib — rising CEA',
            'findings' => 'RUL mass 16mm. Subcarinal LN 9mm. NEW left adrenal nodule 6mm. Sum 25mm + new lesion. RECIST: Progressive Disease.',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 16,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2023-04-12',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 9,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2023-04-12',
        ]);
        $this->addImagingMeasurement($ct6, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left adrenal nodule (new)',
            'value_numeric' => 6,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2023-04-12',
        ]);

        // CT — new baseline PR on Line 2 (2023-06-22)
        $ct7 = $this->addImagingStudy($patient, [
            'study_date' => '2023-06-22',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on amivantamab + lazertinib',
            'findings' => 'RUL mass 12mm. Subcarinal LN 7mm. L adrenal 4mm. Sum 23mm (new baseline). RECIST: Partial Response.',
        ]);
        $this->addImagingMeasurement($ct7, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2023-06-22',
        ]);
        $this->addImagingMeasurement($ct7, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2023-06-22',
        ]);
        $this->addImagingMeasurement($ct7, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left adrenal nodule',
            'value_numeric' => 4,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2023-06-22',
        ]);

        // CT — stable on Line 2 (2024-01-18)
        $ct8 = $this->addImagingStudy($patient, [
            'study_date' => '2024-01-18',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on amivantamab + lazertinib',
            'findings' => 'RUL mass 13mm. Subcarinal LN 7mm. L adrenal 4mm. Sum 24mm. RECIST: Stable Disease.',
        ]);
        $this->addImagingMeasurement($ct8, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 13,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2024-01-18',
        ]);
        $this->addImagingMeasurement($ct8, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Subcarinal LN',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2024-01-18',
        ]);
        $this->addImagingMeasurement($ct8, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left adrenal nodule',
            'value_numeric' => 4,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2024-01-18',
        ]);

        // CT — PD2 with new liver met (2024-07-15)
        $ct9 = $this->addImagingStudy($patient, [
            'study_date' => '2024-07-15',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on amivantamab + lazertinib — rising CEA',
            'findings' => 'RUL mass 18mm. NEW mediastinal LN 12mm. NEW liver metastasis (segment VI) 20mm. Sum 50mm. RECIST: Progressive Disease.',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 18,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2024-07-15',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Mediastinal LN (new)',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2024-07-15',
        ]);
        $this->addImagingMeasurement($ct9, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver metastasis segment VI (new)',
            'value_numeric' => 20,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2024-07-15',
        ]);

        // CT — PR on Line 3 (2025-01-20)
        $ct10 = $this->addImagingStudy($patient, [
            'study_date' => '2025-01-20',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on carboplatin/pemetrexed',
            'findings' => 'RUL mass 10mm. Mediastinal LN 8mm. Liver met 11mm. Sum 29mm (from 50mm). RECIST: Partial Response (-42%).',
        ]);
        $this->addImagingMeasurement($ct10, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 10,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2025-01-20',
        ]);
        $this->addImagingMeasurement($ct10, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Mediastinal LN',
            'value_numeric' => 8,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2025-01-20',
        ]);
        $this->addImagingMeasurement($ct10, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver metastasis segment VI',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2025-01-20',
        ]);

        // CT — stable on pemetrexed maintenance (2025-07-18)
        $ct11 = $this->addImagingStudy($patient, [
            'study_date' => '2025-07-18',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging on pemetrexed maintenance',
            'findings' => 'RUL mass 11mm. Mediastinal LN 9mm. Liver met 11mm. Sum 31mm. RECIST: Stable Disease.',
        ]);
        $this->addImagingMeasurement($ct11, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2025-07-18',
        ]);
        $this->addImagingMeasurement($ct11, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Mediastinal LN',
            'value_numeric' => 9,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2025-07-18',
        ]);
        $this->addImagingMeasurement($ct11, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver metastasis segment VI',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2025-07-18',
        ]);

        // CT — PD3 (2026-01-15)
        $ct12 = $this->addImagingStudy($patient, [
            'study_date' => '2026-01-15',
            'modality' => 'CT',
            'body_site' => 'Chest/Abdomen',
            'description' => 'CT Chest/Abdomen — Restaging',
            'indication' => 'Restaging — rising CEA, clinical decline',
            'findings' => 'RUL mass 11mm stable. Mediastinal LN 7mm. Liver met increased to 24mm (from 11mm). NEW peritoneal nodule. Sum 42mm. RECIST: Progressive Disease.',
        ]);
        $this->addImagingMeasurement($ct12, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Liver metastasis segment VI',
            'value_numeric' => 24,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2026-01-15',
        ]);
        $this->addImagingMeasurement($ct12, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Peritoneal nodule (new)',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2026-01-15',
        ]);
        $this->addImagingMeasurement($ct12, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'RUL mass',
            'value_numeric' => 11,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2026-01-15',
        ]);
        $this->addImagingMeasurement($ct12, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Mediastinal LN',
            'value_numeric' => 7,
            'unit' => 'mm',
            'measured_by' => 'Dr. Robert Chen',
            'measured_at' => '2026-01-15',
        ]);

        // ── Brain MRI studies ───────────────────────────────────

        // Baseline brain MRI (2021-04-13)
        $mri1 = $this->addImagingStudy($patient, [
            'study_date' => '2021-04-13',
            'modality' => 'MR',
            'body_site' => 'Brain',
            'description' => 'Brain MRI with Contrast — Baseline',
            'indication' => 'Staging — newly diagnosed lung adenocarcinoma',
            'findings' => 'Three enhancing brain metastases: R frontal 12mm, L parietal 8mm, R cerebellar 6mm. No leptomeningeal enhancement. No midline shift.',
        ]);
        $this->addImagingMeasurement($mri1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right frontal brain metastasis',
            'value_numeric' => 12,
            'unit' => 'mm',
            'measured_by' => 'Dr. Steven Liu',
            'measured_at' => '2021-04-13',
        ]);
        $this->addImagingMeasurement($mri1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Left parietal brain metastasis',
            'value_numeric' => 8,
            'unit' => 'mm',
            'measured_by' => 'Dr. Steven Liu',
            'measured_at' => '2021-04-13',
        ]);
        $this->addImagingMeasurement($mri1, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right cerebellar brain metastasis',
            'value_numeric' => 6,
            'unit' => 'mm',
            'measured_by' => 'Dr. Steven Liu',
            'measured_at' => '2021-04-13',
        ]);

        // Brain MRI — near CR on osimertinib (2021-07-16)
        $mri2 = $this->addImagingStudy($patient, [
            'study_date' => '2021-07-16',
            'modality' => 'MR',
            'body_site' => 'Brain',
            'description' => 'Brain MRI with Contrast — Restaging',
            'indication' => 'Surveillance brain MRI on osimertinib',
            'findings' => 'R frontal met decreased to 4mm (near complete response). L parietal and R cerebellar lesions no longer measurable. No new lesions.',
        ]);
        $this->addImagingMeasurement($mri2, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right frontal brain metastasis',
            'value_numeric' => 4,
            'unit' => 'mm',
            'measured_by' => 'Dr. Steven Liu',
            'measured_at' => '2021-07-16',
        ]);

        // Brain MRI — new R temporal met (2023-04-14)
        $mri3 = $this->addImagingStudy($patient, [
            'study_date' => '2023-04-14',
            'modality' => 'MR',
            'body_site' => 'Brain',
            'description' => 'Brain MRI with Contrast — Restaging',
            'indication' => 'Surveillance brain MRI — systemic progression on osimertinib',
            'findings' => 'NEW right temporal lobe metastasis 14mm with surrounding edema. Prior R frontal met stable at 4mm. No leptomeningeal disease. RECIST: Progressive Disease (new lesion).',
        ]);
        $this->addImagingMeasurement($mri3, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'measurement_name' => 'Right temporal brain metastasis (new)',
            'value_numeric' => 14,
            'unit' => 'mm',
            'measured_by' => 'Dr. Steven Liu',
            'measured_at' => '2023-04-14',
        ]);

        // ── Observations ────────────────────────────────────────

        // ECOG Performance Status trending
        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_numeric' => 1,
            'observed_at' => '2021-04-28',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_numeric' => 1,
            'observed_at' => '2023-04-12',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_numeric' => 1,
            'observed_at' => '2024-08-12',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'functional_status',
            'value_numeric' => 2,
            'observed_at' => '2026-01-15',
        ]);

        // TNM Staging
        $this->addObservation($patient, [
            'observation_name' => 'TNM Clinical Stage',
            'category' => 'staging',
            'value_text' => 'cT2a N2 M1c Stage IVB',
            'observed_at' => '2021-04-12',
        ]);

        // ── Genomic Variants ────────────────────────────────────

        // Original molecular profiling (2021-04-25)
        $this->addGenomicVariant($patient, [
            'gene' => 'EGFR',
            'variant_name' => 'p.L858R',
            'variant_type' => 'SNV',
            'chromosome' => 'chr7',
            'exon' => '21',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.35,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'FDA_approved_therapy',
            'detected_at' => '2021-04-25',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'TP53',
            'variant_name' => 'p.R248W',
            'variant_type' => 'SNV',
            'chromosome' => 'chr17',
            'exon' => '7',
            'zygosity' => 'heterozygous',
            'clinical_significance' => 'pathogenic',
            'actionability' => 'prognostic',
            'detected_at' => '2021-04-25',
        ]);

        // Acquired resistance variants (ctDNA, 2023-04-20)
        $this->addGenomicVariant($patient, [
            'gene' => 'EGFR',
            'variant_name' => 'p.C797S',
            'variant_type' => 'SNV',
            'chromosome' => 'chr7',
            'exon' => '20',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.12,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'resistance_mechanism',
            'detected_at' => '2023-04-20',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'MET',
            'variant_name' => 'Amplification (CN 8)',
            'variant_type' => 'CNV',
            'chromosome' => 'chr7',
            'clinical_significance' => 'pathogenic',
            'actionability' => 'combination_therapy',
            'detected_at' => '2023-04-20',
        ]);

        // ── Condition Eras ──────────────────────────────────────

        $this->addConditionEra($patient, [
            'condition_name' => 'Lung adenocarcinoma',
            'era_start' => '2021-04-01',
            'era_end' => null,
            'occurrence_count' => 25,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Brain metastases',
            'era_start' => '2021-04-01',
            'era_end' => null,
            'occurrence_count' => 6,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Treatment toxicity (chemotherapy)',
            'era_start' => '2024-08-01',
            'era_end' => '2025-01-01',
            'occurrence_count' => 4,
        ]);

        // ── Drug Eras ───────────────────────────────────────────

        $this->addDrugEra($patient, [
            'drug_name' => 'Osimertinib',
            'era_start' => '2021-05-03',
            'era_end' => '2023-04-12',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Amivantamab + Lazertinib',
            'era_start' => '2023-05-15',
            'era_end' => '2024-07-15',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Carboplatin/Pemetrexed',
            'era_start' => '2024-08-12',
            'era_end' => '2025-07-18',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Trop-2 ADC (investigational)',
            'era_start' => '2026-02-01',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
