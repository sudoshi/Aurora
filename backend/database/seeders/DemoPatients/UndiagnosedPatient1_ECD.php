<?php

namespace Database\Seeders\DemoPatients;

class UndiagnosedPatient1_ECD
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-UD-001',
            'first_name' => 'Marcus',
            'last_name' => 'Thompson',
            'date_of_birth' => '1970-07-22',
            'sex' => 'Male',
            'race' => 'Black or African American',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-MT-88123');
        $this->addIdentifier($patient, 'hospital_mrn', 'AMC-992341', 'Academic Medical Center');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Erdheim-Chester disease',
            'concept_code' => 'C96.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2025-12-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Central diabetes insipidus',
            'concept_code' => 'E23.2',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-02-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Retroperitoneal fibrosis with obstructive uropathy',
            'concept_code' => 'N13.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-08-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Pericardial effusion',
            'concept_code' => 'I31.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-02-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Bilateral osteosclerosis of femurs and tibiae',
            'concept_code' => 'M85.80',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-06-01',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Secondary hypogonadism',
            'concept_code' => 'E29.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-02-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hyperprolactinemia',
            'concept_code' => 'E22.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-02-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic kidney disease stage 3b',
            'concept_code' => 'N18.32',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-08-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Interstitial lung disease',
            'concept_code' => 'J84.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Periorbital xanthelasma',
            'concept_code' => 'H02.60',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-02-01',
            'body_site' => 'Periorbital',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Vemurafenib',
            'concept_code' => '1147220',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 960,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2025-12-15',
            'status' => 'active',
            'prescriber' => 'Dr. Karen Liu',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Desmopressin',
            'concept_code' => '3247',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 0.1,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2024-03-01',
            'status' => 'active',
            'prescriber' => 'Dr. Rachel Patel',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Testosterone cypionate',
            'concept_code' => '10351',
            'vocabulary' => 'RxNorm',
            'route' => 'IM',
            'dose_value' => 200,
            'dose_unit' => 'mg',
            'frequency' => 'every 2 weeks',
            'start_date' => '2024-03-15',
            'status' => 'active',
            'prescriber' => 'Dr. Rachel Patel',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Furosemide',
            'concept_code' => '4603',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 40,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2024-09-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Omeprazole',
            'concept_code' => '7646',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 20,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2025-12-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Ciprofloxacin',
            'concept_code' => '2551',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 500,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2023-10-01',
            'end_date' => '2023-10-21',
            'status' => 'completed',
            'prescriber' => 'Dr. Alan Foster',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Vancomycin',
            'concept_code' => '11124',
            'vocabulary' => 'RxNorm',
            'route' => 'IV',
            'dose_value' => 1000,
            'dose_unit' => 'mg',
            'frequency' => 'every 12 hours',
            'start_date' => '2023-10-22',
            'end_date' => '2023-11-22',
            'status' => 'completed',
            'prescriber' => 'Dr. Alan Foster',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Bone biopsy right femur',
            'concept_code' => '20245',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2023-09-15',
            'performer' => 'Orthopedic Oncology',
            'body_site' => 'Right femur',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Bilateral ureteral stent placement',
            'concept_code' => '50605',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2024-09-01',
            'performer' => 'Urology',
            'laterality' => 'bilateral',
            'body_site' => 'Ureters',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Pericardiocentesis',
            'concept_code' => '33010',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2025-04-15',
            'performer' => 'Cardiology',
            'body_site' => 'Pericardium',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Water deprivation test',
            'concept_code' => '80432',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2024-02-15',
            'performer' => 'Endocrinology',
        ]);

        // ── Visits (diagnostic odyssey ~2.5 years) ──────────────
        // Month 0: PCP
        $visitPcp0 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-06-15',
            'discharge_date' => '2023-06-15',
            'attending_provider' => 'Dr. William Grant',
            'department' => 'Primary Care',
        ]);

        // Month 1: Orthopedic Oncology
        $visitOrtho1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-07-15',
            'discharge_date' => '2023-07-15',
            'attending_provider' => 'Dr. Steven Park',
            'department' => 'Orthopedic Oncology',
        ]);

        // Month 1b: Bone biopsy
        $visitBiopsy = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-09-15',
            'discharge_date' => '2023-09-15',
            'attending_provider' => 'Dr. Steven Park',
            'department' => 'Orthopedic Oncology',
        ]);

        // Month 4: Infectious Disease
        $visitId4 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-10-15',
            'discharge_date' => '2023-10-15',
            'attending_provider' => 'Dr. Alan Foster',
            'department' => 'Infectious Disease',
        ]);

        // Month 6: Rheumatology
        $visitRheum6 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-02-01',
            'discharge_date' => '2024-02-01',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Rheumatology',
        ]);

        // Month 8: Endocrinology
        $visitEndo8 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-02-15',
            'discharge_date' => '2024-02-15',
            'attending_provider' => 'Dr. Rachel Patel',
            'department' => 'Endocrinology',
        ]);

        // Month 10: Pulmonology
        $visitPulm10 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-06-15',
            'discharge_date' => '2024-06-15',
            'attending_provider' => 'Dr. David Chen',
            'department' => 'Pulmonology',
        ]);

        // Month 14: Nephrology
        $visitNephro14 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-08-15',
            'discharge_date' => '2024-08-15',
            'attending_provider' => 'Dr. Nina Vasquez',
            'department' => 'Nephrology',
        ]);

        // Month 14b: Ureteral stent placement
        $visitStent = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-09-01',
            'discharge_date' => '2024-09-03',
            'attending_provider' => 'Dr. Brian Walsh',
            'department' => 'Urology',
        ]);

        // Month 18: Cardiology
        $visitCardio18 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2025-02-15',
            'discharge_date' => '2025-02-15',
            'attending_provider' => 'Dr. James Henderson',
            'department' => 'Cardiology',
        ]);

        // Month 18b: Cardiac MRI
        $visitCardioMri = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2025-02-20',
            'discharge_date' => '2025-02-20',
            'attending_provider' => 'Dr. James Henderson',
            'department' => 'Cardiac Imaging',
        ]);

        // Month 20: Pericardiocentesis
        $visitPericardiocentesis = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2025-04-15',
            'discharge_date' => '2025-04-17',
            'attending_provider' => 'Dr. James Henderson',
            'department' => 'Cardiology',
        ]);

        // Month 22: Hematology/Oncology at academic center
        $visitHemeOnc22 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Academic Medical Center',
            'admission_date' => '2025-04-20',
            'discharge_date' => '2025-04-20',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Hematology/Oncology',
        ]);

        // Month 22b: PET-CT
        $visitPetCt = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Academic Medical Center',
            'admission_date' => '2025-04-25',
            'discharge_date' => '2025-04-25',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Nuclear Medicine',
        ]);

        // Month 24: Multidisciplinary rare disease conference → DIAGNOSIS
        $visitMdc24 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Academic Medical Center — Rare Disease Center',
            'admission_date' => '2025-12-15',
            'discharge_date' => '2025-12-15',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Multidisciplinary Rare Disease Conference',
        ]);

        // Month 24b: Treatment initiation follow-up
        $visitTreatment = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Academic Medical Center',
            'admission_date' => '2025-12-20',
            'discharge_date' => '2025-12-20',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Hematology/Oncology',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $visitPcp0->id,
            'note_type' => 'progress_note',
            'title' => 'PCP Initial Visit — Bilateral Leg Pain',
            'content' => '53-year-old male presents with 3-month history of progressive bilateral leg pain, primarily in distal femurs and proximal tibiae. Pain is deep, aching, worse with weight-bearing. Associated fatigue and unintentional 15-pound weight loss over 6 months. ESR markedly elevated at 68 mm/hr (0-20), CRP 3.8 mg/dL (<0.5), ALP 185 U/L (44-147). No fevers, night sweats, or lymphadenopathy. Initial differential includes metastatic bone disease vs Paget disease vs multiple myeloma. Referred to orthopedic oncology for urgent evaluation.',
            'author' => 'Dr. William Grant',
            'authored_at' => '2023-06-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitBiopsy->id,
            'note_type' => 'procedure_note',
            'title' => 'Bone Biopsy Pathology — Right Femur',
            'content' => 'PATHOLOGY REPORT: Right femur diaphyseal biopsy. GROSS: Two cores of dense sclerotic bone with tan-yellow tissue. MICROSCOPIC: Sheets of foamy histiocytes within a dense fibrotic stroma. Touton giant cells identified focally. No evidence of malignancy or Langerhans cell histiocytosis. No granulomas. No organisms on special stains (GMS, AFB negative). IMMUNOHISTOCHEMISTRY: Not performed per standard panel. DIAGNOSIS: Foamy histiocytic infiltrate in fibrotic stroma, nonspecific. COMMENT: Findings are nonspecific. Clinical correlation recommended. Consider infectious or inflammatory etiology.',
            'author' => 'Dr. Steven Park',
            'authored_at' => '2023-09-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitId4->id,
            'note_type' => 'consult_note',
            'title' => 'Infectious Disease Consult — Suspected Osteomyelitis',
            'content' => 'Consulted for presumed chronic osteomyelitis given bone biopsy showing foamy histiocytes and inflammatory infiltrate. Empiric ciprofloxacin 500mg BID started 2023-10-01 for 3 weeks — no clinical improvement. Escalated to IV vancomycin x4 weeks — no improvement. All blood cultures negative x3 sets. Quantiferon-TB Gold negative. Brucella serology negative. Fungal cultures negative. After 7 weeks of failed empiric antibiotics, infectious etiology is unlikely. Recommended further workup for non-infectious inflammatory process.',
            'author' => 'Dr. Alan Foster',
            'authored_at' => '2023-11-25',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheum6->id,
            'note_type' => 'consult_note',
            'title' => 'Rheumatology Workup — Systemic Inflammatory Process',
            'content' => 'Patient now presents with additional findings: bilateral periorbital yellowish papules (xanthelasma-like), polyuria/polydipsia, and persistent bone pain. Comprehensive autoimmune workup: ANA negative, anti-dsDNA negative, RF <10, anti-CCP <20, ANCA panel negative (MPO and PR3), IgG4 42 mg/dL (normal 4-86). C3 and C4 normal. No evidence of vasculitis, IgG4-related disease, or connective tissue disease. The combination of bone lesions with foamy histiocytes, periorbital lesions, and polyuria raises concern for a xanthogranulomatous process of unclear etiology. Referred to endocrinology for diabetes insipidus workup.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2024-02-01',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitEndo8->id,
            'note_type' => 'consult_note',
            'title' => 'Endocrinology — Central Diabetes Insipidus Diagnosis',
            'content' => 'Water deprivation test confirms central diabetes insipidus: urine osmolality failed to concentrate (180 mOsm/kg) during deprivation, with appropriate response to desmopressin (450 mOsm/kg). Serum Na elevated at 148 mEq/L. Brain MRI shows thickened pituitary stalk (4.2mm, normal <3mm) with absent posterior pituitary bright spot. Additional findings: prolactin elevated at 42 ng/mL (stalk effect), testosterone markedly low at 180 ng/dL with low LH 1.2 (secondary hypogonadism). Pituitary stalk infiltration of unknown etiology. Started desmopressin 0.1mg PO BID. Differential for stalk lesion includes sarcoidosis, Langerhans cell histiocytosis, germinoma, lymphoma.',
            'author' => 'Dr. Rachel Patel',
            'authored_at' => '2024-02-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNephro14->id,
            'note_type' => 'consult_note',
            'title' => 'Nephrology — Hairy Kidney and Coated Aorta',
            'content' => 'Creatinine rising to 1.8 mg/dL (baseline 0.9 six months ago), eGFR 42. CT abdomen/pelvis with contrast reveals bilateral perinephric soft tissue infiltration creating a "hairy kidney" appearance. Circumferential periaortic soft tissue cuffing ("coated aorta" sign). Bilateral hydronephrosis secondary to retroperitoneal fibrosis causing ureteral obstruction. Working diagnosis: idiopathic retroperitoneal fibrosis (Ormond disease). Bilateral ureteral stents placed urgently. Started furosemide 40mg daily. Note: the combination of retroperitoneal fibrosis, bone disease, and pituitary involvement is unusual for typical Ormond disease. Consider systemic histiocytic disorder.',
            'author' => 'Dr. Nina Vasquez',
            'authored_at' => '2024-08-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitCardio18->id,
            'note_type' => 'consult_note',
            'title' => 'Cardiology — Pericardial Effusion and RA Infiltration',
            'content' => 'Echocardiogram reveals moderate pericardial effusion measuring 1.8cm circumferentially without tamponade physiology. Right atrial wall appears thickened and infiltrated. BNP elevated at 380 pg/mL. Cardiac MRI demonstrates pericardial enhancement with gadolinium, right atrial infiltration with delayed enhancement, and small bilateral pleural effusions. Differential: constrictive pericarditis vs infiltrative cardiomyopathy vs pericardial metastatic disease. In context of multisystem disease (bones, kidneys, pituitary, pericardium), this patient needs referral to an academic center for comprehensive evaluation. Pericardiocentesis planned if effusion increases.',
            'author' => 'Dr. James Henderson',
            'authored_at' => '2025-02-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitHemeOnc22->id,
            'note_type' => 'consult_note',
            'title' => 'Hematology/Oncology — Re-stained Biopsy and BRAF V600E Diagnosis',
            'content' => 'ACADEMIC CENTER REVIEW: Re-examination of original 2023 right femur biopsy with extended immunohistochemistry panel. RESULTS: CD68 positive (strong, diffuse), CD163 positive, Factor XIIIa positive, CD1a NEGATIVE, S100 NEGATIVE, Langerin NEGATIVE. This immunophenotype (CD68+/CD163+/CD1a-/S100-) is pathognomonic for non-Langerhans cell histiocytosis, specifically consistent with Erdheim-Chester disease. BRAF V600E mutation testing on cfDNA (liquid biopsy): DETECTED, VAF 2.8%. Tissue confirmation: BRAF p.V600E (c.1799T>A) confirmed on re-stained biopsy tissue by allele-specific PCR. PET-CT ordered for disease extent mapping. DIAGNOSIS: Erdheim-Chester disease, BRAF V600E mutated.',
            'author' => 'Dr. Karen Liu',
            'authored_at' => '2025-04-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitPetCt->id,
            'note_type' => 'imaging_report',
            'title' => 'PET-CT Interpretation — Multifocal Disease Mapping',
            'content' => 'PET-CT WHOLE BODY: Multifocal FDG-avid disease consistent with Erdheim-Chester disease. (1) Bilateral femoral and tibial diaphyseal uptake, SUVmax 4.2, with epiphyseal sparing — classic ECD pattern. (2) Circumferential periaortic FDG uptake ("coated aorta"). (3) Bilateral perinephric FDG uptake ("hairy kidney"). (4) Pericardial FDG uptake with right atrial wall thickening. (5) Retroperitoneal soft tissue FDG uptake. (6) Thickened pituitary stalk with mild FDG uptake. No pulmonary parenchymal uptake beyond known interstitial disease. No CNS parenchymal involvement. Disease burden is multisystemic but without CNS parenchymal disease, which is favorable for vemurafenib response.',
            'author' => 'Dr. Karen Liu',
            'authored_at' => '2025-04-25',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitMdc24->id,
            'note_type' => 'progress_note',
            'title' => 'Multidisciplinary Rare Disease Conference — Final Diagnosis and Treatment Plan',
            'content' => 'MULTIDISCIPLINARY CONFERENCE SUMMARY: After 2.5-year diagnostic odyssey spanning 8 specialties, patient is confirmed to have Erdheim-Chester disease (ECD), a rare non-Langerhans cell histiocytosis. BRAF V600E mutated. KEY LEARNING POINT: The original bone biopsy in 2023 showed foamy histiocytes — the hallmark of ECD — but was read as "nonspecific" because CD68/CD1a/S100 immunostains were not ordered. If the standard histiocytosis IHC panel had been performed at month 3, diagnosis could have been made 19 months earlier. TREATMENT PLAN: Vemurafenib 960mg PO BID (BRAF-targeted therapy, FDA-approved for ECD). Omeprazole for GI protection. Continue desmopressin, testosterone replacement. Ureteral stents to remain. Response assessment PET-CT in 3 months.',
            'author' => 'Dr. Karen Liu',
            'authored_at' => '2025-12-15',
        ]);

        // ── Lab Panels ──────────────────────────────────────────
        // Month 0 (2023-06-15): Initial PCP labs
        $this->addLabPanel($patient, '2023-06-15', [
            ['ESR', '30341-2', 68, 'mm/hr', 0, 20, 'H'],
            ['CRP', '1988-5', 3.8, 'mg/dL', null, 0.5, 'H'],
            ['Alkaline phosphatase', '6768-6', 185, 'U/L', 44, 147, 'H'],
            ['PSA', '2857-1', 1.2, 'ng/mL', null, 4.0, null],
            ['WBC', '6690-2', 7.2, 'x10^3/uL', 4.5, 11.0, null],
            ['Hemoglobin', '718-7', 13.5, 'g/dL', 13.0, 17.5, null],
            ['Platelets', '777-3', 210, 'x10^3/uL', 150, 400, null],
        ]);

        // Month 4 (2023-10-15): ID workup
        $this->addLabPanel($patient, '2023-10-15', [
            ['ESR', '30341-2', 74, 'mm/hr', 0, 20, 'H'],
            ['CRP', '1988-5', 4.1, 'mg/dL', null, 0.5, 'H'],
        ]);
        // Text-only results for ID workup
        $this->addMeasurement($patient, [
            'measurement_name' => 'Blood cultures',
            'concept_code' => '600-7',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative — no growth at 5 days (3 sets)',
            'unit' => null,
            'measured_at' => '2023-10-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Quantiferon-TB Gold',
            'concept_code' => '71774-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2023-10-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Brucella serology',
            'concept_code' => '5073-7',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2023-10-15',
        ]);

        // Month 6 (2024-02-01): Rheumatology autoimmune panel
        $this->addMeasurement($patient, [
            'measurement_name' => 'ANA',
            'concept_code' => '8061-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2024-02-01',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Anti-dsDNA',
            'concept_code' => '11235-9',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2024-02-01',
        ]);
        $this->addLabPanel($patient, '2024-02-01', [
            ['Rheumatoid factor', '11572-5', 8, 'IU/mL', null, 14, null],
            ['Anti-CCP', '53027-9', 15, 'U/mL', null, 20, null],
            ['Complement C3', '4485-9', 118, 'mg/dL', 90, 180, null],
            ['Complement C4', '4498-2', 28, 'mg/dL', 10, 40, null],
            ['IgG4', '19113-0', 42, 'mg/dL', 4, 86, null],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'ANCA panel',
            'concept_code' => '21419-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative (MPO and PR3)',
            'unit' => null,
            'measured_at' => '2024-02-01',
        ]);

        // Month 8 (2024-02-15): Endocrinology panel
        $this->addLabPanel($patient, '2024-02-15', [
            ['Serum sodium', '2951-2', 148, 'mEq/L', 136, 145, 'H'],
            ['Serum osmolality', '2692-2', 298, 'mOsm/kg', 275, 295, 'H'],
            ['Prolactin', '2842-3', 42, 'ng/mL', 4, 15, 'H'],
            ['TSH', '3016-3', 1.8, 'mIU/L', 0.4, 4.0, null],
            ['Free T4', '3024-7', 1.1, 'ng/dL', 0.8, 1.8, null],
            ['AM Cortisol', '2143-6', 14, 'mcg/dL', 6, 24, null],
            ['Testosterone total', '2986-8', 180, 'ng/dL', 264, 916, 'L'],
            ['LH', '10501-5', 1.2, 'mIU/mL', 1.7, 8.6, 'L'],
        ]);

        // Month 14 (2024-08-15): Nephrology
        $this->addLabPanel($patient, '2024-08-15', [
            ['Creatinine', '2160-0', 1.8, 'mg/dL', 0.7, 1.3, 'H'],
            ['BUN', '3094-0', 28, 'mg/dL', 7, 20, 'H'],
            ['eGFR', '48642-3', 42, 'mL/min/1.73m2', 60, null, 'L'],
        ]);

        // Month 18 (2025-02-15): Cardiology
        $this->addLabPanel($patient, '2025-02-15', [
            ['BNP', '30934-4', 380, 'pg/mL', null, 100, 'H'],
            ['Troponin I', '49563-0', 0.03, 'ng/mL', null, 0.04, null],
        ]);

        // Month 22 (2025-04-15): Hematology — BRAF cfDNA
        $this->addMeasurement($patient, [
            'measurement_name' => 'BRAF V600E cfDNA',
            'concept_code' => '85075-9',
            'vocabulary' => 'LOINC',
            'value_text' => 'Detected — VAF 2.8%',
            'unit' => null,
            'measured_at' => '2025-04-15',
        ]);

        // ── Observations ────────────────────────────────────────
        // Weight tracking (declining)
        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 210,
            'value_text' => '210 lb',
            'observed_at' => '2023-06-15',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 195,
            'value_text' => '195 lb',
            'observed_at' => '2024-02-01',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 185,
            'value_text' => '185 lb',
            'observed_at' => '2025-02-15',
            'category' => 'vital_signs',
        ]);

        // Working diagnoses (diagnostic odyssey trail)
        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Possible metastatic bone disease vs Paget disease',
            'observed_at' => '2023-06-15',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Chronic osteomyelitis',
            'observed_at' => '2023-09-20',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Xanthogranulomatous process of unclear etiology',
            'observed_at' => '2024-02-01',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Idiopathic retroperitoneal fibrosis (Ormond disease)',
            'observed_at' => '2024-09-01',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Constrictive pericarditis vs infiltrative cardiomyopathy',
            'observed_at' => '2025-02-15',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Erdheim-Chester disease — confirmed',
            'observed_at' => '2025-12-15',
            'category' => 'clinical_assessment',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'XR',
            'study_date' => '2023-06-20',
            'description' => 'X-ray bilateral femurs and tibiae — symmetric diaphyseal osteosclerosis with epiphyseal sparing, bilateral',
            'body_part' => 'Bilateral lower extremity',
            'num_series' => 1,
            'num_instances' => 4,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'NM',
            'study_date' => '2023-07-15',
            'description' => 'Tc-99m bone scan whole body — symmetric intense radiotracer uptake in bilateral lower extremity long bones (femurs and tibiae), epiphyses spared',
            'body_part' => 'Whole body',
            'num_series' => 1,
            'num_instances' => 6,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2024-02-20',
            'description' => 'Brain MRI with contrast — thickened pituitary stalk (4.2mm), absent posterior pituitary bright spot, no parenchymal lesions',
            'body_part' => 'Brain',
            'num_series' => 4,
            'num_instances' => 180,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-06-15',
            'description' => 'CT chest — interlobular septal thickening, small bilateral pleural effusions, periaortic soft tissue cuffing',
            'body_part' => 'Chest',
            'num_series' => 2,
            'num_instances' => 250,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-08-10',
            'description' => 'CT abdomen/pelvis with contrast — bilateral perinephric infiltration ("hairy kidney"), circumferential periaortic soft tissue ("coated aorta"), bilateral hydronephrosis',
            'body_part' => 'Abdomen and pelvis',
            'num_series' => 3,
            'num_instances' => 400,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2025-02-10',
            'description' => 'Transthoracic echocardiogram — moderate pericardial effusion 1.8cm circumferential, right atrial wall thickening, LVEF 55%',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 48,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2025-02-20',
            'description' => 'Cardiac MRI — pericardial enhancement with delayed gadolinium enhancement, right atrial infiltration, small bilateral pleural effusions',
            'body_part' => 'Heart',
            'num_series' => 6,
            'num_instances' => 320,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'PET',
            'study_date' => '2025-04-20',
            'description' => 'PET-CT whole body — multifocal FDG uptake: bilateral femurs/tibiae SUVmax 4.2, pericardium, periaortic, retroperitoneum, perinephric, pituitary stalk',
            'body_part' => 'Whole body',
            'num_series' => 2,
            'num_instances' => 500,
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'BRAF',
            'variant' => 'p.V600E',
            'hgvs_c' => 'c.1799T>A',
            'variant_type' => 'SNV',
            'chromosome' => 'chr7',
            'allele_frequency' => 0.028,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'FDA_approved_therapy',
            'sample_type' => 'cfDNA (liquid biopsy), confirmed on tissue',
            'reported_at' => '2025-04-15',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'condition_name' => 'Bone pain era',
            'era_start' => '2023-06-01',
            'era_end' => null,
            'occurrence_count' => 8,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Central diabetes insipidus era',
            'era_start' => '2024-02-01',
            'era_end' => null,
            'occurrence_count' => 6,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Retroperitoneal fibrosis era',
            'era_start' => '2024-08-01',
            'era_end' => null,
            'occurrence_count' => 5,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Pericardial disease era',
            'era_start' => '2025-02-01',
            'era_end' => null,
            'occurrence_count' => 4,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Failed antibiotics (ciprofloxacin + vancomycin)',
            'era_start' => '2023-10-01',
            'era_end' => '2023-11-22',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Desmopressin',
            'era_start' => '2024-03-01',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Vemurafenib',
            'era_start' => '2025-12-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
