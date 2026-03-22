<?php

namespace Database\Seeders\DemoPatients;

class PreSurgicalPatient1_CABG
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-PS-001',
            'first_name' => 'Robert',
            'last_name' => 'Kowalski',
            'date_of_birth' => '1958-02-28',
            'sex' => 'Male',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-RK-88234');
        $this->addIdentifier($patient, 'hospital_mrn', 'RMC-112445', 'Regional Medical Center');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Severe aortic stenosis',
            'concept_code' => 'I35.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2025-09-01',
            'severity' => 'severe',
            'body_site' => 'Heart',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Three-vessel coronary artery disease, prior CABG',
            'concept_code' => 'I25.10',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-03-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Status post coronary artery bypass graft',
            'concept_code' => 'Z95.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2015-03-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Alcohol-related liver cirrhosis Child-Pugh B',
            'concept_code' => 'K70.30',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2020-01-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic kidney disease stage 3b',
            'concept_code' => 'N18.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2022-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Type 2 diabetes mellitus with hyperglycemia, insulin-dependent',
            'concept_code' => 'E11.65',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2012-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Morbid obesity, BMI 32.4',
            'concept_code' => 'E66.01',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2010-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic atrial fibrillation',
            'concept_code' => 'I48.2',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2019-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'COPD moderate, GOLD stage II',
            'concept_code' => 'J44.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2018-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Prior deep vein thrombosis, left lower extremity',
            'concept_code' => 'Z86.718',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2023-01-01',
            'resolved_date' => '2023-07-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Warfarin 4mg PO daily (held 5 days pre-op)',
            'concept_code' => '855332',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2019-03-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Spironolactone 50mg PO daily',
            'concept_code' => '198222',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2020-06-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Furosemide 40mg PO daily',
            'concept_code' => '197417',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2020-06-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Insulin glargine 28 units SQ nightly',
            'concept_code' => '261551',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2018-01-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Insulin lispro sliding scale with meals',
            'concept_code' => '86009',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2018-01-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Metoprolol succinate 100mg PO daily',
            'concept_code' => '866924',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2019-03-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Atorvastatin 40mg PO daily',
            'concept_code' => '259255',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2015-03-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Tiotropium 18mcg inhaled daily',
            'concept_code' => '284635',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2018-06-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Albuterol 2 puffs PRN',
            'concept_code' => '435',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2018-06-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Lactulose 30mL PO TID',
            'concept_code' => '6026',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2021-01-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Rifaximin 550mg PO BID',
            'concept_code' => '337394',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2021-01-01',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Coronary artery bypass graft x2 (SVG-LAD, SVG-RCA)',
            'concept_code' => '33533',
            'vocabulary' => 'CPT',
            'performed_at' => '2015-03-20',
            'specialty' => 'Cardiac Surgery',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Cardiac catheterization with coronary angiography',
            'concept_code' => '93458',
            'vocabulary' => 'CPT',
            'performed_at' => '2025-10-15',
            'specialty' => 'Interventional Cardiology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Pulmonary function tests (spirometry with DLCO)',
            'concept_code' => '94729',
            'vocabulary' => 'CPT',
            'performed_at' => '2025-12-10',
            'specialty' => 'Pulmonology',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $cardioInitial = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-09-15',
            'department' => 'Cardiology',
            'provider_name' => 'Dr. Sarah Chen',
            'reason' => 'Initial evaluation — new murmur, dyspnea on exertion, echocardiography',
        ]);

        $cathVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'visit_date' => '2025-10-15',
            'department' => 'Interventional Cardiology',
            'provider_name' => 'Dr. James Morton',
            'reason' => 'Cardiac catheterization — coronary angiography and hemodynamic assessment',
        ]);

        $heartTeam = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-11-05',
            'department' => 'Cardiac Surgery / Heart Team',
            'provider_name' => 'Dr. Raj Patel',
            'reason' => 'Heart Team conference — TAVR vs surgical AVR decision',
        ]);

        $hepatoVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-11-15',
            'department' => 'Hepatology',
            'provider_name' => 'Dr. Lisa Nguyen',
            'reason' => 'Hepatic optimization for cardiac surgery — cirrhosis management',
        ]);

        $nephroVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-12-01',
            'department' => 'Nephrology',
            'provider_name' => 'Dr. Ahmed Hassan',
            'reason' => 'Renal clearance for cardiac surgery — CKD 3b assessment',
        ]);

        $pulmoVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2025-12-10',
            'department' => 'Pulmonology',
            'provider_name' => 'Dr. Maria Santos',
            'reason' => 'Pulmonary function testing and COPD optimization pre-op',
        ]);

        $endoVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-10',
            'department' => 'Endocrinology',
            'provider_name' => 'Dr. Karen Liu',
            'reason' => 'Perioperative insulin protocol planning — HbA1c optimization',
        ]);

        $hemeVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-20',
            'department' => 'Hematology',
            'provider_name' => 'Dr. David Park',
            'reason' => 'Coagulopathy assessment — thrombocytopenia and INR management for surgery',
        ]);

        $anesthVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-15',
            'department' => 'Anesthesiology',
            'provider_name' => 'Dr. Michael Torres',
            'reason' => 'Pre-operative anesthesia assessment — ASA IV, multi-organ risk',
        ]);

        $consentVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-03-01',
            'department' => 'Cardiac Surgery',
            'provider_name' => 'Dr. Raj Patel',
            'reason' => 'Surgical consent — redo CABG + AVR, risk discussion',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $heartTeam->id,
            'note_type' => 'Heart Team Decision Note',
            'note_date' => '2025-11-05',
            'author' => 'Dr. Raj Patel',
            'content' => "HEART TEAM CONFERENCE — DECISION NOTE\n\nPatient: Robert Kowalski, 67M\nDate: 2025-11-05\n\nPRESENTATION: Severe aortic stenosis (AVA 0.7 cm², mean gradient 48 mmHg) with three-vessel CAD and prior CABG x2 (2015). SVG-LAD occluded, native LAD 95%, LCx 80%, RCA 90%. LVEF 40%, moderate MR.\n\nDISCUSSION:\n- TAVR considered but REJECTED: concurrent three-vessel CAD requiring revascularization precludes transcatheter approach. Occluded SVG-LAD mandates redo surgical grafting.\n- Surgical risk elevated: STS mortality 8.2%, EuroSCORE II 9.6%. Major risk factors include cirrhosis (Child-Pugh B, MELD 14), CKD 3b (eGFR 38), thrombocytopenia (Plt 78), COPD (FEV1 58%).\n- Porcelain aorta identified on CT — will require modified cannulation strategy. RV adherent to sternum — femoral cannulation planned.\n\nDECISION: Proceed with redo CABG + surgical AVR after 4-month multidisciplinary optimization period.\n\nPLAN:\n1. Hepatology optimization — lactulose/rifaximin titration, MELD trending\n2. Nephrology — pre-op hydration protocol, hold nephrotoxins\n3. Pulmonology — PFTs, bronchodilator optimization\n4. Endocrinology — perioperative insulin protocol\n5. Hematology — platelet optimization, warfarin bridging strategy\n6. Target surgery date: March 2026",
        ]);

        $this->addNote($patient, [
            'visit_id' => $hepatoVisit->id,
            'note_type' => 'Hepatology Optimization Note',
            'note_date' => '2025-11-15',
            'author' => 'Dr. Lisa Nguyen',
            'content' => "HEPATOLOGY PRE-SURGICAL OPTIMIZATION\n\nDiagnosis: Alcohol-related liver cirrhosis, Child-Pugh B (8 points)\nMELD Score: 14 (Cr 1.6, Bili 1.8, INR 1.4)\n\nCurrent Status: Compensated cirrhosis with portal hypertension. Splenomegaly (16 cm) with hypersplenism contributing to thrombocytopenia. Small volume ascites managed with diuretics. No recent variceal bleeding. Hepatic encephalopathy controlled on lactulose/rifaximin.\n\nOPTIMIZATION PLAN:\n1. Continue lactulose 30 mL TID — titrate to 3-4 BMs/day\n2. Continue rifaximin 550 mg BID\n3. Spironolactone 50 mg daily / Furosemide 40 mg daily — monitor electrolytes\n4. Trend MELD monthly — proceed with surgery if MELD remains <20\n5. Pre-op albumin infusion protocol to target albumin >3.0\n6. Avoid hepatotoxic medications peri-operatively\n7. Platelet transfusion threshold: <50K for surgery\n\nRISK: Cardiac surgery in Child-Pugh B cirrhosis carries 30-50% mortality in literature. Patient counseled extensively. Benefit of AVR + CABG outweighs medical management given progressive symptoms.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $nephroVisit->id,
            'note_type' => 'Nephrology Clearance Note',
            'note_date' => '2025-12-01',
            'author' => 'Dr. Ahmed Hassan',
            'content' => "NEPHROLOGY PRE-OPERATIVE CLEARANCE\n\nDiagnosis: CKD stage 3b (eGFR 38 mL/min), likely multifactorial — diabetic nephropathy and cardiorenal syndrome.\n\nCurrent Labs: Cr 1.7, BUN 30, K 4.9, Cystatin C 1.6\n\nRISK ASSESSMENT:\n- High risk for AKI with cardiopulmonary bypass — estimated 40-50% risk\n- Possible need for temporary RRT post-operatively (15-20% risk)\n- Contrast exposure from recent catheterization — adequate washout period observed\n\nRECOMMENDATIONS:\n1. Pre-operative IV hydration with isotonic bicarbonate\n2. Hold metformin (not currently on), hold NSAIDs\n3. Minimize bypass time — discuss with surgical team\n4. Post-op nephrology follow-up for AKI monitoring\n5. Potassium monitoring Q6H post-operatively\n6. CLEARED for surgery with above precautions",
        ]);

        $this->addNote($patient, [
            'visit_id' => $pulmoVisit->id,
            'note_type' => 'Pulmonology Clearance Note',
            'note_date' => '2025-12-10',
            'author' => 'Dr. Maria Santos',
            'content' => "PULMONOLOGY PRE-OPERATIVE CLEARANCE\n\nDiagnosis: COPD, GOLD stage II (moderate)\n\nPFTs (2025-12-10):\n- FEV1: 1.68 L (58% predicted)\n- FVC: 2.94 L (74% predicted)\n- FEV1/FVC: 0.57\n- DLCO: 52% predicted\n\nAssessment: Moderate obstructive ventilatory defect with reduced diffusion capacity. Reduced DLCO likely multifactorial — COPD, CHF, possible hepatopulmonary contribution.\n\nOPTIMIZATION:\n1. Continue tiotropium 18 mcg daily\n2. Albuterol PRN — use 30 min before activity\n3. Smoking cessation confirmed — quit 2020\n4. Incentive spirometry education — begin pre-operatively\n5. Post-op: early extubation protocol preferred, ICU respiratory therapy\n\nCLEARED for surgery — moderate risk for prolonged ventilation. FEV1 >40% predicted is acceptable threshold for cardiac surgery.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $anesthVisit->id,
            'note_type' => 'Pre-operative Anesthesia Assessment',
            'note_date' => '2026-02-15',
            'author' => 'Dr. Michael Torres',
            'content' => "PRE-OPERATIVE ANESTHESIA ASSESSMENT\n\nASA Physical Status: IV (severe systemic disease — constant threat to life)\n\nPATIENT SUMMARY: 67M scheduled for redo CABG + AVR. Major comorbidities: cirrhosis Child-Pugh B (MELD 17), CKD 3b, chronic AFib on warfarin, COPD GOLD II, DM2 on insulin, obesity (BMI 32.4).\n\nAIRWAY: Mallampati II, full cervical ROM, BMI 32.4 — standard induction anticipated.\n\nCARDIOVASCULAR: LVEF 40%, severe AS (AVA 0.7), moderate MR, AFib. TEE planned intra-operatively. Avoid hypotension — fixed cardiac output with severe AS.\n\nHEPATIC: Child-Pugh B. Drug metabolism significantly altered. Avoid halothane. Reduce doses of hepatically cleared medications. Coagulopathy baseline — FFP and platelet availability confirmed with blood bank.\n\nRENAL: eGFR 38. Avoid nephrotoxins. Minimize bypass time.\n\nPULMONARY: FEV1 58%, DLCO 52%. Lung-protective ventilation. Early extubation if stable.\n\nBLOOD PRODUCTS: Type and crossmatch 6 units PRBCs, 4 units FFP, 2 units platelets, cryoprecipitate on standby. Cell saver requested.\n\nPLAN: GA with arterial line, PA catheter, TEE. Femoral cannulation planned (redo sternotomy with RV adhesion). Discuss intraoperative TEE findings for valve sizing.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $consentVisit->id,
            'note_type' => 'Surgical Consent Note',
            'note_date' => '2026-03-01',
            'author' => 'Dr. Raj Patel',
            'content' => "INFORMED CONSENT — REDO CABG + AORTIC VALVE REPLACEMENT\n\nProcedure: Redo coronary artery bypass grafting (LIMA-LAD, SVG-OM, SVG-RCA) with aortic valve replacement (tissue prosthesis).\n\nIndication: Severe symptomatic aortic stenosis with three-vessel CAD and failed prior SVG-LAD graft.\n\nRISKS DISCUSSED:\n- Operative mortality: 8-10% (STS predicted 8.2%, EuroSCORE II 9.6%)\n- Stroke: 3-5%\n- Renal failure requiring dialysis: 15-20%\n- Prolonged ventilation: 20-30%\n- Deep sternal wound infection: 3-5% (redo, obesity, DM)\n- Hepatic decompensation: 15-25% (Child-Pugh B)\n- Bleeding requiring re-exploration: 10-15% (coagulopathy, thrombocytopenia)\n\nALTERNATIVES: Medical management (progressive decline expected), TAVR not suitable (concurrent CAD requiring CABG).\n\nPATIENT UNDERSTANDING: Mr. Kowalski demonstrates understanding of risks. He has discussed with family. He accepts the risks given progressive symptoms (NYHA III dyspnea, angina).\n\nConsent signed: 2026-03-01\nWitness: RN Jennifer Adams",
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // MELD trending — Month 1 (2025-10-01)
        $this->addLabPanel($patient, '2025-10-01', [
            ['Creatinine',       '2160-0',  1.6,  'mg/dL', 0.7,  1.3,  'H'],
            ['Total Bilirubin',  '1975-2',  1.8,  'mg/dL', 0.1,  1.2,  'H'],
            ['INR',              '6301-6',  1.4,  null,    0.8,  1.2,  'H'],
        ]);

        // MELD trending — Month 3 (2025-12-01)
        $this->addLabPanel($patient, '2025-12-01', [
            ['Creatinine',       '2160-0',  1.7,  'mg/dL', 0.7,  1.3,  'H'],
            ['Total Bilirubin',  '1975-2',  2.0,  'mg/dL', 0.1,  1.2,  'H'],
            ['INR',              '6301-6',  1.5,  null,    0.8,  1.2,  'H'],
        ]);

        // Hematology / Coagulation (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['Hemoglobin',       '718-7',   10.2, 'g/dL',  13.5, 17.5, 'L'],
            ['Platelet Count',   '777-3',   78,   'K/uL',  150,  400,  'L'],
            ['INR',              '6301-6',  1.6,  null,    0.8,  1.2,  'H'],
            ['Prothrombin Time', '5902-2',  19.4, 'sec',   11.0, 13.5, 'H'],
            ['aPTT',             '3173-2',  38,   'sec',   25,   35,   'H'],
            ['Fibrinogen',       '3255-7',  148,  'mg/dL', 200,  400,  'L'],
        ]);

        // Renal Panel (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['Creatinine',       '2160-0',  1.9,  'mg/dL', 0.7,  1.3,  'H'],
            ['eGFR',             '33914-3', 38,   'mL/min/1.73m2', 60, null, 'L'],
            ['BUN',              '3094-0',  34,   'mg/dL', 7,    20,   'H'],
            ['Potassium',        '2823-3',  5.1,  'mEq/L', 3.5,  5.0,  'H'],
            ['Cystatin C',       '33863-2', 1.8,  'mg/L',  0.6,  1.0,  'H'],
        ]);

        // Hepatic Panel (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['Albumin',          '1751-7',  2.8,  'g/dL',  3.5,  5.0,  'L'],
            ['Total Bilirubin',  '1975-2',  2.4,  'mg/dL', 0.1,  1.2,  'H'],
            ['AST',              '1920-8',  68,   'U/L',   10,   40,   'H'],
            ['ALT',              '1742-6',  52,   'U/L',   7,    56,   null],
            ['Ammonia',          '1841-2',  62,   'umol/L', 15,  45,   'H'],
        ]);

        // Cardiac Biomarkers (2026-03-01)
        $this->addLabPanel($patient, '2026-03-01', [
            ['NT-proBNP',        '33762-6', 2840, 'pg/mL', null, 300,  'H'],
            ['hs-Troponin I',    '89579-7', 42,   'ng/L',  null, 14,   'H'],
            ['HbA1c',            '4548-4',  8.1,  '%',     null, 7.0,  'H'],
        ]);

        // ── Observations (Risk Scores) ──────────────────────────
        $this->addObservation($patient, [
            'observation_name' => 'ASA Physical Status',
            'category' => 'clinical_score',
            'value_text' => 'IV',
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'STS Predicted Mortality',
            'category' => 'clinical_score',
            'value_numeric' => 8.2,
            'unit' => '%',
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'EuroSCORE II',
            'category' => 'clinical_score',
            'value_numeric' => 9.6,
            'unit' => '%',
            'observed_at' => '2026-03-01',
        ]);

        // MELD trending
        $this->addObservation($patient, [
            'observation_name' => 'MELD Score',
            'category' => 'clinical_score',
            'value_numeric' => 14,
            'observed_at' => '2025-10-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'MELD Score',
            'category' => 'clinical_score',
            'value_numeric' => 15,
            'observed_at' => '2025-12-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'MELD Score',
            'category' => 'clinical_score',
            'value_numeric' => 17,
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Child-Pugh Score',
            'category' => 'clinical_score',
            'value_text' => 'B (8 points)',
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Lee Revised Cardiac Risk Index',
            'category' => 'clinical_score',
            'value_numeric' => 4,
            'unit' => 'points',
            'observed_at' => '2026-03-01',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'CHA2DS2-VASc Score',
            'category' => 'clinical_score',
            'value_numeric' => 5,
            'observed_at' => '2026-03-01',
        ]);

        // PFT Observations
        $this->addObservation($patient, [
            'observation_name' => 'FEV1',
            'category' => 'pulmonary_function',
            'value_numeric' => 1.68,
            'unit' => 'L',
            'value_text' => '58% predicted',
            'observed_at' => '2025-12-10',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'FVC',
            'category' => 'pulmonary_function',
            'value_numeric' => 2.94,
            'unit' => 'L',
            'value_text' => '74% predicted',
            'observed_at' => '2025-12-10',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'FEV1/FVC Ratio',
            'category' => 'pulmonary_function',
            'value_numeric' => 0.57,
            'observed_at' => '2025-12-10',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'DLCO',
            'category' => 'pulmonary_function',
            'value_text' => '52% predicted',
            'observed_at' => '2025-12-10',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $echo = $this->addImagingStudy($patient, [
            'study_date' => '2025-09-15',
            'modality' => 'US',
            'body_site' => 'Heart',
            'description' => 'Transthoracic Echocardiogram',
            'indication' => 'New systolic murmur, dyspnea on exertion',
            'findings' => 'Severe aortic stenosis: AVA 0.7 cm², mean gradient 48 mmHg, peak gradient 78 mmHg. LVEF 40% with global hypokinesis. Moderate mitral regurgitation. RVSP 48 mmHg (moderate pulmonary hypertension). Concentric LVH. LA dilation.',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_name' => 'Aortic Valve Area',
            'value_numeric' => 0.7,
            'unit' => 'cm²',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_name' => 'Mean Aortic Gradient',
            'value_numeric' => 48,
            'unit' => 'mmHg',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_name' => 'LVEF',
            'value_numeric' => 40,
            'unit' => '%',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_name' => 'RVSP',
            'value_numeric' => 48,
            'unit' => 'mmHg',
        ]);

        $angio = $this->addImagingStudy($patient, [
            'study_date' => '2025-10-15',
            'modality' => 'XR',
            'body_site' => 'Heart',
            'description' => 'Coronary Angiography',
            'indication' => 'Known CAD, prior CABG, new aortic stenosis — assess graft patency and native vessels',
            'findings' => 'SVG-LAD: occluded (chronic total occlusion). SVG-RCA: patent with 50% proximal stenosis. Native LAD: 95% proximal stenosis. Left circumflex: 80% mid-vessel stenosis. RCA: 90% proximal stenosis. LMCA: 30% ostial plaque. Aortic root calcification noted.',
        ]);

        $ctChest = $this->addImagingStudy($patient, [
            'study_date' => '2025-11-01',
            'modality' => 'CT',
            'body_site' => 'Chest',
            'description' => 'CT Chest with Contrast — Redo Sternotomy Planning',
            'indication' => 'Pre-operative planning for redo cardiac surgery',
            'findings' => 'Right ventricle adherent to posterior sternum — high risk for injury during redo sternotomy. Porcelain aorta: extensive circumferential calcification of ascending aorta — will require modified cannulation and cross-clamp strategy. Patent SVG-RCA courses directly posterior to sternum. Bilateral pleural effusions, small. No pulmonary embolism.',
        ]);

        $abdUS = $this->addImagingStudy($patient, [
            'study_date' => '2025-11-15',
            'modality' => 'US',
            'body_site' => 'Abdomen',
            'description' => 'Abdominal Ultrasound with Doppler',
            'indication' => 'Cirrhosis surveillance, pre-operative hepatic assessment',
            'findings' => 'Nodular liver contour consistent with cirrhosis. Splenomegaly measuring 16 cm (upper limit 12 cm). Small volume ascites in Morison pouch and pelvis. Patent portal vein with hepatopetal flow, velocity 18 cm/s (borderline low). No focal hepatic lesions. Common bile duct normal caliber.',
        ]);

        // ── Condition Eras ───────────────────────────────────────
        $this->addConditionEra($patient, [
            'condition_name' => 'Coronary artery disease',
            'era_start' => '2015-03-01',
            'era_end' => null,
            'occurrence_count' => 20,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Alcohol-related liver cirrhosis',
            'era_start' => '2020-01-01',
            'era_end' => null,
            'occurrence_count' => 10,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Chronic kidney disease',
            'era_start' => '2022-01-01',
            'era_end' => null,
            'occurrence_count' => 8,
        ]);
    }
}
