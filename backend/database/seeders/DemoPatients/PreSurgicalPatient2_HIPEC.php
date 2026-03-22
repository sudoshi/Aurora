<?php

namespace Database\Seeders\DemoPatients;

class PreSurgicalPatient2_HIPEC
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-PS-002',
            'first_name' => 'Carmen',
            'last_name' => 'Delgado',
            'date_of_birth' => '1972-05-18',
            'sex' => 'Female',
            'race' => 'White',
            'ethnicity' => 'Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-CD-55129');
        $this->addIdentifier($patient, 'cancer_center_mrn', 'NCC-887341', 'National Cancer Center');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Pseudomyxoma peritonei from low-grade appendiceal mucinous neoplasm',
            'concept_code' => 'C78.6',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2025-11-01',
            'severity' => 'severe',
            'body_site' => 'Peritoneum',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Low-grade appendiceal mucinous neoplasm (LAMN)',
            'concept_code' => 'D37.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2025-11-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Coronary artery disease, status post drug-eluting stent to LAD',
            'concept_code' => 'I25.10',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-11-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Presence of coronary artery drug-eluting stent',
            'concept_code' => 'Z95.5',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-11-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Essential hypertension',
            'concept_code' => 'I10',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-01-01',
            'severity' => 'mild',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Type 2 diabetes mellitus without insulin, with ophthalmic complications',
            'concept_code' => 'E11.65',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2018-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hypothyroidism, unspecified',
            'concept_code' => 'E03.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2020-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Moderate protein-calorie malnutrition',
            'concept_code' => 'E44.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-12-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Iron deficiency anemia, unspecified',
            'concept_code' => 'D50.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-11-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Major depressive disorder, recurrent, moderate',
            'concept_code' => 'F33.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2022-01-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Aspirin 81mg PO daily (continue periop per AHA for DES)',
            'concept_code' => '243670',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2025-11-15',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Clopidogrel 75mg PO daily (held 5 days pre-op)',
            'concept_code' => '32968',
            'vocabulary' => 'RxNorm',
            'status' => 'discontinued',
            'start_date' => '2025-11-15',
            'end_date' => '2026-02-25',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Metformin 1000mg PO BID (held 48h pre-op)',
            'concept_code' => '6809',
            'vocabulary' => 'RxNorm',
            'status' => 'discontinued',
            'start_date' => '2018-06-01',
            'end_date' => '2026-02-28',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Empagliflozin 10mg PO daily (held 3 days pre-op, DKA risk)',
            'concept_code' => '1545653',
            'vocabulary' => 'RxNorm',
            'status' => 'discontinued',
            'start_date' => '2023-01-01',
            'end_date' => '2026-02-27',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Lisinopril 20mg PO daily (held morning of surgery)',
            'concept_code' => '29046',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2015-06-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Amlodipine 5mg PO daily',
            'concept_code' => '17767',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2019-01-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Levothyroxine 75mcg PO daily',
            'concept_code' => '10582',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2020-03-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Sertraline 100mg PO daily (serotonin syndrome awareness)',
            'concept_code' => '36437',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2022-03-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Ferrous sulfate 325mg PO daily',
            'concept_code' => '4167',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2025-12-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Ensure Plus oral nutritional supplement TID',
            'concept_code' => '227518',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2025-12-15',
            'route' => 'oral',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Diagnostic laparoscopy with peritoneal biopsy',
            'concept_code' => '49320',
            'vocabulary' => 'CPT',
            'performed_date' => '2026-02-14',
            'performer' => 'Surgical Oncology',
            'body_site' => 'Abdomen',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Drug-eluting stent placement to LAD',
            'concept_code' => '92928',
            'vocabulary' => 'CPT',
            'performed_date' => '2025-11-10',
            'performer' => 'Interventional Cardiology',
            'body_site' => 'Heart',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $surgOncVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2026-02-18',
            'department' => 'Surgical Oncology',
            'attending_provider' => 'Dr. Elena Vasquez',
        ]);

        $cardioVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2026-02-20',
            'department' => 'Interventional Cardiology',
            'attending_provider' => 'Dr. Marcus Holt',
        ]);

        $medOncVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2026-02-22',
            'department' => 'Medical Oncology',
            'attending_provider' => 'Dr. Priya Sharma',
        ]);

        $nutritionVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2026-02-25',
            'department' => 'Nutrition / Dietetics',
            'attending_provider' => 'RD Sarah Kim',
        ]);

        $endoVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2026-02-26',
            'department' => 'Endocrinology',
            'attending_provider' => 'Dr. Karen Liu',
        ]);

        $anesthVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2026-02-28',
            'department' => 'Anesthesiology',
            'attending_provider' => 'Dr. Alan Whitfield',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $surgOncVisit->id,
            'note_type' => 'Surgical Oncology Consultation',
            'authored_at' => '2026-02-18',
            'author' => 'Dr. Elena Vasquez',
            'content' => "SURGICAL ONCOLOGY CONSULTATION — CRS-HIPEC PLANNING\n\nPatient: Carmen Delgado, 53F\nDate: 2026-02-18\n\nDIAGNOSIS: Pseudomyxoma peritonei (PMP) secondary to low-grade appendiceal mucinous neoplasm (LAMN), confirmed on diagnostic laparoscopy biopsy 2026-02-14.\n\nPCI ASSESSMENT: Peritoneal Cancer Index score 22/39 based on CT imaging and laparoscopic findings. Distribution: diffuse mucinous ascites involving all quadrants, omental cake measuring 12 x 8 cm, hepatic surface scalloping (non-invasive), extensive pelvic deposits.\n\nSURGICAL PLAN:\n- Cytoreductive surgery (CRS) with goal of CC-0 (complete cytoreduction, no visible residual disease)\n- HIPEC with mitomycin C (40mg, 90 min at 42°C) per Sugarbaker protocol\n- Anticipated procedures: greater omentectomy, peritonectomy (parietal, pelvic, diaphragmatic bilateral), appendectomy, possible splenectomy, possible low anterior resection\n- Estimated OR time: 10-14 hours\n\nCOMPLICATING FACTORS:\n1. Recent DES placement (2025-11-10) — only 3.5 months of DAPT. AHA guidelines recommend minimum 6 months DAPT for DES. Clopidogrel held 5 days pre-op but aspirin continued. Cardiology recommends cangrelor bridge intra-operatively.\n2. Moderate malnutrition with prealbumin 12 (improving from 8). Need minimum prealbumin >15 ideally before major surgery.\n3. Iron deficiency anemia — Hgb 10.8, may require intra-op transfusion.\n\nTARGET SURGERY DATE: 2026-03-05 (pending cardiology and nutrition clearance)\nRISK DISCUSSION: Morbidity 30-40% for CRS-HIPEC, mortality 2-5%. Additional cardiac risk from recent stent. Patient counseled and wishes to proceed.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $cardioVisit->id,
            'note_type' => 'Cardiology DAPT Risk Assessment',
            'authored_at' => '2026-02-20',
            'author' => 'Dr. Marcus Holt',
            'content' => "CARDIOLOGY CONSULTATION — DAPT MANAGEMENT FOR CRS-HIPEC\n\nPatient: Carmen Delgado, 53F\nDate: 2026-02-20\n\nHISTORY: DES to LAD placed 2025-11-10 for acute coronary syndrome. Currently on DAPT (aspirin 81mg + clopidogrel 75mg) for 3.5 months. Planned CRS-HIPEC surgery 2026-03-05.\n\nDILEMMA: Competing urgencies — cancer progression vs stent thrombosis risk.\n- AHA guidelines: minimum 6 months DAPT after DES, ideally 12 months\n- Cancer surgery cannot wait 6 months — PMP is progressive with PCI 22\n- Stent thrombosis risk with premature DAPT cessation: 2-5% (potentially catastrophic)\n\nPLATELET FUNCTION TESTING:\n- VerifyNow P2Y12: 68 PRU (significant residual inhibition, ref >208 = no effect)\n- Confirms adequate platelet inhibition on current DAPT\n\nRECOMMENDATIONS:\n1. Continue aspirin 81mg through surgery — DO NOT hold\n2. Hold clopidogrel 5 days pre-op (held 2026-02-25)\n3. Cangrelor IV bridge intra-operatively: 0.75 mcg/kg/min infusion during surgery, provides rapid-onset reversible P2Y12 inhibition (half-life 3-6 min)\n4. Resume clopidogrel 300mg loading dose within 24h post-op when surgical hemostasis confirmed\n5. Complete 12 months total DAPT (through November 2026)\n6. Troponin monitoring Q8H x 48h post-operatively\n7. RCRI: 2 points (CAD + major surgery) — intermediate cardiac risk\n\nCLEARED for surgery with above bridging protocol. Direct communication with surgical and anesthesia teams regarding cangrelor timing.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $nutritionVisit->id,
            'note_type' => 'Nutrition Pre-habilitation Note',
            'authored_at' => '2026-02-25',
            'author' => 'RD Sarah Kim',
            'content' => "NUTRITION PRE-HABILITATION ASSESSMENT\n\nPatient: Carmen Delgado, 53F\nDate: 2026-02-25\n\nDIAGNOSIS: Moderate protein-calorie malnutrition (ICD E44.0) in setting of pseudomyxoma peritonei with mucinous ascites.\n\nNUTRITIONAL STATUS:\n- Prealbumin trending: 8 (2026-02-01) → 10 (2026-02-15) → 12 (2026-03-01)\n- Albumin: 3.0 g/dL (below 3.5 target)\n- BMI: 22.1 (down from 25.4 at diagnosis — 13% weight loss in 3 months)\n- Prognostic Nutritional Index (PNI): 38.2 (<40 = significant surgical risk)\n\nCURRENT REGIMEN:\n- Ensure Plus TID (1050 kcal, 39g protein supplemental)\n- Iron supplementation (ferrous sulfate 325mg daily)\n- High-protein diet counseling (target 1.5 g/kg/day = 95g/day)\n\nPRE-HABILITATION PLAN:\n1. Continue Ensure Plus TID — consider adding Prosource protein supplement\n2. Target caloric intake 2200 kcal/day (30 kcal/kg)\n3. Immunonutrition: Impact Advanced Recovery x 5 days pre-op (arginine, omega-3, nucleotides)\n4. IV iron infusion if oral iron insufficient — discuss with hematology\n5. Post-op: anticipate TPN initiation POD 1-3, transition to enteral when ileus resolves\n\nRISK: PNI <40 associated with 2x increased complications post CRS-HIPEC. Improving prealbumin trend is encouraging but still suboptimal.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $anesthVisit->id,
            'note_type' => 'Pre-operative Anesthesia Assessment',
            'authored_at' => '2026-02-28',
            'author' => 'Dr. Alan Whitfield',
            'content' => "PRE-OPERATIVE ANESTHESIA ASSESSMENT\n\nASA Physical Status: III (severe systemic disease)\n\nPATIENT SUMMARY: 53F scheduled for CRS-HIPEC for pseudomyxoma peritonei (PCI 22). Significant comorbidities: CAD s/p recent DES (3.5 months), HTN, DM2, hypothyroidism, moderate malnutrition, MDD on sertraline.\n\nAIRWAY: Mallampati I, full cervical ROM, BMI 22.1 — standard induction.\n\nCARDIOVASCULAR: Recent DES to LAD (2025-11). LVEF 55% on echo. Aspirin continued, clopidogrel held 5 days. Cangrelor bridge planned intra-operatively.\n\nHIPEC-SPECIFIC CONSIDERATIONS:\n1. Hemodynamic: HIPEC causes vasodilation, hypotension, tachycardia. Aggressive volume resuscitation anticipated (8-15 L crystalloid + colloid). Arterial line and CVP monitoring mandatory.\n2. Metabolic: Hyperthermia to 39-40°C core temp during HIPEC phase. Active cooling of head/extremities. Metabolic acidosis common — serial ABGs Q30 min during HIPEC.\n3. Renal: Mitomycin C nephrotoxicity + hyperthermic renal stress. Maintain UOP >0.5 mL/kg/h. Consider mannitol during HIPEC.\n4. Coagulation: DIC risk with prolonged surgery + hyperthermia. TEG/ROTEM monitoring intra-operatively.\n\nSEROTONIN RISK: Sertraline 100mg daily. Avoid ondansetron (5-HT3 antagonist usually safe but monitor). NO tramadol, NO meperidine, NO methylene blue. Use hydromorphone for analgesia.\n\nANESTHETIC PLAN:\n- GA with thoracic epidural (T8-T10) for post-op analgesia\n- Arterial line, central venous catheter, Foley, OG tube\n- Cell saver requested\n- Type and crossmatch: 4 units PRBCs, 4 units FFP\n- Anticipated case duration: 10-14 hours\n- ICU bed reserved",
        ]);

        $this->addNote($patient, [
            'visit_id' => $surgOncVisit->id,
            'note_type' => 'Pathology Report',
            'authored_at' => '2026-02-18',
            'author' => 'Dr. Robert Chang',
            'content' => "PATHOLOGY REPORT — DIAGNOSTIC LAPAROSCOPY SPECIMENS\n\nDate of Procedure: 2026-02-14\nDate of Report: 2026-02-18\n\nSPECIMENS:\nA. Omental biopsy\nB. Pelvic peritoneal biopsy\nC. Right diaphragmatic peritoneal biopsy\nD. Mucinous ascites fluid (cytology)\n\nGROSS DESCRIPTION:\nA. Tan-yellow gelatinous tissue, 3.2 x 2.1 x 1.0 cm\nB. Gray-tan tissue with adherent mucin, 2.0 x 1.5 x 0.8 cm\nC. Gray-white tissue with surface mucin, 1.8 x 1.2 x 0.5 cm\nD. 250 mL viscous mucinous fluid\n\nMICROSCOPIC:\nA-C: Dissecting mucin pools with strips of low-grade mucinous epithelium. Cells demonstrate mild nuclear atypia, absent high-grade features. No signet ring cells. No lymphovascular or perineural invasion. Consistent with low-grade pseudomyxoma peritonei.\nD: Mucinous material with scattered clusters of bland mucinous epithelial cells. No high-grade atypia.\n\nIMMUNOHISTOCHEMISTRY:\n- CK20: Positive (diffuse)\n- CDX2: Positive (diffuse)\n- CK7: Negative\n- MUC2: Positive\n- Ki-67: <5%\n\nDIAGNOSIS:\n- Low-grade pseudomyxoma peritonei, consistent with disseminated peritoneal adenomucinosis (DPAM)\n- Origin: low-grade appendiceal mucinous neoplasm (LAMN)\n- PSOGI Classification: Low-grade with low-grade cytology\n\nCOMMENT: Low-grade histology is favorable for CRS-HIPEC outcomes. Ten-year survival with complete cytoreduction (CC-0) and HIPEC approaches 70-80% for low-grade PMP.",
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Hematology (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['Hemoglobin',     '718-7',   10.8, 'g/dL',  12.0, 16.0, 'L'],
            ['Platelet Count', '777-3',   224,  'K/uL',  150,  400,  null],
            ['WBC',            '6690-2',  11.2, 'K/uL',  4.5,  11.0, 'H'],
            ['INR',            '6301-6',  1.0,  null,    0.8,  1.2,  null],
            ['aPTT',           '3173-2',  28,   'sec',   25,   35,   null],
        ]);

        // Platelet Function (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['VerifyNow P2Y12', '62387-7', 68, 'PRU', null, 208, 'L'],
        ]);

        // Tumor Markers (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['CEA',    '2039-6',  14.2, 'ng/mL', null, 5.0,  'H'],
            ['CA 19-9', '24108-3', 48,  'U/mL',  null, 37,   'H'],
            ['CA-125', '10334-1', 82,   'U/mL',  null, 35,   'H'],
        ]);

        // Renal / Hepatic (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['Creatinine', '2160-0',  0.9,  'mg/dL', 0.6,  1.1,  null],
            ['eGFR',       '33914-3', 78,   'mL/min/1.73m2', 60, null, null],
            ['Albumin',    '1751-7',  3.0,  'g/dL',  3.5,  5.0,  'L'],
            ['Prealbumin', '14338-8', 12,   'mg/dL', 20,   40,   'L'],
            ['LDH',        '2532-0',  280,  'U/L',   140,  280,  null],
        ]);

        // Metabolic (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['HbA1c',           '4548-4',  7.4, '%',     null, 7.0,  'H'],
            ['Fasting Glucose', '1558-6',  148, 'mg/dL', 70,   100,  'H'],
            ['Magnesium',       '19123-9', 1.6, 'mg/dL', 1.7,  2.2,  'L'],
            ['Phosphorus',      '2777-1',  2.2, 'mg/dL', 2.5,  4.5,  'L'],
        ]);

        // Nutritional trending — Prealbumin at earlier timepoints
        $this->addLabPanel($patient, '2026-02-01', [
            ['Prealbumin', '14338-8', 8, 'mg/dL', 20, 40, 'L'],
        ]);

        $this->addLabPanel($patient, '2026-02-15', [
            ['Prealbumin', '14338-8', 10, 'mg/dL', 20, 40, 'L'],
        ]);

        // ── Observations (Risk Scores) ──────────────────────────
        $this->addObservation($patient, [
            'observation_name' => 'ASA Physical Status',
            'category' => 'clinical_score',
            'value_text' => 'III',
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Peritoneal Cancer Index (PCI)',
            'category' => 'clinical_score',
            'value_numeric' => 22,
            'observed_at' => '2026-02-18',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Lee Revised Cardiac Risk Index',
            'category' => 'clinical_score',
            'value_numeric' => 2,
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ACS NSQIP Predicted Complication Rate',
            'category' => 'clinical_score',
            'value_numeric' => 34,
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Prognostic Nutritional Index (PNI)',
            'category' => 'clinical_score',
            'value_numeric' => 38.2,
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'ECOG Performance Status',
            'category' => 'clinical_score',
            'value_numeric' => 1,
            'observed_at' => '2026-03-01',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $ctAbdPelvis = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-01',
            'modality' => 'CT',
            'body_part' => 'Abdomen',
            'description' => 'CT Abdomen/Pelvis with IV Contrast',
        ]);

        $ctChest = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-01',
            'modality' => 'CT',
            'body_part' => 'Chest',
            'description' => 'CT Chest without Contrast',
        ]);

        $petCt = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-05',
            'modality' => 'PET',
            'body_part' => 'Whole body',
            'description' => 'PET-CT (FDG)',
        ]);

        $this->addImagingMeasurement($petCt, [
            'measurement_type' => 'SUVmax (peritoneal deposits)',
            'value_numeric' => 3.2,
            'unit' => 'SUV',
        ]);

        $echo = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-10',
            'modality' => 'US',
            'body_part' => 'Heart',
            'description' => 'Transthoracic Echocardiogram',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_type' => 'LVEF',
            'value_numeric' => 55,
            'unit' => '%',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Pseudomyxoma peritonei',
            'era_start' => '2025-11-01',
            'era_end' => null,
            'occurrence_count' => 3,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Coronary artery disease',
            'era_start' => '2025-11-01',
            'era_end' => null,
            'occurrence_count' => 4,
        ]);
    }
}
