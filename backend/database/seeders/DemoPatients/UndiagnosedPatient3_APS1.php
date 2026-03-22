<?php

namespace Database\Seeders\DemoPatients;

class UndiagnosedPatient3_APS1
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-UD-003',
            'first_name' => 'Sofia',
            'last_name' => 'Reyes',
            'date_of_birth' => '2015-03-22',
            'sex' => 'Female',
            'race' => 'White',
            'ethnicity' => 'Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-SR-11847');
        $this->addIdentifier($patient, 'hospital_mrn', 'CHH-334892', 'Children\'s Hospital');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Autoimmune polyendocrine syndrome type 1 (APECED)',
            'concept_code' => 'E31.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2026-09-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic mucocutaneous candidiasis',
            'concept_code' => 'B37.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-03-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Autoimmune hypoparathyroidism',
            'concept_code' => 'E20.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-03-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Primary adrenal insufficiency (Addison disease)',
            'concept_code' => 'E27.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2026-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Autoimmune hepatitis type 1',
            'concept_code' => 'K75.4',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-07-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Alopecia areata',
            'concept_code' => 'L63.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-03-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Nail dystrophy (trachyonychia)',
            'concept_code' => 'L60.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-03-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Oligoarticular juvenile idiopathic arthritis',
            'concept_code' => 'M08.40',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2025-03-01',
            'resolution_date' => '2026-09-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Keratoconjunctivitis sicca',
            'concept_code' => 'H16.22',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2026-05-01',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Dental enamel hypoplasia',
            'concept_code' => 'K00.4',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-09-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Fluconazole',
            'concept_code' => '4450',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 100,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2023-09-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Calcium carbonate',
            'concept_code' => '1897',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 500,
            'dose_unit' => 'mg',
            'frequency' => 'TID',
            'start_date' => '2024-03-20',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Calcitriol',
            'concept_code' => '1886',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 0.25,
            'dose_unit' => 'mcg',
            'frequency' => 'BID',
            'start_date' => '2024-03-20',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Hydrocortisone',
            'concept_code' => '5492',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 10,
            'dose_unit' => 'mg/m2/day',
            'frequency' => 'divided TID',
            'start_date' => '2026-06-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Fludrocortisone',
            'concept_code' => '4456',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 0.1,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2026-06-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Prednisolone',
            'concept_code' => '8638',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 1,
            'dose_unit' => 'mg/kg/day',
            'frequency' => 'once daily with taper',
            'start_date' => '2025-08-01',
            'end_date' => '2026-01-15',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Azathioprine',
            'concept_code' => '1256',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 1,
            'dose_unit' => 'mg/kg/day',
            'frequency' => 'once daily',
            'start_date' => '2025-08-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Artificial tears',
            'concept_code' => '197319',
            'vocabulary' => 'RxNorm',
            'route' => 'ophthalmic',
            'dose_value' => 1,
            'dose_unit' => 'drop',
            'frequency' => 'PRN',
            'start_date' => '2026-05-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Naproxen',
            'concept_code' => '7258',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 10,
            'dose_unit' => 'mg/kg/day',
            'frequency' => 'BID',
            'start_date' => '2025-03-20',
            'end_date' => '2026-09-01',
            'status' => 'completed',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Scalp biopsy',
            'concept_code' => '11102',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2023-05-15',
            'performer' => 'Dermatology',
            'body_site' => 'Scalp',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Electroencephalogram (EEG)',
            'concept_code' => '95816',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2024-03-15',
            'performer' => 'Neurology',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Liver biopsy',
            'concept_code' => '47100',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2025-08-10',
            'performer' => 'Pediatric GI',
            'body_site' => 'Liver',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Ophthalmologic slit lamp exam',
            'concept_code' => '92012',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2026-05-10',
            'performer' => 'Ophthalmology',
            'body_site' => 'Eyes',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'ACTH stimulation test (cosyntropin)',
            'concept_code' => '80400',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2026-06-10',
            'performer' => 'Endocrinology',
        ]);

        // ── Visits (diagnostic odyssey ~3 years, 7 subspecialties) ─
        // Month 0: PCP — recurrent thrush, alopecia, nail dystrophy
        $visitPcp0 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2023-03-15',
            'discharge_date' => '2023-03-15',
            'attending_provider' => 'Dr. Maria Gonzalez',
            'department' => 'Pediatrics',
        ]);

        // Month 2: Dermatology — scalp biopsy
        $visitDerm2 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2023-05-15',
            'discharge_date' => '2023-05-15',
            'attending_provider' => 'Dr. Rachel Kim',
            'department' => 'Dermatology',
        ]);

        // Month 2b: Dermatology biopsy result
        $visitDermResult = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2023-05-20',
            'discharge_date' => '2023-05-20',
            'attending_provider' => 'Dr. Rachel Kim',
            'department' => 'Dermatology',
        ]);

        // Month 6: Pediatric Immunology
        $visitImmuno6 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2023-09-15',
            'discharge_date' => '2023-09-15',
            'attending_provider' => 'Dr. Jonathan Blake',
            'department' => 'Pediatric Immunology',
        ]);

        // Month 6b: Immunology workup follow-up
        $visitImmunoResult = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2023-09-20',
            'discharge_date' => '2023-09-20',
            'attending_provider' => 'Dr. Jonathan Blake',
            'department' => 'Pediatric Immunology',
        ]);

        // Month 12: ED → PICU — hypocalcemic seizure
        $visitED12 = $this->addVisit($patient, [
            'visit_type' => 'emergency',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2024-03-15',
            'discharge_date' => '2024-03-18',
            'attending_provider' => 'Dr. Kevin Marsh',
            'department' => 'Emergency/PICU',
        ]);

        // Month 13: Pediatric Endocrinology #1
        $visitEndo13 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2024-04-15',
            'discharge_date' => '2024-04-15',
            'attending_provider' => 'Dr. Natasha Patel',
            'department' => 'Pediatric Endocrinology',
        ]);

        // Month 18: Pediatric Dentistry
        $visitDent18 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital Dental Clinic',
            'admission_date' => '2024-09-15',
            'discharge_date' => '2024-09-15',
            'attending_provider' => 'Dr. Amy Chen',
            'department' => 'Pediatric Dentistry',
        ]);

        // Month 24: Pediatric Rheumatology
        $visitRheum24 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2025-03-20',
            'discharge_date' => '2025-03-20',
            'attending_provider' => 'Dr. Lisa Brennan',
            'department' => 'Pediatric Rheumatology',
        ]);

        // Month 28: Pediatric GI
        $visitGI28 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2025-07-15',
            'discharge_date' => '2025-07-15',
            'attending_provider' => 'Dr. Robert Feldman',
            'department' => 'Pediatric GI',
        ]);

        // Month 28b: Liver biopsy
        $visitGIBiopsy = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2025-08-10',
            'discharge_date' => '2025-08-10',
            'attending_provider' => 'Dr. Robert Feldman',
            'department' => 'Pediatric GI',
        ]);

        // Month 28c: GI biopsy result / AIH diagnosis
        $visitGIResult = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2025-08-15',
            'discharge_date' => '2025-08-15',
            'attending_provider' => 'Dr. Robert Feldman',
            'department' => 'Pediatric GI',
        ]);

        // Month 32: Ophthalmology
        $visitOphtho32 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2026-05-10',
            'discharge_date' => '2026-05-10',
            'attending_provider' => 'Dr. Sarah Winters',
            'department' => 'Ophthalmology',
        ]);

        // Month 34: Endocrinology #2 — Addison
        $visitEndo34 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2026-06-10',
            'discharge_date' => '2026-06-10',
            'attending_provider' => 'Dr. Natasha Patel',
            'department' => 'Pediatric Endocrinology',
        ]);

        // Month 36: Genetics — AIRE confirmation
        $visitGenetics36 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Children\'s Hospital',
            'admission_date' => '2026-09-15',
            'discharge_date' => '2026-09-15',
            'attending_provider' => 'Dr. Elizabeth Tran',
            'department' => 'Genetics',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $visitPcp0->id,
            'note_type' => 'progress_note',
            'title' => 'PCP Initial Visit — Recurrent Thrush, Alopecia, Nail Dystrophy',
            'content' => '8-year-old female presents with recurrent oral thrush (4 episodes in the past year), patchy hair loss on scalp x2 months, and roughened/ridged fingernails. Mother reports child has had frequent muscle cramps in legs. No significant past medical history. Immunizations up to date. Growth parameters: height 50th percentile, weight 45th percentile. Examination: white plaques on buccal mucosa and tongue consistent with oral candidiasis, patchy non-scarring alopecia on vertex scalp, trachyonychia (rough sandpaper-like nails) all 10 fingernails, mild Chvostek sign equivocal. Labs: CBC all within normal limits. CMP notable for calcium 8.2 mg/dL (ref 8.8-10.8) — slightly low, likely hemolysis artifact given otherwise normal labs. Started nystatin oral suspension. Referral to dermatology for alopecia evaluation.',
            'author' => 'Dr. Maria Gonzalez',
            'authored_at' => '2023-03-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDermResult->id,
            'note_type' => 'procedure_note',
            'title' => 'Dermatology Scalp Biopsy Pathology — Alopecia Areata',
            'content' => 'PATHOLOGY REPORT: Punch biopsy, scalp. GROSS: 4mm punch biopsy from area of alopecia, vertex scalp. MICROSCOPIC: Peribulbar lymphocytic infiltrate surrounding hair follicle bulbs ("swarm of bees" pattern). Increased catagen/telogen follicles. No scarring fibrosis. No granulomas. DIAGNOSIS: Alopecia areata. CLINICAL CORRELATION: 8-year-old with patchy alopecia, nail dystrophy (trachyonychia), and recurrent mucocutaneous candidiasis. Biopsy confirms alopecia areata. The combination of alopecia areata with trachyonychia is well-recognized. Recommend topical clobetasol for affected scalp areas and dermatology follow-up in 3 months.',
            'author' => 'Dr. Rachel Kim',
            'authored_at' => '2023-05-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitImmunoResult->id,
            'note_type' => 'consult_note',
            'title' => 'Pediatric Immunology — No Primary Immunodeficiency Identified',
            'content' => 'Referred for evaluation of recurrent mucocutaneous candidiasis without systemic immune defect. 8-year-old female with 4+ episodes oral thrush/year, one episode esophageal candidiasis, and vulvovaginal candidiasis x2. No history of invasive bacterial or viral infections. No opportunistic infections. Growth and development normal. IMMUNOLOGIC WORKUP: IgG 1050 mg/dL (normal), IgA 145 mg/dL (normal), IgM 120 mg/dL (normal). Lymphocyte subsets: CD4 850 cells/uL (normal), CD8 420 cells/uL (normal), CD4/CD8 ratio 2.0 (normal). HIV negative. DHR assay normal (CGD excluded). Mannose-binding lectin 1800 ng/mL (normal). ASSESSMENT: No primary immunodeficiency identified. Recurrent mucocutaneous candidiasis without systemic immune defect. The selective susceptibility to Candida is unusual but immunoglobulin levels, T-cell subsets, and phagocyte function are all normal. NOTE: Anti-IL-17 and anti-IL-22 antibody testing was NOT performed as part of this workup (not routinely available). Recommend fluconazole 100mg daily prophylaxis given recurrence frequency.',
            'author' => 'Dr. Jonathan Blake',
            'authored_at' => '2023-09-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitED12->id,
            'note_type' => 'progress_note',
            'title' => 'ED/PICU — Hypocalcemic Seizure',
            'content' => 'EMERGENCY PRESENTATION: 9-year-old female brought in by ambulance after witnessed generalized tonic-clonic seizure at school, duration approximately 3 minutes. No prior seizure history. Afebrile. No recent illness. On arrival: post-ictal, GCS 12 improving to 15 over 30 minutes. Positive Chvostek sign. Positive Trousseau sign (carpal spasm with BP cuff inflation). CRITICAL LABS: Calcium 6.8 mg/dL (CRITICAL LOW, ref 8.8-10.8), Phosphorus 7.2 mg/dL (HIGH, ref 3.7-5.6), Magnesium 1.6 mg/dL (LOW, ref 1.7-2.2), Albumin 4.0 g/dL (normal — confirms TRUE hypocalcemia, not artifact), PTH 4 pg/mL (CRITICAL LOW, ref 15-65), 25-OH Vitamin D 32 ng/mL (normal). ECG: QTc 502ms (prolonged — hypocalcemia). EEG: No epileptiform discharges, generalized slowing consistent with metabolic encephalopathy. MANAGEMENT: IV calcium gluconate 100mg/kg bolus then continuous infusion. Transferred to PICU for cardiac monitoring. Calcium stabilized at 7.8 mg/dL on IV supplementation. Started oral calcium carbonate 500mg TID and calcitriol 0.25mcg BID. ASSESSMENT: Hypocalcemic seizure secondary to hypoparathyroidism. Critically low PTH with hyperphosphatemia and normal vitamin D confirms primary hypoparathyroidism. RETROSPECTIVE NOTE: The calcium of 8.2 mg/dL at PCP visit 12 months ago was likely a TRUE finding, not hemolysis artifact.',
            'author' => 'Dr. Kevin Marsh',
            'authored_at' => '2024-03-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitEndo13->id,
            'note_type' => 'consult_note',
            'title' => 'Pediatric Endocrinology #1 — Autoimmune HP Confirmed, DiGeorge Excluded',
            'content' => '9-year-old female referred after hypocalcemic seizure with confirmed hypoparathyroidism (PTH 4 pg/mL). ADDITIONAL WORKUP: Parathyroid antibodies positive at 1:320 titer. Anti-calcium sensing receptor (CaSR) antibodies positive. DiGeorge syndrome FISH for 22q11.2 deletion: NORMAL. AM Cortisol 12 mcg/dL (normal, ref 6-24). ACTH stimulation test 60-minute cortisol 22 mcg/dL (normal, >18). TSH 3.2 mIU/L (normal). Free T4 1.2 ng/dL (normal). ASSESSMENT: Autoimmune hypoparathyroidism confirmed by positive parathyroid antibodies and anti-CaSR antibodies. DiGeorge syndrome excluded (normal 22q11.2 FISH). Remaining endocrine axes (adrenal, thyroid) are currently normal. This is classified as ISOLATED autoimmune hypoparathyroidism at this time. PLAN: Continue calcium carbonate 500mg TID and calcitriol 0.25mcg BID. Monitor serum calcium q3 months. Annual screening for adrenal and thyroid autoimmunity given autoimmune HP can be a component of polyglandular syndromes.',
            'author' => 'Dr. Natasha Patel',
            'authored_at' => '2024-04-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDent18->id,
            'note_type' => 'progress_note',
            'title' => 'Pediatric Dentistry — Enamel Hypoplasia',
            'content' => 'DENTAL EXAMINATION: 9-year-old female referred for evaluation of abnormal tooth appearance. Examination reveals horizontal pitting defects on permanent incisors and first molars bilaterally. Enamel is thin and rough with yellowish discoloration in affected areas. No caries currently. No gingival disease. RADIOGRAPHS: Thin enamel layer visible on permanent teeth. Deciduous teeth (remaining) appear normal. ASSESSMENT: Enamel hypoplasia of permanent dentition — horizontal pitting pattern affecting incisors and first molars. Differential includes fluorosis (but patient not in high-fluoride area and pattern is atypical for fluorosis), developmental enamel defect, or systemic condition affecting amelogenesis. Recommend fluoride varnish application and close monitoring. NOTE: This finding was not communicated to the patient\'s endocrinologist.',
            'author' => 'Dr. Amy Chen',
            'authored_at' => '2024-09-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheum24->id,
            'note_type' => 'consult_note',
            'title' => 'Pediatric Rheumatology — Oligoarticular JIA',
            'content' => '10-year-old female referred for bilateral knee swelling x6 weeks. PMH: autoimmune hypoparathyroidism, alopecia areata, recurrent mucocutaneous candidiasis. EXAMINATION: Bilateral knee effusions with warmth, limited ROM (flexion 100 degrees bilaterally), no hip or ankle involvement, no enthesitis, no psoriatic features. No sacroiliac tenderness. Eyes: no uveitis on slit lamp exam (normal). LABS: ESR 38 mm/hr (elevated), CRP 1.8 mg/dL (elevated), ANA 1:160 speckled pattern, RF <10 IU/mL (negative), anti-CCP <20 U/mL (negative), HLA-B27 negative. IMAGING: Knee ultrasound shows bilateral suprapatellar effusions with synovial thickening. ASSESSMENT: Oligoarticular juvenile idiopathic arthritis (JIA). ANA-positive, RF-negative, anti-CCP-negative with bilateral knee involvement. Started naproxen 10mg/kg/day divided BID. Will consider intra-articular steroid injection if not responding. Eye exam q3 months for uveitis screening (ANA-positive JIA). NOTE: Patient has multiple autoimmune conditions (HP, alopecia, candidiasis) — these are treated as separate comorbidities.',
            'author' => 'Dr. Lisa Brennan',
            'authored_at' => '2025-03-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitGIResult->id,
            'note_type' => 'procedure_note',
            'title' => 'GI Liver Biopsy Pathology — Autoimmune Hepatitis',
            'content' => 'PATHOLOGY REPORT: Liver biopsy, percutaneous. GROSS: 18-gauge needle core biopsy, 1.5cm. MICROSCOPIC: Interface hepatitis with prominent lymphoplasmacytic portal infiltrate extending into the lobular parenchyma. Rosette formation of hepatocytes at the limiting plate. No granulomas. No bile duct damage. No steatosis. Iron stain negative. STAGING: Metavir Activity A2, Fibrosis F0 (no fibrosis). DIAGNOSIS: Changes compatible with autoimmune hepatitis. CLINICAL COMMENT: This 10-year-old female has ASMA positive 1:80, elevated IgG 1850 mg/dL, AST 142, ALT 198, and biopsy showing interface hepatitis — consistent with autoimmune hepatitis type 1 (ASMA+, anti-LKM-1 negative). IMPORTANT NOTE: This patient has a remarkable constellation of autoimmune conditions: autoimmune hypoparathyroidism, alopecia areata, chronic mucocutaneous candidiasis, oligoarticular JIA, and now autoimmune hepatitis. This combination raises strong suspicion for an autoimmune polyglandular syndrome. Consider AIRE gene testing. However, AIRE testing was not ordered at this time.',
            'author' => 'Dr. Robert Feldman',
            'authored_at' => '2025-08-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitEndo34->id,
            'note_type' => 'consult_note',
            'title' => 'Pediatric Endocrinology #2 — Addison Diagnosis, Classic Triad Recognized',
            'content' => '11-year-old female returning for endocrinology follow-up. Mother reports new symptoms: progressive skin darkening (especially knuckles, elbows, gum line), salt craving, fatigue, and episodes of dizziness. PMH: autoimmune hypoparathyroidism (on calcium/calcitriol), alopecia areata, CMC, autoimmune hepatitis (on azathioprine), enamel hypoplasia, JIA (on naproxen). EXAM: Hyperpigmentation of buccal mucosa, palmar creases, and extensor surfaces. BP 88/54 (low for age). CRITICAL LABS: AM Cortisol 3.2 mcg/dL (CRITICAL LOW, ref 6-24). ACTH 280 pg/mL (markedly elevated, ref 10-60). ACTH stimulation test — 60-minute cortisol 4.8 mcg/dL (CRITICAL LOW, >18 expected). 21-Hydroxylase antibodies positive. Aldosterone 2 ng/dL (low, ref 3-35). Renin 18 ng/mL/hr (elevated, ref 0.5-4.0). Na 132 mEq/L (low). K 5.8 mEq/L (high). DIAGNOSIS: Primary adrenal insufficiency (Addison disease) — autoimmune, confirmed by positive 21-hydroxylase antibodies and failed ACTH stimulation. CRITICAL RECOGNITION: The CLASSIC TRIAD of APS-1/APECED is now complete: (1) chronic mucocutaneous candidiasis, (2) hypoparathyroidism, (3) Addison disease. Combined with alopecia, nail dystrophy, enamel hypoplasia, autoimmune hepatitis — this is almost certainly APS-1. AIRE gene sequencing ORDERED. Started hydrocortisone 10mg/m2/day divided TID and fludrocortisone 0.1mg daily. Stress dosing education provided to family.',
            'author' => 'Dr. Natasha Patel',
            'authored_at' => '2026-06-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitGenetics36->id,
            'note_type' => 'progress_note',
            'title' => 'Genetics — AIRE Gene Results Confirm APS-1/APECED',
            'content' => 'GENETIC TESTING RESULTS: AIRE gene sequencing reveals compound heterozygous pathogenic variants: (1) c.769C>T (p.Arg257Ter) in exon 6 — nonsense mutation, the most common AIRE mutation worldwide (Finnish founder mutation), and (2) c.967_979del13 (p.Leu323fsX372) in exon 8 — frameshift deletion causing premature stop codon. Both variants are classified as pathogenic per ACMG criteria. INTERPRETATION: These biallelic AIRE loss-of-function mutations confirm the diagnosis of Autoimmune Polyendocrine Syndrome Type 1 (APS-1), also known as APECED (Autoimmune Polyendocrinopathy-Candidiasis-Ectodermal Dystrophy). ANTI-CYTOKINE ANTIBODY PANEL: Anti-IFN-omega antibodies >300 U/mL (markedly elevated, ref <50) — highly specific biomarker for APS-1. Anti-IFN-alpha antibodies positive. Anti-IL-17F antibodies positive (explains candidiasis susceptibility). Anti-IL-22 antibodies positive. HLA TYPING: HLA-DRB1*04:04 — associated with increased risk of autoimmune hepatitis in APS-1. CLINICAL CORRELATION: All manifestations are now unified under APS-1: CMC (anti-IL-17/IL-22), hypoparathyroidism, Addison disease (classic triad), autoimmune hepatitis, alopecia areata, nail dystrophy, enamel hypoplasia (ectodermal features), keratoconjunctivitis sicca. The prior diagnosis of oligoarticular JIA is reclassified as APS-1-associated autoimmune arthritis. RECOMMENDATIONS: Lifelong monitoring for additional APS-1 components (type 1 diabetes, autoimmune thyroiditis, pernicious anemia, vitiligo, gonadal failure). Annual screening panel recommended. Genetic counseling for parents (autosomal recessive).',
            'author' => 'Dr. Elizabeth Tran',
            'authored_at' => '2026-09-15',
        ]);

        // ── Lab Panels ──────────────────────────────────────────
        // Month 0 PCP (2023-03-15)
        $this->addLabPanel($patient, '2023-03-15', [
            ['Calcium', '17861-6', 8.2, 'mg/dL', 8.8, 10.8, 'L'],
        ]);

        // Month 6 Immunology (2023-09-15)
        $this->addLabPanel($patient, '2023-09-15', [
            ['IgG', '2465-3', 1050, 'mg/dL', 700, 1600, null],
            ['IgA', '2458-8', 145, 'mg/dL', 70, 400, null],
            ['IgM', '2472-9', 120, 'mg/dL', 40, 230, null],
            ['CD4 count', '24467-3', 850, 'cells/uL', 500, 1500, null],
            ['CD8 count', '8137-2', 420, 'cells/uL', 200, 900, null],
            ['CD4/CD8 ratio', '54218-3', 2.0, 'ratio', 1.0, 3.0, null],
            ['Mannose-binding lectin', '49655-4', 1800, 'ng/mL', 500, 5000, null],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'HIV',
            'concept_code' => '68961-2',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2023-09-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'DHR assay',
            'concept_code' => '69048-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Normal',
            'unit' => null,
            'measured_at' => '2023-09-15',
        ]);

        // Month 12 ED/PICU (2024-03-15)
        $this->addLabPanel($patient, '2024-03-15', [
            ['Calcium', '17861-6', 6.8, 'mg/dL', 8.8, 10.8, 'CRITICAL LOW'],
            ['Phosphorus', '2777-1', 7.2, 'mg/dL', 3.7, 5.6, 'H'],
            ['Magnesium', '19123-9', 1.6, 'mg/dL', 1.7, 2.2, 'L'],
            ['Albumin', '1751-7', 4.0, 'g/dL', 3.5, 5.0, null],
            ['PTH', '2731-8', 4, 'pg/mL', 15, 65, 'CRITICAL LOW'],
            ['25-OH Vitamin D', '1989-3', 32, 'ng/mL', 30, 100, null],
        ]);

        // Month 13 Endocrinology (2024-04-15)
        $this->addMeasurement($patient, [
            'measurement_name' => 'Parathyroid antibodies',
            'concept_code' => '56718-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive 1:320',
            'unit' => null,
            'measured_at' => '2024-04-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Anti-CaSR antibodies',
            'concept_code' => '57789-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive',
            'unit' => null,
            'measured_at' => '2024-04-15',
        ]);
        $this->addLabPanel($patient, '2024-04-15', [
            ['AM Cortisol', '2143-6', 12, 'mcg/dL', 6, 24, null],
            ['ACTH stim 60min cortisol', '14675-3', 22, 'mcg/dL', 18, null, null],
            ['TSH', '11580-8', 3.2, 'mIU/L', 0.5, 4.5, null],
            ['Free T4', '3024-7', 1.2, 'ng/dL', 0.8, 1.8, null],
        ]);

        // Month 24 Rheumatology (2025-03-20)
        $this->addLabPanel($patient, '2025-03-20', [
            ['ESR', '30341-2', 38, 'mm/hr', 0, 20, 'H'],
            ['CRP', '1988-5', 1.8, 'mg/dL', null, 0.5, 'H'],
            ['RF', '11572-5', 8, 'IU/mL', null, 14, null],
            ['Anti-CCP', '53027-9', 15, 'U/mL', null, 20, null],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'ANA',
            'concept_code' => '8061-4',
            'vocabulary' => 'LOINC',
            'value_text' => '1:160 speckled',
            'unit' => null,
            'measured_at' => '2025-03-20',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'HLA-B27',
            'concept_code' => '13916-1',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2025-03-20',
        ]);

        // Month 28 GI (2025-07-15)
        $this->addLabPanel($patient, '2025-07-15', [
            ['AST', '1920-8', 142, 'U/L', 10, 40, 'H'],
            ['ALT', '1742-6', 198, 'U/L', 7, 56, 'H'],
            ['GGT', '2324-2', 62, 'U/L', 0, 45, 'H'],
            ['Total bilirubin', '1975-2', 1.4, 'mg/dL', 0.1, 1.2, 'H'],
            ['ALP', '6768-6', 320, 'U/L', 100, 400, null],
            ['IgG', '2465-3', 1850, 'mg/dL', 700, 1600, 'H'],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'ASMA',
            'concept_code' => '31003-8',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive 1:80',
            'unit' => null,
            'measured_at' => '2025-07-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Anti-LKM-1',
            'concept_code' => '56709-9',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2025-07-15',
        ]);

        // Month 32 Ophthalmology (2026-05-10)
        $this->addLabPanel($patient, '2026-05-10', [
            ['Schirmer test', '79840-2', 4, 'mm/5min', 10, null, 'L'],
        ]);

        // Month 34 Endocrinology (2026-06-10)
        $this->addLabPanel($patient, '2026-06-10', [
            ['AM Cortisol', '2143-6', 3.2, 'mcg/dL', 6, 24, 'CRITICAL LOW'],
            ['ACTH', '2141-0', 280, 'pg/mL', 10, 60, 'H'],
            ['ACTH stim 60min cortisol', '14675-3', 4.8, 'mcg/dL', 18, null, 'CRITICAL LOW'],
            ['Aldosterone', '1763-2', 2, 'ng/dL', 3, 35, 'L'],
            ['Renin', '2915-7', 18, 'ng/mL/hr', 0.5, 4.0, 'H'],
            ['Sodium', '2951-2', 132, 'mEq/L', 136, 145, 'L'],
            ['Potassium', '2823-3', 5.8, 'mEq/L', 3.5, 5.0, 'H'],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => '21-Hydroxylase antibodies',
            'concept_code' => '56540-8',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive',
            'unit' => null,
            'measured_at' => '2026-06-10',
        ]);

        // Month 36 Genetics (2026-09-15)
        $this->addLabPanel($patient, '2026-09-15', [
            ['Anti-IFN-omega antibodies', '94505-2', 300, 'U/mL', null, 50, 'H'],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Anti-IFN-alpha antibodies',
            'concept_code' => '94504-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive',
            'unit' => null,
            'measured_at' => '2026-09-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Anti-IL-17F antibodies',
            'concept_code' => '94506-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive',
            'unit' => null,
            'measured_at' => '2026-09-15',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Anti-IL-22 antibodies',
            'concept_code' => '94507-8',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive',
            'unit' => null,
            'measured_at' => '2026-09-15',
        ]);

        // ── Observations ────────────────────────────────────────
        // QTc interval
        $this->addObservation($patient, [
            'observation_name' => 'QTc interval',
            'concept_code' => '8634-8',
            'vocabulary' => 'LOINC',
            'value_numeric' => 502,
            'value_text' => 'Prolonged QTc from hypocalcemia',
            'observed_at' => '2024-03-15',
            'category' => 'cardiac',
        ]);

        // Working diagnoses (diagnostic odyssey trail)
        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Recurrent mucocutaneous candidiasis, no primary immunodeficiency',
            'observed_at' => '2023-09-20',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Alopecia areata (isolated)',
            'observed_at' => '2023-05-20',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Isolated autoimmune hypoparathyroidism',
            'observed_at' => '2024-04-20',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Enamel hypoplasia — possible fluorosis vs developmental',
            'observed_at' => '2024-09-15',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Oligoarticular juvenile idiopathic arthritis',
            'observed_at' => '2025-03-25',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Autoimmune hepatitis type 1',
            'observed_at' => '2025-08-15',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'APS-1 / APECED — confirmed (classic triad)',
            'observed_at' => '2026-09-15',
            'category' => 'clinical_assessment',
        ]);

        // DiGeorge FISH
        $this->addObservation($patient, [
            'observation_name' => 'DiGeorge FISH 22q11.2',
            'concept_code' => '40695-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Normal',
            'observed_at' => '2024-04-18',
            'category' => 'genetic_testing',
        ]);

        // HLA typing
        $this->addObservation($patient, [
            'observation_name' => 'HLA-DRB1 typing',
            'concept_code' => '13303-2',
            'vocabulary' => 'LOINC',
            'value_text' => 'HLA-DRB1*04:04 (autoimmune hepatitis risk in APS-1)',
            'observed_at' => '2026-09-15',
            'category' => 'genetic_testing',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2025-03-18',
            'description' => 'Knee ultrasound bilateral — bilateral suprapatellar effusions with synovial thickening. No Baker cyst. No bony erosion.',
            'body_part' => 'Bilateral knees',
            'num_series' => 1,
            'num_instances' => 20,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2025-07-12',
            'description' => 'Liver ultrasound — mild hepatomegaly (liver span 14cm, upper limit for age), increased echogenicity suggesting parenchymal disease. No focal lesions. Normal bile ducts. Normal portal vein flow.',
            'body_part' => 'Abdomen',
            'num_series' => 1,
            'num_instances' => 30,
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'AIRE',
            'variant' => 'p.Arg257Ter',
            'hgvs_c' => 'c.769C>T',
            'variant_type' => 'SNV',
            'chromosome' => 'chr21',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'diagnostic',
            'sample_type' => 'peripheral blood',
            'reported_at' => '2026-09-15',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'AIRE',
            'variant' => 'p.Leu323fsX372',
            'hgvs_c' => 'c.967_979del13',
            'variant_type' => 'indel',
            'chromosome' => 'chr21',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'diagnostic',
            'sample_type' => 'peripheral blood',
            'reported_at' => '2026-09-15',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'condition_name' => 'Candidiasis era',
            'era_start' => '2023-03-01',
            'era_end' => null,
            'occurrence_count' => 8,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Hypoparathyroidism era',
            'era_start' => '2024-03-01',
            'era_end' => null,
            'occurrence_count' => 6,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Autoimmune hepatitis era',
            'era_start' => '2025-07-01',
            'era_end' => null,
            'occurrence_count' => 4,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Adrenal insufficiency era',
            'era_start' => '2026-06-01',
            'era_end' => null,
            'occurrence_count' => 2,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Arthritis era',
            'era_start' => '2025-03-01',
            'era_end' => '2026-09-01',
            'occurrence_count' => 4,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Fluconazole',
            'era_start' => '2023-09-01',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Calcium + Calcitriol',
            'era_start' => '2024-03-20',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Azathioprine',
            'era_start' => '2025-08-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Hydrocortisone + Fludrocortisone',
            'era_start' => '2026-06-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
