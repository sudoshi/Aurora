<?php

namespace Database\Seeders\DemoPatients;

class UndiagnosedPatient2_VEXAS
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-UD-002',
            'first_name' => 'Gerald',
            'last_name' => 'Kowalczyk',
            'date_of_birth' => '1959-01-18',
            'sex' => 'Male',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-GK-22918');
        $this->addIdentifier($patient, 'hospital_mrn', 'UNI-667234', 'University Hospital');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'VEXAS syndrome',
            'concept_code' => 'D89.89',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2026-06-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Polymyalgia rheumatica',
            'concept_code' => 'M35.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2023-07-01',
            'resolution_date' => '2026-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Sweet syndrome (acute febrile neutrophilic dermatosis)',
            'concept_code' => 'L98.2',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2023-10-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Myelodysplastic syndrome, unclassifiable',
            'concept_code' => 'D46.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2024-01-01',
            'resolution_date' => '2026-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Relapsing polychondritis',
            'concept_code' => 'M94.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-04-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Unprovoked DVT left lower extremity',
            'concept_code' => 'I82.402',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-08-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Bilateral episcleritis',
            'concept_code' => 'H15.10',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-02-01',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Anterior uveitis',
            'concept_code' => 'H20.00',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-02-01',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Progressive interstitial lung disease',
            'concept_code' => 'J84.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Sensorineural hearing loss bilateral',
            'concept_code' => 'H90.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-04-01',
            'laterality' => 'bilateral',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Prednisone',
            'concept_code' => '8640',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 40,
            'dose_unit' => 'mg',
            'frequency' => 'once daily with taper',
            'start_date' => '2023-07-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Methotrexate',
            'concept_code' => '6851',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 15,
            'dose_unit' => 'mg',
            'frequency' => 'weekly',
            'start_date' => '2024-04-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Doxycycline',
            'concept_code' => '3640',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 100,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2023-10-20',
            'end_date' => '2024-02-01',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Rivaroxaban',
            'concept_code' => '1114195',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 20,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2024-08-20',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Folic acid',
            'concept_code' => '4511',
            'vocabulary' => 'RxNorm',
            'route' => 'PO',
            'dose_value' => 1,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2024-04-15',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Skin biopsy trunk',
            'concept_code' => '11102',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2023-10-10',
            'performer' => 'Dermatology',
            'body_site' => 'Trunk',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Bone marrow biopsy',
            'concept_code' => '38221',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2024-01-15',
            'performer' => 'Hematology',
            'body_site' => 'Posterior iliac crest',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Audiometry',
            'concept_code' => '92557',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2024-04-20',
            'performer' => 'ENT',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Bronchoalveolar lavage (BAL)',
            'concept_code' => '31624',
            'vocabulary' => 'CPT',
            'domain' => 'diagnostic',
            'performed_date' => '2025-06-15',
            'performer' => 'Pulmonology',
            'body_site' => 'Lungs',
        ]);

        // ── Visits (diagnostic odyssey ~3 years) ────────────────
        // Month 0: PCP
        $visitPcp0 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-07-01',
            'discharge_date' => '2023-07-01',
            'attending_provider' => 'Dr. Patricia Nowak',
            'department' => 'Primary Care',
        ]);

        // Month 1: Rheumatology #1
        $visitRheum1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-08-01',
            'discharge_date' => '2023-08-01',
            'attending_provider' => 'Dr. Sandra Ling',
            'department' => 'Rheumatology',
        ]);

        // Month 3: Dermatology
        $visitDerm3 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-10-10',
            'discharge_date' => '2023-10-10',
            'attending_provider' => 'Dr. David Reeves',
            'department' => 'Dermatology',
        ]);

        // Month 3b: Dermatology pathology follow-up
        $visitDermPath = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2023-10-15',
            'discharge_date' => '2023-10-15',
            'attending_provider' => 'Dr. David Reeves',
            'department' => 'Dermatology',
        ]);

        // Month 6: Hematology — bone marrow biopsy
        $visitHeme6 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-01-15',
            'discharge_date' => '2024-01-15',
            'attending_provider' => 'Dr. Michael Torres',
            'department' => 'Hematology',
        ]);

        // Month 6b: Hematology bone marrow results
        $visitHemeResult = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-01-20',
            'discharge_date' => '2024-01-20',
            'attending_provider' => 'Dr. Michael Torres',
            'department' => 'Hematology',
        ]);

        // Month 9: Rheumatology #2 — relapsing polychondritis
        $visitRheum2 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-04-15',
            'discharge_date' => '2024-04-15',
            'attending_provider' => 'Dr. Sandra Ling',
            'department' => 'Rheumatology',
        ]);

        // Month 9b: ENT — audiometry
        $visitEnt = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-04-20',
            'discharge_date' => '2024-04-20',
            'attending_provider' => 'Dr. Robert Kim',
            'department' => 'ENT',
        ]);

        // Month 13: Vascular/Hematology — DVT
        $visitDvt = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2024-08-18',
            'discharge_date' => '2024-08-22',
            'attending_provider' => 'Dr. Michael Torres',
            'department' => 'Hematology',
        ]);

        // Month 19: Ophthalmology
        $visitOphtho = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2025-02-10',
            'discharge_date' => '2025-02-10',
            'attending_provider' => 'Dr. Angela Park',
            'department' => 'Ophthalmology',
        ]);

        // Month 23: Pulmonology
        $visitPulm = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2025-06-10',
            'discharge_date' => '2025-06-10',
            'attending_provider' => 'Dr. James Fletcher',
            'department' => 'Pulmonology',
        ]);

        // Month 23b: Pulmonology BAL
        $visitBal = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'Community Medical Center',
            'admission_date' => '2025-06-15',
            'discharge_date' => '2025-06-15',
            'attending_provider' => 'Dr. James Fletcher',
            'department' => 'Pulmonology',
        ]);

        // Month 35: Academic hematology 2nd opinion
        $visitAcademic = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Hospital — Hematology Center',
            'admission_date' => '2026-05-15',
            'discharge_date' => '2026-05-15',
            'attending_provider' => 'Dr. Catherine Hoffman',
            'department' => 'Academic Hematology',
        ]);

        // Month 36: VEXAS diagnosis confirmation
        $visitDx = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Hospital — Hematology Center',
            'admission_date' => '2026-06-01',
            'discharge_date' => '2026-06-01',
            'attending_provider' => 'Dr. Catherine Hoffman',
            'department' => 'Academic Hematology',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $visitPcp0->id,
            'note_type' => 'progress_note',
            'title' => 'PCP Initial Visit — Fever, Skin Nodules, Cytopenias',
            'content' => '64-year-old male presents with 2-week history of recurrent fevers (Tmax 101.5°F), tender erythematous skin nodules on trunk and upper extremities, bilateral ear swelling, diffuse arthralgias predominantly in shoulders and hips, and fatigue. Initial labs reveal pancytopenia: WBC 3.8 (L), Hgb 10.2 (L), MCV 104 (H), Plt 118 (L). Inflammatory markers markedly elevated: ESR 92 mm/hr, CRP 8.4 mg/dL. No recent infections, travel, or new medications. Examination notable for tender erythematous papulonodular lesions on trunk, bilateral auricular edema with erythema, and diffuse proximal joint tenderness without synovitis. Assessment: Systemic inflammatory process with cytopenias — differential includes inflammatory myopathy, vasculitis, myelodysplastic syndrome, adult-onset Still disease. Referred to rheumatology urgently.',
            'author' => 'Dr. Patricia Nowak',
            'authored_at' => '2023-07-01',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheum1->id,
            'note_type' => 'consult_note',
            'title' => 'Rheumatology Consult #1 — PMR with Possible RP Overlap',
            'content' => 'Referred for systemic inflammation with cytopenias. Examination confirms bilateral shoulder and hip girdle tenderness, morning stiffness >1 hour, bilateral auricular chondritis. Labs: ANA 1:80 (weak positive, speckled pattern), RF 18 IU/mL (borderline elevated), anti-CCP <20 (negative), C3 95 (low-normal), C4 18 (normal), ANCA MPO negative, ANCA PR3 negative, Ferritin 680 (markedly elevated), IL-6 42 pg/mL (markedly elevated). Assessment: Clinical presentation is most consistent with polymyalgia rheumatica given proximal girdle pain, elevated ESR/CRP, and dramatic response to corticosteroids. The auricular swelling raises concern for possible relapsing polychondritis overlap, though currently mild and may represent steroid-responsive chondritis. Started prednisone 40mg daily with taper plan. Will monitor for additional chondritis features. The cytopenias are somewhat atypical for PMR and warrant hematology evaluation if persistent.',
            'author' => 'Dr. Sandra Ling',
            'authored_at' => '2023-08-01',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDermPath->id,
            'note_type' => 'procedure_note',
            'title' => 'Dermatology Skin Biopsy Pathology — Sweet Syndrome',
            'content' => 'PATHOLOGY REPORT: Punch biopsy, trunk. GROSS: 4mm punch biopsy of erythematous papulonodule. MICROSCOPIC: Dense dermal neutrophilic infiltrate with leukocytoclasia, marked papillary dermal edema. No evidence of vasculitis — vessel walls intact, no fibrinoid necrosis. No granulomas. No organisms on special stains (GMS, PAS negative). Epidermis unremarkable. DIAGNOSIS: Acute febrile neutrophilic dermatosis (Sweet syndrome). COMMENT: Sweet syndrome may be idiopathic, drug-induced, or associated with underlying malignancy (particularly hematologic) or autoimmune disease. Recommend evaluation for underlying hematologic malignancy given concurrent cytopenias.',
            'author' => 'Dr. David Reeves',
            'authored_at' => '2023-10-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitHemeResult->id,
            'note_type' => 'procedure_note',
            'title' => 'Bone Marrow Biopsy Pathology — MDS Unclassifiable',
            'content' => 'PATHOLOGY REPORT: Bone marrow biopsy and aspirate, posterior iliac crest. GROSS: Adequate core biopsy and aspirate. MICROSCOPIC: Hypercellular marrow (70% cellularity, expected 30-40% for age). M:E ratio 4:1. Mild erythroid dysplasia with occasional nuclear budding and irregular nuclear contours. Prominent cytoplasmic vacuoles in myeloid AND erythroid precursors — noted as diffuse finding. Blasts <3% by aspirate differential. Ring sideroblasts 8% on iron stain (below 15% threshold for MDS-RS). Megakaryocytes adequate, no significant dysplasia. CYTOGENETICS: Normal 46,XY. No clonal abnormalities detected. FISH panel for MDS: negative for del(5q), -7/del(7q), trisomy 8, del(20q). FLOW CYTOMETRY: Blasts 2%, no aberrant phenotype. ASSESSMENT: Myelodysplastic syndrome, unclassifiable. IPSS-R score 2.5 (low-risk). NOTE: The prominent cytoplasmic vacuolization in myeloid and erythroid precursors is an unusual finding. This may reflect nutritional deficiency (B12/folate — check levels), drug effect, or metabolic stress. Clinical correlation recommended.',
            'author' => 'Dr. Michael Torres',
            'authored_at' => '2024-01-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheum2->id,
            'note_type' => 'consult_note',
            'title' => 'Rheumatology #2 — Relapsing Polychondritis Diagnosed',
            'content' => 'Return visit for progressive chondritis symptoms. Now with bilateral auricular chondritis (cauliflower ear deformity developing), nasal chondritis with early saddle nose deformity, and bilateral sensorineural hearing loss confirmed by ENT audiometry. Patient has been unable to taper prednisone below 20mg without severe flares of fever, skin lesions, and joint symptoms (steroid-dependent). Assessment: Meets McAdam criteria for relapsing polychondritis (bilateral auricular chondritis, nasal chondritis, audiovestibular damage). Adding methotrexate 15mg PO weekly as steroid-sparing agent with folic acid 1mg daily. The combination of PMR, Sweet syndrome, MDS, and now relapsing polychondritis in the same patient is highly unusual. These may represent separate comorbidities, but the possibility of an overarching unifying diagnosis should be considered. Unfortunately, no single diagnosis readily explains all of these features.',
            'author' => 'Dr. Sandra Ling',
            'authored_at' => '2024-04-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDvt->id,
            'note_type' => 'progress_note',
            'title' => 'Hematology — Unprovoked DVT with Negative Thrombophilia Workup',
            'content' => 'Admitted for left lower extremity swelling and pain. LE duplex confirms acute popliteal vein DVT, left leg. CT pulmonary angiography negative for pulmonary embolism but incidentally shows bilateral ground-glass opacities in lower lobes. Comprehensive thrombophilia workup: D-dimer 4.2 (H), Factor V Leiden negative, Prothrombin G20210A negative, Antithrombin III normal, Protein C normal, Protein S normal, antiphospholipid antibodies (anticardiolipin IgG/IgM, anti-beta-2-glycoprotein I IgG/IgM, lupus anticoagulant) all negative. Started rivaroxaban 20mg daily. Assessment: Unprovoked DVT in setting of MDS and systemic inflammation. No identifiable thrombophilia. The combination of venous thromboembolism with systemic inflammation and cytopenias is concerning — consider paraneoplastic process.',
            'author' => 'Dr. Michael Torres',
            'authored_at' => '2024-08-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitBal->id,
            'note_type' => 'procedure_note',
            'title' => 'Pulmonology BAL — Neutrophilic Alveolitis',
            'content' => 'Bronchoalveolar lavage performed for progressive dyspnea and worsening bilateral GGOs on chest CT. PFTs: FVC 72% predicted, DLCO 58% predicted — moderately reduced. BAL cell count differential: 62% neutrophils (markedly elevated, normal <3%), 28% macrophages, 8% lymphocytes, 2% eosinophils. All bacterial, fungal, and mycobacterial cultures negative. Cytology negative for malignancy. No hemosiderin-laden macrophages. Assessment: Neutrophilic alveolitis without infectious etiology. In context of progressive ILD with neutrophil-predominant BAL, consider drug-induced pneumonitis (methotrexate), Sweet syndrome of the lung (pulmonary neutrophilic infiltrates), or ILD associated with MDS/autoimmune process. Recommend pulmonary-rheumatology conference.',
            'author' => 'Dr. James Fletcher',
            'authored_at' => '2025-06-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitAcademic->id,
            'note_type' => 'consult_note',
            'title' => 'Academic Hematology 2nd Opinion — VEXAS Suspected',
            'content' => 'ACADEMIC CENTER REVIEW: 66-year-old male referred with 3-year history of steroid-dependent systemic inflammation with accumulating diagnoses: PMR, Sweet syndrome, MDS, relapsing polychondritis, unprovoked DVT, episcleritis/uveitis, progressive ILD, sensorineural hearing loss. Currently on prednisone 20mg (unable to taper further), methotrexate 15mg weekly, rivaroxaban. CRITICAL REVIEW OF BONE MARROW: Re-reviewed the 2024-01-15 bone marrow biopsy slides. The STRIKING cytoplasmic vacuolization in myeloid and erythroid precursors is NOT consistent with nutritional deficiency (B12/folate were normal) or drug effect. This vacuolization pattern is DIAGNOSTIC for VEXAS syndrome (Vacuoles, E1 enzyme, X-linked, Autoinflammatory, Somatic). VEXAS is caused by somatic mutations in UBA1, the major E1 ubiquitin-activating enzyme. It unifies ALL of this patient\'s diagnoses: the MDS, Sweet syndrome, polychondritis, DVT, ocular inflammation, ILD, and hearing loss are all manifestations of a single disease. UBA1 sequencing ordered on peripheral blood.',
            'author' => 'Dr. Catherine Hoffman',
            'authored_at' => '2026-05-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDx->id,
            'note_type' => 'progress_note',
            'title' => 'UBA1 Mutation Confirmed — VEXAS Syndrome Diagnosis',
            'content' => 'UBA1 SEQUENCING RESULT: Pathogenic somatic mutation detected — UBA1 p.Met41Thr (c.122T>C), variant allele frequency (VAF) 62%. This confirms the diagnosis of VEXAS syndrome. INTERPRETATION: The p.Met41Thr mutation at the catalytic cysteine-adjacent methionine 41 residue abolishes the cytoplasmic UBA1b isoform, leading to defective ubiquitin-activating enzyme activity and the characteristic unfolded protein response. The high VAF (62%) indicates a large mutant clone, consistent with the severe phenotype. KEY INSIGHT: The bone marrow vacuoles documented in January 2024 were the diagnostic clue. Had UBA1 testing been available or suspected at that time, diagnosis could have been made 2.5 years earlier. All prior diagnoses (PMR, Sweet syndrome, MDS, RP) are now reclassified as manifestations of VEXAS. Myeloid NGS panel was also performed: no mutations detected in ASXL1, TET2, DNMT3A, SF3B1, or other myeloid genes. Treatment discussion: azacitidine for disease modification, JAK inhibitor for inflammation control, allogeneic stem cell transplant evaluation given high VAF.',
            'author' => 'Dr. Catherine Hoffman',
            'authored_at' => '2026-06-01',
        ]);

        // ── Lab Panels ──────────────────────────────────────────
        // Month 0 PCP (2023-07-01)
        $this->addLabPanel($patient, '2023-07-01', [
            ['ESR', '30341-2', 92, 'mm/hr', 0, 20, 'H'],
            ['CRP', '1988-5', 8.4, 'mg/dL', null, 0.5, 'H'],
            ['WBC', '6690-2', 3.8, 'x10^3/uL', 4.5, 11.0, 'L'],
            ['Hemoglobin', '718-7', 10.2, 'g/dL', 13.5, 17.5, 'L'],
            ['MCV', '787-2', 104, 'fL', 80, 100, 'H'],
            ['Platelets', '777-3', 118, 'x10^3/uL', 150, 400, 'L'],
        ]);

        // Month 1 Rheum (2023-08-01)
        $this->addMeasurement($patient, [
            'measurement_name' => 'ANA',
            'concept_code' => '8061-4',
            'vocabulary' => 'LOINC',
            'value_text' => '1:80 speckled (weak positive)',
            'unit' => null,
            'measured_at' => '2023-08-01',
        ]);
        $this->addLabPanel($patient, '2023-08-01', [
            ['Rheumatoid factor', '11572-5', 18, 'IU/mL', null, 14, 'H'],
            ['Anti-CCP', '53027-9', 15, 'U/mL', null, 20, null],
            ['Complement C3', '4485-9', 95, 'mg/dL', 90, 180, null],
            ['Complement C4', '4498-2', 18, 'mg/dL', 10, 40, null],
            ['Ferritin', '2276-4', 680, 'ng/mL', 24, 336, 'H'],
            ['IL-6', '26881-3', 42, 'pg/mL', null, 7, 'H'],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'ANCA MPO',
            'concept_code' => '21419-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2023-08-01',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'ANCA PR3',
            'concept_code' => '21418-2',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2023-08-01',
        ]);

        // Month 6 Heme (2024-01-15)
        $this->addLabPanel($patient, '2024-01-15', [
            ['WBC', '6690-2', 3.5, 'x10^3/uL', 4.5, 11.0, 'L'],
            ['Hemoglobin', '718-7', 9.4, 'g/dL', 13.5, 17.5, 'L'],
            ['MCV', '787-2', 106, 'fL', 80, 100, 'H'],
            ['Platelets', '777-3', 102, 'x10^3/uL', 150, 400, 'L'],
            ['Reticulocytes', '4679-7', 1.0, '%', 0.5, 2.5, null],
            ['Vitamin B12', '2132-9', 580, 'pg/mL', 200, 900, null],
            ['Folate', '2284-8', 14.2, 'ng/mL', 3.0, 20.0, null],
            ['LDH', '2532-0', 280, 'U/L', 140, 280, null],
            ['Haptoglobin', '4542-7', 85, 'mg/dL', 30, 200, null],
            ['Ferritin', '2276-4', 720, 'ng/mL', 24, 336, 'H'],
            ['Serum iron', '2498-4', 42, 'mcg/dL', 60, 170, 'L'],
            ['TIBC', '2500-7', 220, 'mcg/dL', 250, 370, 'L'],
        ]);

        // Month 14 DVT (2024-08-20)
        $this->addLabPanel($patient, '2024-08-20', [
            ['D-dimer', '48066-5', 4.2, 'mcg/mL FEU', null, 0.5, 'H'],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Factor V Leiden',
            'concept_code' => '21668-2',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2024-08-20',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Prothrombin G20210A',
            'concept_code' => '24475-6',
            'vocabulary' => 'LOINC',
            'value_text' => 'Negative',
            'unit' => null,
            'measured_at' => '2024-08-20',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Antithrombin III',
            'concept_code' => '3174-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Normal',
            'unit' => null,
            'measured_at' => '2024-08-20',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Protein C',
            'concept_code' => '27820-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'Normal',
            'unit' => null,
            'measured_at' => '2024-08-20',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Protein S',
            'concept_code' => '27821-8',
            'vocabulary' => 'LOINC',
            'value_text' => 'Normal',
            'unit' => null,
            'measured_at' => '2024-08-20',
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Antiphospholipid antibodies',
            'concept_code' => '53977-5',
            'vocabulary' => 'LOINC',
            'value_text' => 'All negative (aCL IgG/IgM, anti-B2GP1 IgG/IgM, lupus anticoagulant)',
            'unit' => null,
            'measured_at' => '2024-08-20',
        ]);

        // Month 30 Dx (2026-06-01)
        $this->addMeasurement($patient, [
            'measurement_name' => 'UBA1 sequencing',
            'concept_code' => '101263-2',
            'vocabulary' => 'LOINC',
            'value_text' => 'p.Met41Thr (c.122T>C), VAF 62%',
            'unit' => null,
            'measured_at' => '2026-06-01',
        ]);

        // ── Observations ────────────────────────────────────────
        // Working diagnoses (diagnostic odyssey trail)
        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Polymyalgia rheumatica with possible relapsing polychondritis overlap',
            'observed_at' => '2023-08-01',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Sweet syndrome (acute febrile neutrophilic dermatosis)',
            'observed_at' => '2023-10-15',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'MDS, unclassifiable (low-risk IPSS-R 2.5)',
            'observed_at' => '2024-01-20',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'Relapsing polychondritis (McAdam criteria met)',
            'observed_at' => '2024-04-20',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Working diagnosis',
            'concept_code' => '29308-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'VEXAS syndrome — confirmed',
            'observed_at' => '2026-06-01',
            'category' => 'clinical_assessment',
        ]);

        // Pulmonary function tests
        $this->addObservation($patient, [
            'observation_name' => 'FVC',
            'concept_code' => '19868-9',
            'vocabulary' => 'LOINC',
            'value_numeric' => 72,
            'value_text' => '72% predicted',
            'observed_at' => '2025-06-15',
            'category' => 'pulmonary_function',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'DLCO',
            'concept_code' => '19911-7',
            'vocabulary' => 'LOINC',
            'value_numeric' => 58,
            'value_text' => '58% predicted',
            'observed_at' => '2025-06-15',
            'category' => 'pulmonary_function',
        ]);

        // Vital signs
        $this->addObservation($patient, [
            'observation_name' => 'Temperature',
            'concept_code' => '8310-5',
            'vocabulary' => 'LOINC',
            'value_numeric' => 101.5,
            'value_text' => '101.5°F',
            'observed_at' => '2023-07-01',
            'category' => 'vital_signs',
        ]);

        // Myeloid NGS panel observation
        $this->addObservation($patient, [
            'observation_name' => 'Myeloid NGS panel',
            'concept_code' => '69048-4',
            'vocabulary' => 'LOINC',
            'value_text' => 'No mutations detected (ASXL1, TET2, DNMT3A, SF3B1 all wild-type)',
            'observed_at' => '2026-06-01',
            'category' => 'genomic',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2024-08-18',
            'description' => 'LE duplex left — acute popliteal vein DVT, left lower extremity. Non-compressible popliteal vein with echogenic thrombus. Normal femoral and tibial veins.',
            'body_part' => 'Left lower extremity',
            'num_series' => 1,
            'num_instances' => 24,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-08-18',
            'description' => 'CT pulmonary angiography — no pulmonary embolism. Incidental bilateral ground-glass opacities in lower lobes. No pleural effusion. Mediastinal lymph nodes not enlarged.',
            'body_part' => 'Chest',
            'num_series' => 2,
            'num_instances' => 300,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-04-18',
            'description' => 'CT sinuses — nasal septal cartilage thinning with early saddle deformity. Mild mucosal thickening bilateral maxillary sinuses. No destructive lesion.',
            'body_part' => 'Sinuses',
            'num_series' => 1,
            'num_instances' => 80,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2025-06-10',
            'description' => 'CT chest follow-up — progressive bilateral ground-glass opacities increased from prior study. New small bilateral pleural effusions. No lymphadenopathy. No pulmonary embolism.',
            'body_part' => 'Chest',
            'num_series' => 2,
            'num_instances' => 280,
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'UBA1',
            'variant' => 'p.Met41Thr',
            'hgvs_c' => 'c.122T>C',
            'variant_type' => 'SNV',
            'chromosome' => 'chrX',
            'zygosity' => 'hemizygous',
            'allele_frequency' => 0.62,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'emerging_therapies',
            'sample_type' => 'peripheral blood',
            'reported_at' => '2026-06-01',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'condition_name' => 'Systemic inflammation era',
            'era_start' => '2023-07-01',
            'era_end' => null,
            'occurrence_count' => 20,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Cytopenias era',
            'era_start' => '2023-07-01',
            'era_end' => null,
            'occurrence_count' => 12,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Chondritis era',
            'era_start' => '2024-04-01',
            'era_end' => null,
            'occurrence_count' => 6,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'DVT era',
            'era_start' => '2024-08-01',
            'era_end' => null,
            'occurrence_count' => 3,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Prednisone',
            'era_start' => '2023-07-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Methotrexate',
            'era_start' => '2024-04-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Rivaroxaban',
            'era_start' => '2024-08-20',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
