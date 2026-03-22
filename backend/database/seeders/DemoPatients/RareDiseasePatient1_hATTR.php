<?php

namespace Database\Seeders\DemoPatients;

class RareDiseasePatient1_hATTR
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-RD-001',
            'first_name' => 'Marcus',
            'last_name' => 'Washington',
            'date_of_birth' => '1966-03-14',
            'sex' => 'Male',
            'race' => 'Black or African American',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-MW-45892');
        $this->addIdentifier($patient, 'hospital_mrn', 'UMC-338891');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Hereditary transthyretin amyloidosis',
            'concept_code' => 'E85.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2022-06-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Heart failure with preserved ejection fraction — restrictive cardiomyopathy',
            'concept_code' => 'I43',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2019-01-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Bilateral carpal tunnel syndrome',
            'concept_code' => 'G56.00',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2018-03-01',
            'severity' => 'moderate',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Autonomic neuropathy',
            'concept_code' => 'G90.09',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2019-06-01',
            'severity' => 'moderate',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic kidney disease stage 3a',
            'concept_code' => 'N18.31',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2020-01-01',
            'severity' => 'moderate',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Ventricular tachycardia',
            'concept_code' => 'I47.20',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-06-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Peripheral polyneuropathy',
            'concept_code' => 'G62.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2019-06-01',
            'severity' => 'moderate',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Protein-calorie malnutrition',
            'concept_code' => 'E44.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2021-01-01',
            'severity' => 'moderate',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Tafamidis meglumine',
            'concept_code' => '2377453',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 61,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2022-06-20',
            'status' => 'active',
            'prescriber' => 'Dr. Sarah Chen',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Midodrine',
            'concept_code' => '6956',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 10,
            'dose_unit' => 'mg',
            'frequency' => 'TID',
            'start_date' => '2022-06-20',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Gabapentin',
            'concept_code' => '25480',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 300,
            'dose_unit' => 'mg',
            'frequency' => 'TID',
            'start_date' => '2022-06-20',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Diflunisal',
            'concept_code' => '3393',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 250,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2023-06-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Furosemide',
            'concept_code' => '4603',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 80,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2019-01-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Spironolactone',
            'concept_code' => '9997',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 25,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2019-01-15',
            'end_date' => '2021-01-10',
            'status' => 'discontinued',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Bilateral carpal tunnel release',
            'concept_code' => '64721',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2018-09-15',
            'performer' => 'Orthopedic Surgery',
            'laterality' => 'bilateral',
            'body_site' => 'Upper extremity',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Cardiac catheterization',
            'concept_code' => '93451',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2020-06-10',
            'performer' => 'Cardiology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Endomyocardial biopsy',
            'concept_code' => '93505',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2022-03-15',
            'performer' => 'Cardiology',
            'body_site' => 'Heart',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Fat pad aspirate',
            'concept_code' => '88305',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2022-03-20',
            'performer' => 'Hematology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'ICD implantation',
            'concept_code' => '33249',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2023-08-10',
            'performer' => 'Electrophysiology',
            'body_site' => 'Heart',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $visitPcpAnnual2018 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2018-03-10',
            'discharge_date' => '2018-03-10',
            'attending_provider' => 'Dr. James Miller',
            'department' => 'Primary Care',
        ]);

        $visitOrtho2018 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2018-05-01',
            'discharge_date' => '2018-05-01',
            'attending_provider' => 'Dr. Robert Kim',
            'department' => 'Orthopedic Surgery',
        ]);

        $visitCardio2018 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2018-05-10',
            'discharge_date' => '2018-05-10',
            'attending_provider' => 'Dr. Patricia Hayes',
            'department' => 'Cardiology',
        ]);

        $visitPcpAnnual2019 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2019-03-12',
            'discharge_date' => '2019-03-12',
            'attending_provider' => 'Dr. James Miller',
            'department' => 'Primary Care',
        ]);

        $visitNeuro2019 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2019-06-15',
            'discharge_date' => '2019-06-15',
            'attending_provider' => 'Dr. Angela Torres',
            'department' => 'Neurology',
        ]);

        $visitCardio2019 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2019-09-20',
            'discharge_date' => '2019-09-20',
            'attending_provider' => 'Dr. Patricia Hayes',
            'department' => 'Cardiology',
        ]);

        $visitPcpAnnual2020 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2020-03-15',
            'discharge_date' => '2020-03-15',
            'attending_provider' => 'Dr. James Miller',
            'department' => 'Primary Care',
        ]);

        $visitHfInpatient2020 = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2020-06-08',
            'discharge_date' => '2020-06-14',
            'attending_provider' => 'Dr. Patricia Hayes',
            'department' => 'Cardiology',
        ]);

        $visitCardioMri2020 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2020-12-15',
            'discharge_date' => '2020-12-15',
            'attending_provider' => 'Dr. Patricia Hayes',
            'department' => 'Cardiac Imaging',
        ]);

        $visitGi2021 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2021-02-10',
            'discharge_date' => '2021-02-10',
            'attending_provider' => 'Dr. David Park',
            'department' => 'Gastroenterology',
        ]);

        $visitNucMed2021 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2021-06-20',
            'discharge_date' => '2021-06-20',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Nuclear Medicine',
        ]);

        $visitGenetics2021 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2021-09-15',
            'discharge_date' => '2021-09-15',
            'attending_provider' => 'Dr. Emily Watkins',
            'department' => 'Medical Genetics',
        ]);

        $visitHematology2022 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2022-03-20',
            'discharge_date' => '2022-03-20',
            'attending_provider' => 'Dr. Michael Ross',
            'department' => 'Hematology',
        ]);

        $visitCardio2022a = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2022-03-15',
            'discharge_date' => '2022-03-15',
            'attending_provider' => 'Dr. Patricia Hayes',
            'department' => 'Cardiology',
        ]);

        $visitCardio2022b = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2022-05-10',
            'discharge_date' => '2022-05-10',
            'attending_provider' => 'Dr. Patricia Hayes',
            'department' => 'Cardiology',
        ]);

        $visitMultidisc2022 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center — Amyloidosis Center',
            'admission_date' => '2022-06-20',
            'discharge_date' => '2022-06-20',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Amyloidosis Multidisciplinary Clinic',
        ]);

        $visitNeuro2022 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2022-06-20',
            'discharge_date' => '2022-06-20',
            'attending_provider' => 'Dr. Angela Torres',
            'department' => 'Neurology',
        ]);

        $visitPcpAnnual2022 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2022-09-10',
            'discharge_date' => '2022-09-10',
            'attending_provider' => 'Dr. James Miller',
            'department' => 'Primary Care',
        ]);

        $visitCardio2023 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2023-05-10',
            'discharge_date' => '2023-05-10',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Cardiology',
        ]);

        $visitIcdInpatient2023 = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2023-08-09',
            'discharge_date' => '2023-08-12',
            'attending_provider' => 'Dr. Kevin Wright',
            'department' => 'Electrophysiology',
        ]);

        $visitMultidisc2023 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center — Amyloidosis Center',
            'admission_date' => '2023-11-15',
            'discharge_date' => '2023-11-15',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Amyloidosis Multidisciplinary Clinic',
        ]);

        $visitPcpAnnual2024 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2024-03-14',
            'discharge_date' => '2024-03-14',
            'attending_provider' => 'Dr. James Miller',
            'department' => 'Primary Care',
        ]);

        $visitCardio2024 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2024-05-10',
            'discharge_date' => '2024-05-10',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Cardiology',
        ]);

        $visitMultidisc2024 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center — Amyloidosis Center',
            'admission_date' => '2024-11-20',
            'discharge_date' => '2024-11-20',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Amyloidosis Multidisciplinary Clinic',
        ]);

        $visitMultidisc2025 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center — Amyloidosis Center',
            'admission_date' => '2025-05-10',
            'discharge_date' => '2025-05-10',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Amyloidosis Multidisciplinary Clinic',
        ]);

        $visitPcpAnnual2025 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2025-09-15',
            'discharge_date' => '2025-09-15',
            'attending_provider' => 'Dr. James Miller',
            'department' => 'Primary Care',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $visitPcpAnnual2018->id,
            'note_type' => 'progress_note',
            'title' => 'PCP Annual Visit — Initial Presentation',
            'content' => 'Patient presents with bilateral hand numbness and tingling worsening over 3 months. Reports difficulty with fine motor tasks. Referred to orthopedic surgery for carpal tunnel evaluation. Incidental finding of mild lower extremity edema noted.',
            'author' => 'Dr. James Miller',
            'authored_at' => '2018-03-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitOrtho2018->id,
            'note_type' => 'consult_note',
            'title' => 'Orthopedic Surgery Consult — Carpal Tunnel',
            'content' => 'EMG confirms bilateral carpal tunnel syndrome, moderate-to-severe. Bilateral carpal tunnel release recommended. Flexor tenosynovium noted to be unusually thickened on exam, raising suspicion for infiltrative process.',
            'author' => 'Dr. Robert Kim',
            'authored_at' => '2018-05-01',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitCardio2018->id,
            'note_type' => 'consult_note',
            'title' => 'Cardiology Initial Consult',
            'content' => 'TTE shows concentric LV hypertrophy (wall thickness 12mm) with diastolic dysfunction grade II. NT-proBNP elevated at 1850. No valvular disease. Differential includes hypertensive heart disease vs infiltrative cardiomyopathy. Will follow closely.',
            'author' => 'Dr. Patricia Hayes',
            'authored_at' => '2018-05-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNeuro2019->id,
            'note_type' => 'procedure_note',
            'title' => 'Neurology EMG/NCS Report',
            'content' => 'EMG/NCS of bilateral lower extremities reveals length-dependent axonal sensorimotor polyneuropathy. Findings suggest a systemic process beyond typical diabetic or alcoholic neuropathy. Autonomic testing demonstrates orthostatic hypotension with 25mmHg systolic drop. Recommended workup for systemic amyloidosis.',
            'author' => 'Dr. Angela Torres',
            'authored_at' => '2019-06-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitHfInpatient2020->id,
            'note_type' => 'discharge_summary',
            'title' => 'Heart Failure Hospitalization — Discharge Summary',
            'content' => 'Admitted for acute decompensated heart failure with volume overload. Diuresed 8 liters over 5 days with IV furosemide. TTE shows progressive concentric hypertrophy (14mm) with granular sparkling pattern highly suggestive of cardiac amyloidosis. Cardiac MRI ordered as outpatient.',
            'author' => 'Dr. Patricia Hayes',
            'authored_at' => '2020-06-14',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitCardioMri2020->id,
            'note_type' => 'imaging_report',
            'title' => 'Cardiac MRI 1.5T Report',
            'content' => 'Cardiac MRI demonstrates diffuse late gadolinium enhancement in a non-coronary distribution consistent with infiltrative cardiomyopathy. Native T1 elevated at 1150ms (normal <1050ms). ECV markedly elevated at 0.55 (normal <0.30). Findings highly suggestive of cardiac amyloidosis. Tc-99m PYP scan recommended to differentiate ATTR from AL subtype.',
            'author' => 'Dr. Patricia Hayes',
            'authored_at' => '2020-12-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitGi2021->id,
            'note_type' => 'consult_note',
            'title' => 'GI Consult — Malnutrition Assessment',
            'content' => 'Patient reports 25-pound unintentional weight loss over 2 years with early satiety and intermittent diarrhea. Albumin declining (3.2). Autonomic GI dysmotility suspected secondary to amyloid infiltration. Started nutritional supplementation and referred to dietitian.',
            'author' => 'Dr. David Park',
            'authored_at' => '2021-02-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNucMed2021->id,
            'note_type' => 'imaging_report',
            'title' => 'Tc-99m PYP Nuclear Scan Interpretation',
            'content' => 'Tc-99m pyrophosphate scan shows Grade 3 diffuse myocardial uptake with H/CL ratio of 1.8. This is diagnostic for ATTR cardiac amyloidosis when AL amyloidosis is excluded by serum and urine protein electrophoresis. Genetic testing strongly recommended to differentiate hereditary from wild-type ATTR.',
            'author' => 'Dr. Lisa Nguyen',
            'authored_at' => '2021-06-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitGenetics2021->id,
            'note_type' => 'consult_note',
            'title' => 'Medical Genetics Consult',
            'content' => 'Genetic testing reveals TTR c.364G>A (p.Val142Ile) variant, classified as pathogenic. This is the most common pathogenic TTR variant, with 3-4% carrier frequency in African Americans. Confirms diagnosis of hereditary ATTR amyloidosis. Cascade genetic testing offered for first-degree relatives. Referred to amyloidosis multidisciplinary clinic.',
            'author' => 'Dr. Emily Watkins',
            'authored_at' => '2021-09-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitHematology2022->id,
            'note_type' => 'consult_note',
            'title' => 'Hematology Workup — AL Exclusion',
            'content' => 'Serum free kappa 1.3, lambda 1.5, ratio normal. Fat pad aspirate negative for AL amyloid, positive for TTR amyloid by immunohistochemistry and mass spectrometry. SPEP and UPEP negative for monoclonal protein. AL amyloidosis definitively excluded. Congo red stain shows apple-green birefringence confirming amyloid deposits.',
            'author' => 'Dr. Michael Ross',
            'authored_at' => '2022-03-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitCardio2022a->id,
            'note_type' => 'procedure_note',
            'title' => 'Endomyocardial Biopsy Pathology',
            'content' => 'Endomyocardial biopsy demonstrates extensive interstitial and perivascular amyloid deposition. Immunohistochemistry and mass spectrometry confirm ATTR (transthyretin) type amyloid. Estimated amyloid burden approximately 40% of myocardial tissue. Consistent with advanced cardiac ATTR amyloidosis.',
            'author' => 'Dr. Patricia Hayes',
            'authored_at' => '2022-03-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitMultidisc2022->id,
            'note_type' => 'progress_note',
            'title' => 'Amyloidosis Multidisciplinary Clinic — Treatment Initiation',
            'content' => 'Four-year diagnostic odyssey from first symptoms to confirmed diagnosis. Confirmed hATTR-CM with V142I variant. Starting tafamidis 61mg daily as TTR stabilizer. Adding midodrine for orthostatic hypotension, gabapentin for neuropathic pain. Comprehensive treatment plan coordinated across cardiology, neurology, genetics, and nutrition. Follow-up every 3 months.',
            'author' => 'Dr. Sarah Chen',
            'authored_at' => '2022-06-20',
        ]);

        // ── Lab Panels ──────────────────────────────────────────
        // Year 1 — 2018
        $this->addLabPanel($patient, '2018-05-10', [
            ['NT-proBNP', '33762-6', 1850, 'pg/mL', 0, 125, 'H'],
            ['Troponin T', '6598-7', 0.04, 'ng/mL', 0, 0.01, 'H'],
            ['eGFR', '48642-3', 72, 'mL/min/1.73m2', 90, null, 'L'],
            ['Albumin', '1751-7', 3.8, 'g/dL', 3.5, 5.0, null],
            ['BNP', '30934-4', 420, 'pg/mL', 0, 100, 'H'],
        ]);

        // Year 3 — 2020
        $this->addLabPanel($patient, '2020-05-10', [
            ['NT-proBNP', '33762-6', 3200, 'pg/mL', 0, 125, 'H'],
            ['Troponin T', '6598-7', 0.06, 'ng/mL', 0, 0.01, 'H'],
            ['eGFR', '48642-3', 65, 'mL/min/1.73m2', 90, null, 'L'],
            ['Albumin', '1751-7', 3.5, 'g/dL', 3.5, 5.0, null],
            ['BNP', '30934-4', 680, 'pg/mL', 0, 100, 'H'],
            ['Serum Free Kappa Light Chain', '11050-2', 1.2, 'mg/dL', 0.33, 1.94, null],
            ['Serum Free Lambda Light Chain', '11051-0', 1.4, 'mg/dL', 0.57, 2.63, null],
        ]);

        // Year 5 — 2022
        $this->addLabPanel($patient, '2022-05-10', [
            ['NT-proBNP', '33762-6', 4500, 'pg/mL', 0, 125, 'H'],
            ['Troponin T', '6598-7', 0.09, 'ng/mL', 0, 0.01, 'H'],
            ['eGFR', '48642-3', 58, 'mL/min/1.73m2', 90, null, 'L'],
            ['Albumin', '1751-7', 3.2, 'g/dL', 3.5, 5.0, 'L'],
            ['BNP', '30934-4', 950, 'pg/mL', 0, 100, 'H'],
            ['TTR (Prealbumin)', '14338-1', 12, 'mg/dL', 20, 40, 'L'],
            ['Serum Free Kappa Light Chain', '11050-2', 1.3, 'mg/dL', 0.33, 1.94, null],
            ['Serum Free Lambda Light Chain', '11051-0', 1.5, 'mg/dL', 0.57, 2.63, null],
        ]);

        // Year 6 — 2023
        $this->addLabPanel($patient, '2023-05-10', [
            ['NT-proBNP', '33762-6', 3100, 'pg/mL', 0, 125, 'H'],
            ['Troponin T', '6598-7', 0.07, 'ng/mL', 0, 0.01, 'H'],
            ['eGFR', '48642-3', 55, 'mL/min/1.73m2', 90, null, 'L'],
            ['Albumin', '1751-7', 3.4, 'g/dL', 3.5, 5.0, null],
            ['BNP', '30934-4', 620, 'pg/mL', 0, 100, 'H'],
            ['TTR (Prealbumin)', '14338-1', 22, 'mg/dL', 20, 40, null],
        ]);

        // Year 8 — 2025
        $this->addLabPanel($patient, '2025-05-10', [
            ['NT-proBNP', '33762-6', 2400, 'pg/mL', 0, 125, 'H'],
            ['Troponin T', '6598-7', 0.05, 'ng/mL', 0, 0.01, 'H'],
            ['eGFR', '48642-3', 52, 'mL/min/1.73m2', 90, null, 'L'],
            ['Albumin', '1751-7', 3.6, 'g/dL', 3.5, 5.0, null],
            ['BNP', '30934-4', 480, 'pg/mL', 0, 100, 'H'],
            ['TTR (Prealbumin)', '14338-1', 24, 'mg/dL', 20, 40, null],
        ]);

        // ── Observations ────────────────────────────────────────
        $this->addObservation($patient, [
            'observation_name' => 'NYHA Functional Classification',
            'concept_code' => '420816009',
            'vocabulary' => 'SNOMED',
            'value_text' => 'Class II',
            'observed_at' => '2018-05-10',
            'category' => 'functional_status',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'NYHA Functional Classification',
            'concept_code' => '420816009',
            'vocabulary' => 'SNOMED',
            'value_text' => 'Class III',
            'observed_at' => '2020-06-10',
            'category' => 'functional_status',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'NYHA Functional Classification',
            'concept_code' => '420816009',
            'vocabulary' => 'SNOMED',
            'value_text' => 'Class III',
            'observed_at' => '2022-06-20',
            'category' => 'functional_status',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'NYHA Functional Classification',
            'concept_code' => '420816009',
            'vocabulary' => 'SNOMED',
            'value_text' => 'Class II-III',
            'observed_at' => '2024-05-10',
            'category' => 'functional_status',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 195,
            'value_text' => '195 lb',
            'observed_at' => '2018-05-10',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 185,
            'value_text' => '185 lb',
            'observed_at' => '2020-06-10',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 170,
            'value_text' => '170 lb',
            'observed_at' => '2022-06-20',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Body Weight',
            'concept_code' => '29463-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 165,
            'value_text' => '165 lb',
            'observed_at' => '2024-05-10',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Orthostatic Blood Pressure Drop',
            'concept_code' => '75367002',
            'vocabulary' => 'SNOMED',
            'value_numeric' => 25,
            'value_text' => '25 mmHg systolic drop on standing',
            'observed_at' => '2022-06-20',
            'category' => 'vital_signs',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Karnofsky Performance Status',
            'concept_code' => '89243-0',
            'vocabulary' => 'LOINC',
            'value_numeric' => 80,
            'value_text' => '80 — Normal activity with effort',
            'observed_at' => '2018-05-10',
            'category' => 'functional_status',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Karnofsky Performance Status',
            'concept_code' => '89243-0',
            'vocabulary' => 'LOINC',
            'value_numeric' => 60,
            'value_text' => '60 — Requires occasional assistance',
            'observed_at' => '2022-06-20',
            'category' => 'functional_status',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Karnofsky Performance Status',
            'concept_code' => '89243-0',
            'vocabulary' => 'LOINC',
            'value_numeric' => 70,
            'value_text' => '70 — Cares for self but unable to carry on normal activity',
            'observed_at' => '2024-05-10',
            'category' => 'functional_status',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2018-05-10',
            'description' => 'TTE — Concentric LV hypertrophy, wall thickness 12mm, diastolic dysfunction grade II',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 45,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2020-06-10',
            'description' => 'TTE — Progressive concentric hypertrophy, LV wall 14mm, granular sparkling pattern, diastolic dysfunction grade III',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 52,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2020-12-15',
            'description' => 'Cardiac MRI 1.5T — Diffuse LGE non-coronary distribution, native T1 1150ms, ECV 0.55, consistent with infiltrative cardiomyopathy',
            'body_part' => 'Heart',
            'num_series' => 8,
            'num_instances' => 320,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'NM',
            'study_date' => '2021-06-20',
            'description' => 'Tc-99m PYP scan — Grade 3 diffuse myocardial uptake, H/CL ratio 1.8, diagnostic for ATTR cardiac amyloidosis',
            'body_part' => 'Heart',
            'num_series' => 2,
            'num_instances' => 64,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'EMG',
            'study_date' => '2019-06-15',
            'description' => 'EMG/NCS — Length-dependent axonal sensorimotor polyneuropathy, bilateral lower extremities',
            'body_part' => 'Lower extremity',
            'laterality' => 'bilateral',
            'num_series' => 1,
            'num_instances' => 1,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'EMG',
            'study_date' => '2022-06-20',
            'description' => 'EMG/NCS — Progression of axonal polyneuropathy compared to 2019, bilateral lower extremities',
            'body_part' => 'Lower extremity',
            'laterality' => 'bilateral',
            'num_series' => 1,
            'num_instances' => 1,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2022-06-25',
            'description' => 'Nerve ultrasound — Median nerve cross-sectional area 18mm² (normal <10mm²), bilateral upper extremities',
            'body_part' => 'Upper extremity',
            'laterality' => 'bilateral',
            'num_series' => 1,
            'num_instances' => 12,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2022-05-10',
            'description' => 'TTE — LV wall thickness 15mm, persistent granular sparkling, diastolic dysfunction grade III, LVEF 55%',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 48,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2024-05-10',
            'description' => 'TTE — LV wall thickness stable at 15mm on tafamidis therapy, LVEF 52%, no significant interval change',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 50,
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'TTR',
            'variant' => 'c.364G>A (p.Val142Ile)',
            'variant_type' => 'SNV',
            'chromosome' => 'chr18',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'FDA-approved therapy',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'CYP2C9',
            'variant' => '*3',
            'variant_type' => 'SNV',
            'chromosome' => 'chr10',
            'zygosity' => 'heterozygous',
            'clinical_significance' => 'VUS',
            'actionability' => 'dose_adjustment',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Heart failure',
            'era_start' => '2019-01-01',
            'era_end' => null,
            'occurrence_count' => 8,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Polyneuropathy',
            'era_start' => '2019-06-01',
            'era_end' => null,
            'occurrence_count' => 5,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Chronic kidney disease',
            'era_start' => '2020-01-01',
            'era_end' => null,
            'occurrence_count' => 6,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Furosemide',
            'era_start' => '2019-01-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Tafamidis',
            'era_start' => '2022-06-20',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Midodrine',
            'era_start' => '2022-06-20',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Gabapentin',
            'era_start' => '2022-06-20',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Diflunisal',
            'era_start' => '2023-06-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
