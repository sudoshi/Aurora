<?php

namespace Database\Seeders\DemoPatients;

class RareDiseasePatient3_CAPS
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-RD-003',
            'first_name' => 'Ananya',
            'last_name' => 'Patel',
            'date_of_birth' => '1992-04-22',
            'sex' => 'Female',
            'race' => 'Asian',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-AP-33891');
        $this->addIdentifier($patient, 'hospital_mrn', 'UH-445672', 'University Hospital');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Antiphospholipid syndrome',
            'concept_code' => 'D68.61',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2019-04-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Recurrent pregnancy loss',
            'concept_code' => 'N96',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2018-04-01',
            'resolution_date' => '2019-10-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Deep vein thrombosis bilateral',
            'concept_code' => 'I82.40',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2022-04-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Pulmonary embolism bilateral',
            'concept_code' => 'I26.99',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2026-04-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'APS nephropathy / thrombotic microangiopathy',
            'concept_code' => 'N17.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2025-04-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic kidney disease stage 3b',
            'concept_code' => 'N18.32',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2026-05-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Acute respiratory distress syndrome',
            'concept_code' => 'J80',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'resolved',
            'onset_date' => '2026-04-02',
            'resolution_date' => '2026-04-10',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hepatic ischemia',
            'concept_code' => 'K76.89',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'resolved',
            'onset_date' => '2026-04-02',
            'resolution_date' => '2026-04-14',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Digital gangrene right hand',
            'concept_code' => 'I73.01',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2026-04-02',
            'laterality' => 'right',
            'body_site' => 'Hand',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Livedo reticularis',
            'concept_code' => 'R23.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2023-04-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Transient ischemic attack',
            'concept_code' => 'G45.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'resolved',
            'onset_date' => '2024-04-01',
            'resolution_date' => '2024-04-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Aspirin',
            'concept_code' => 'B01AC06',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 81,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2019-10-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Warfarin',
            'concept_code' => 'B01AA03',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 3,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2022-04-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Hydroxychloroquine',
            'concept_code' => 'P01BA02',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 200,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'start_date' => '2025-04-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Lisinopril',
            'concept_code' => 'C09AA03',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 20,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2025-04-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Rituximab',
            'concept_code' => 'L01FA01',
            'vocabulary' => 'ATC',
            'route' => 'intravenous',
            'dose_value' => 1000,
            'dose_unit' => 'mg',
            'frequency' => 'every 6 months',
            'start_date' => '2026-05-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Enoxaparin',
            'concept_code' => 'B01AB05',
            'vocabulary' => 'ATC',
            'route' => 'subcutaneous',
            'dose_value' => 40,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2021-01-01',
            'end_date' => '2021-09-01',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Methylprednisolone',
            'concept_code' => 'H02AB04',
            'vocabulary' => 'ATC',
            'route' => 'intravenous',
            'dose_value' => 1000,
            'dose_unit' => 'mg',
            'frequency' => 'daily x3 days',
            'start_date' => '2026-04-02',
            'end_date' => '2026-04-04',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'IVIG',
            'concept_code' => 'J06BA02',
            'vocabulary' => 'ATC',
            'route' => 'intravenous',
            'dose_value' => 0.4,
            'dose_unit' => 'g/kg/day',
            'frequency' => 'daily x5 days',
            'start_date' => '2026-04-04',
            'end_date' => '2026-04-08',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Rituximab (CAPS acute)',
            'concept_code' => 'L01FA01',
            'vocabulary' => 'ATC',
            'route' => 'intravenous',
            'dose_value' => 375,
            'dose_unit' => 'mg/m²',
            'frequency' => 'weekly x2',
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-19',
            'status' => 'completed',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Therapeutic plasma exchange x5',
            'concept_code' => '36514',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2026-04-02',
            'performer' => 'ICU',
            'notes' => 'Five sessions over 2026-04-02 to 2026-04-06. Emergent TPE for catastrophic APS with multiorgan failure.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Intermittent hemodialysis x8',
            'concept_code' => '90935',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2026-04-03',
            'performer' => 'Nephrology',
            'notes' => 'Eight sessions over 2026-04-03 to 2026-04-18. AKI from renal TMA requiring intermittent HD.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Endotracheal intubation',
            'concept_code' => '31500',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2026-04-02',
            'performer' => 'ICU',
            'notes' => 'Emergent intubation for ARDS secondary to CAPS. P/F ratio 110.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Renal biopsy',
            'concept_code' => '50200',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2025-04-10',
            'performer' => 'Nephrology',
            'body_site' => 'Kidney',
            'notes' => 'Percutaneous renal biopsy showing thrombotic microangiopathy consistent with APS nephropathy.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Skin biopsy',
            'concept_code' => '11102',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2023-04-15',
            'performer' => 'Dermatology',
            'body_site' => 'Lower extremity',
            'notes' => 'Punch biopsy of livedo reticularis showing thrombotic vasculopathy consistent with APS.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Partial amputation right 2nd finger',
            'concept_code' => '26910',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2026-04-21',
            'performer' => 'Hand Surgery',
            'laterality' => 'right',
            'notes' => 'Partial amputation of right 2nd digit at DIP joint for irreversible digital gangrene secondary to CAPS.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Cesarean section',
            'concept_code' => '59510',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2021-08-20',
            'performer' => 'OB/GYN',
            'notes' => 'Planned cesarean at 36 weeks for APS-managed pregnancy. Healthy male infant, 2.4kg. Enoxaparin bridged perioperatively.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Dilation and curettage',
            'concept_code' => '59812',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2018-10-15',
            'performer' => 'OB/GYN',
            'notes' => 'D&C following first pregnancy loss at 14 weeks. Placental pathology showed extensive villous infarction.',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $facility = 'University Hospital';

        // OB/GYN — pregnancy losses
        $visitOB1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2018-06-15',
            'discharge_date' => '2018-06-15',
            'attending_provider' => 'Dr. Priya Sharma',
            'department' => 'OB/GYN',
        ]);

        $visitOB2 = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2018-10-14',
            'discharge_date' => '2018-10-16',
            'attending_provider' => 'Dr. Priya Sharma',
            'department' => 'OB/GYN',
        ]);

        $visitOB3 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2019-04-08',
            'discharge_date' => '2019-04-08',
            'attending_provider' => 'Dr. Priya Sharma',
            'department' => 'OB/GYN',
        ]);

        $visitOB4 = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2021-08-20',
            'discharge_date' => '2021-08-23',
            'attending_provider' => 'Dr. Priya Sharma',
            'department' => 'OB/GYN',
        ]);

        // Rheumatology — APS diagnosis and follow-up
        $visitRheum1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2019-04-15',
            'discharge_date' => '2019-04-15',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $visitRheum2 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2019-10-15',
            'discharge_date' => '2019-10-15',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2020-04-15',
            'discharge_date' => '2020-04-15',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2021-04-15',
            'discharge_date' => '2021-04-15',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2023-04-10',
            'discharge_date' => '2023-04-10',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2024-04-10',
            'discharge_date' => '2024-04-10',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $visitRheumPostCAPS = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2026-06-15',
            'discharge_date' => '2026-06-15',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        $visitRheumFU = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2026-10-15',
            'discharge_date' => '2026-10-15',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        // Hematology — anticoagulation
        $visitHem1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2022-04-12',
            'discharge_date' => '2022-04-12',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Hematology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2022-10-15',
            'discharge_date' => '2022-10-15',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Hematology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2023-10-15',
            'discharge_date' => '2023-10-15',
            'attending_provider' => 'Dr. Karen Liu',
            'department' => 'Hematology',
        ]);

        // Dermatology — livedo
        $visitDerm = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2023-04-15',
            'discharge_date' => '2023-04-15',
            'attending_provider' => 'Dr. Maya Singh',
            'department' => 'Dermatology',
        ]);

        // Neurology — TIA
        $visitNeuro = $this->addVisit($patient, [
            'visit_type' => 'emergency',
            'facility' => $facility,
            'admission_date' => '2024-04-15',
            'discharge_date' => '2024-04-16',
            'attending_provider' => 'Dr. James Park',
            'department' => 'Neurology',
        ]);

        // Nephrology — proteinuria, renal biopsy, HD
        $visitNeph1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2025-04-05',
            'discharge_date' => '2025-04-05',
            'attending_provider' => 'Dr. Fatima Al-Hassan',
            'department' => 'Nephrology',
        ]);

        $visitNephBiopsy = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2025-04-10',
            'discharge_date' => '2025-04-12',
            'attending_provider' => 'Dr. Fatima Al-Hassan',
            'department' => 'Nephrology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2025-10-15',
            'discharge_date' => '2025-10-15',
            'attending_provider' => 'Dr. Fatima Al-Hassan',
            'department' => 'Nephrology',
        ]);

        $visitNephPostCAPS = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2026-06-01',
            'discharge_date' => '2026-06-01',
            'attending_provider' => 'Dr. Fatima Al-Hassan',
            'department' => 'Nephrology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2026-09-15',
            'discharge_date' => '2026-09-15',
            'attending_provider' => 'Dr. Fatima Al-Hassan',
            'department' => 'Nephrology',
        ]);

        // ED / ICU — CAPS event (inpatient cluster)
        $visitED = $this->addVisit($patient, [
            'visit_type' => 'emergency',
            'facility' => $facility,
            'admission_date' => '2026-04-01',
            'discharge_date' => '2026-04-01',
            'attending_provider' => 'Dr. Robert Chen',
            'department' => 'Emergency Medicine',
        ]);

        $visitICU = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2026-04-01',
            'discharge_date' => '2026-04-10',
            'attending_provider' => 'Dr. Sarah Mitchell',
            'department' => 'Medical ICU',
        ]);

        $visitInpatientStep = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2026-04-10',
            'discharge_date' => '2026-04-21',
            'attending_provider' => 'Dr. Naveen Reddy',
            'department' => 'Rheumatology',
        ]);

        // Hand surgery
        $visitHandSurg = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2026-04-21',
            'discharge_date' => '2026-04-23',
            'attending_provider' => 'Dr. Thomas Wright',
            'department' => 'Hand Surgery',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $visitOB2->id,
            'note_type' => 'pathology_report',
            'title' => 'Placental pathology — first pregnancy loss',
            'content' => 'Products of conception from 14-week IUFD. Placenta shows extensive villous infarction involving >60% of parenchyma with decidual vasculopathy. Intervillous fibrin deposition and perivillous fibrinoid necrosis. Findings suggest underlying thrombophilia or autoimmune etiology. Correlation with antiphospholipid antibody testing recommended.',
            'author' => 'Dr. Linda Foster',
            'authored_at' => '2018-10-18',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitOB3->id,
            'note_type' => 'pathology_report',
            'title' => 'Placental pathology — second pregnancy loss',
            'content' => 'Products of conception from 10-week IUFD. Small-for-gestational-age placenta with extensive villous infarction, chronic histiocytic intervillositis, and decidual vasculopathy. Pattern identical to prior loss. Highly suspicious for antiphospholipid syndrome. Urgent rheumatology referral placed.',
            'author' => 'Dr. Linda Foster',
            'authored_at' => '2019-04-12',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheum1->id,
            'note_type' => 'consultation',
            'title' => 'Rheumatology — APS diagnosis',
            'content' => 'Twenty-seven-year-old female referred for recurrent pregnancy loss. aPL panel reveals triple positivity: lupus anticoagulant positive, aCL IgG 58 GPL, anti-beta2-GPI IgG 45 U/mL. Meets revised Sapporo criteria for APS with obstetric manifestations. No evidence of SLE. Initiated aspirin 81mg daily. Plan confirmatory repeat aPL at 12 weeks.',
            'author' => 'Dr. Naveen Reddy',
            'authored_at' => '2019-04-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheum2->id,
            'note_type' => 'progress',
            'title' => 'Rheumatology — confirmed triple-positive APS',
            'content' => 'Repeat aPL testing confirms persistent triple positivity at 12 weeks. High-risk aPL profile with LA, aCL IgG, and anti-beta2-GPI all elevated. Started aspirin for secondary prevention. Counseled on high thrombotic risk. Future pregnancies will require enoxaparin plus aspirin from positive test onward.',
            'author' => 'Dr. Naveen Reddy',
            'authored_at' => '2019-10-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDerm->id,
            'note_type' => 'pathology_report',
            'title' => 'Skin biopsy — livedo reticularis',
            'content' => 'Punch biopsy from left lower extremity demonstrates thrombotic vasculopathy of dermal arterioles with intimal hyperplasia and luminal narrowing. No vasculitis. PAS stain negative for vessel wall deposits. Findings consistent with antiphospholipid syndrome-related livedo reticularis.',
            'author' => 'Dr. Maya Singh',
            'authored_at' => '2023-04-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNephBiopsy->id,
            'note_type' => 'pathology_report',
            'title' => 'Renal biopsy — APS nephropathy',
            'content' => 'Core needle biopsy of left kidney. Light microscopy shows thrombotic microangiopathy with fibrin thrombi in glomerular capillaries and arterioles. Interlobular arteries show myxoid intimal hyperplasia. IF negative for immune complex deposition. EM confirms endothelial swelling without subepithelial deposits. Diagnosis: APS-associated thrombotic microangiopathy. No lupus nephritis.',
            'author' => 'Dr. Fatima Al-Hassan',
            'authored_at' => '2025-04-14',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitED->id,
            'note_type' => 'emergency',
            'title' => 'ED presentation — CAPS prodrome',
            'content' => 'Thirty-three-year-old female with known triple-positive APS presents with acute onset bilateral leg swelling, dyspnea, and fever to 39.1C. INR found subtherapeutic at 1.6 (reports missing warfarin doses due to GI illness). Bilateral DVT confirmed on duplex. CT PE protocol ordered. Heparin drip initiated. Admitted to ICU given concern for evolving catastrophic APS.',
            'author' => 'Dr. Robert Chen',
            'authored_at' => '2026-04-01',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitICU->id,
            'note_type' => 'admission',
            'title' => 'ICU admission — catastrophic APS',
            'content' => 'Patient admitted to MICU with rapidly evolving multiorgan failure. Within 24 hours of admission: bilateral PE (CT), bilateral renal infarcts and hepatic infarcts (CT abdomen), ARDS requiring intubation (P/F 110), AKI with Cr rising from 1.1 to 4.2, thrombocytopenia to 42K with schistocytes on smear, LDH 2800. Asherson criteria for catastrophic APS met: involvement of 3+ organ systems, development within days, histopathologic confirmation of small-vessel thrombosis, aPL positive. Triple therapy initiated: plasma exchange, IV methylprednisolone pulse, and IVIG.',
            'author' => 'Dr. Sarah Mitchell',
            'authored_at' => '2026-04-02',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitICU->id,
            'note_type' => 'progress',
            'title' => 'CAPS day 3 — rituximab added',
            'content' => 'Suboptimal response to triple therapy after 48 hours. Plt remains 55K, Cr 3.8, LDH 1800, ongoing mechanical ventilation. Decision to add rituximab 375mg/m2 as rescue therapy per emerging CAPS treatment data. Rheumatology, hematology, and nephrology in agreement. Complement C3/C4 profoundly depressed suggesting complement-mediated injury — CFH variant may be contributing.',
            'author' => 'Dr. Sarah Mitchell',
            'authored_at' => '2026-04-05',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitICU->id,
            'note_type' => 'progress',
            'title' => 'CAPS day 7 — clinical improvement',
            'content' => 'Significant improvement after 5 plasma exchange sessions and rituximab. Extubated successfully on day 5 (P/F 250). Plt recovering to 95K, Cr trending down to 3.0, LDH 800. Transitioned from heparin drip to warfarin with INR target 3.0-4.0. Right 2nd finger shows fixed gangrene — hand surgery consulted. HD tapered to every other day.',
            'author' => 'Dr. Sarah Mitchell',
            'authored_at' => '2026-04-08',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitInpatientStep->id,
            'note_type' => 'progress',
            'title' => 'Step-down unit — ongoing recovery',
            'content' => 'Transferred from ICU on day 10. Renal function slowly recovering (Cr 2.5). Off HD since day 16. Warfarin titrated to INR 3.2. Hydroxychloroquine continued for antithrombotic effect. Right 2nd finger gangrene demarcated — scheduled for partial amputation day 21. Planning rituximab maintenance every 6 months.',
            'author' => 'Dr. Naveen Reddy',
            'authored_at' => '2026-04-14',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitHandSurg->id,
            'note_type' => 'operative',
            'title' => 'Partial amputation right 2nd finger',
            'content' => 'Partial amputation of right 2nd digit at DIP joint under digital block. Dry gangrene had demarcated well. Bone resected at middle phalanx head. Primary closure achieved without tension. Pathology shows complete thrombotic occlusion of digital arteries. Warfarin held perioperatively with heparin bridge.',
            'author' => 'Dr. Thomas Wright',
            'authored_at' => '2026-04-21',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitHandSurg->id,
            'note_type' => 'discharge_summary',
            'title' => 'Discharge summary — catastrophic APS',
            'content' => 'Twenty-one-day hospitalization for catastrophic APS with multiorgan failure (lungs, kidneys, liver, digits). Triggered by subtherapeutic anticoagulation during GI illness. Treated with plasma exchange x5, methylprednisolone pulse, IVIG x5 days, rituximab x2 doses, intermittent HD x8 sessions. Partial amputation right 2nd finger for irreversible gangrene. Discharge on warfarin (INR target 3.0-4.0), aspirin 81mg, hydroxychloroquine 200mg BID, lisinopril 20mg. Rituximab maintenance every 6 months. CKD stage 3b at discharge (Cr 2.1). Close rheumatology and nephrology follow-up arranged.',
            'author' => 'Dr. Naveen Reddy',
            'authored_at' => '2026-04-21',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheumPostCAPS->id,
            'note_type' => 'progress',
            'title' => 'Post-CAPS follow-up — stable',
            'content' => 'Six weeks post-discharge from catastrophic APS. Clinically stable on warfarin with INR 3.4 at target. Cr stable at 1.8. No new thrombotic events. Rituximab maintenance dose administered. Finger amputation site healed well. Continued livedo reticularis on lower extremities. Plan: continue current regimen, renal function monitoring, repeat aPL panel.',
            'author' => 'Dr. Naveen Reddy',
            'authored_at' => '2026-06-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitRheumFU->id,
            'note_type' => 'progress',
            'title' => 'Rheumatology 6-month follow-up',
            'content' => 'Six months post-CAPS. Remains triple-positive but titers decreasing (aCL IgG 45, anti-beta2-GPI 35). CKD stage 3b stable (Cr 1.6). INR therapeutic at 3.4. No recurrent thrombosis. Rituximab well tolerated. Pharmacogenomic warfarin dosing stable. Long-term prognosis discussed — lifelong anticoagulation mandatory, high recurrence risk if anticoagulation lapses.',
            'author' => 'Dr. Naveen Reddy',
            'authored_at' => '2026-10-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNeuro->id,
            'note_type' => 'consultation',
            'title' => 'Neurology — transient ischemic attack',
            'content' => 'Thirty-two-year-old with known APS presents with transient right-sided weakness and word-finding difficulty lasting 45 minutes, fully resolved. Brain MRI shows 3 chronic white matter lesions but no acute infarct on DWI. Likely APS-related TIA. INR was therapeutic at 3.1 at time of event. Warfarin target maintained. MRA neck unremarkable. Close neurology follow-up recommended.',
            'author' => 'Dr. James Park',
            'authored_at' => '2024-04-15',
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Year 1 Diagnosis (2019-04-15)
        $this->addMeasurement($patient, [
            'measurement_name' => 'Lupus anticoagulant',
            'concept_code' => '34515-7',
            'vocabulary' => 'LOINC',
            'value_text' => 'Positive',
            'unit' => null,
            'abnormal_flag' => 'A',
            'measured_at' => '2019-04-15',
        ]);

        $this->addLabPanel($patient, '2019-04-15', [
            ['aCL IgG', '53998-1', 58, 'GPL', 0, 20, 'H'],
            ['Anti-beta2-glycoprotein I IgG', '53981-7', 45, 'U/mL', 0, 20, 'H'],
            ['Platelet count', '777-3', 195, 'K/uL', 150, 400, null],
            ['Creatinine', '2160-0', 0.8, 'mg/dL', 0.6, 1.2, null],
            ['LDH', '2532-0', 180, 'U/L', 120, 246, null],
            ['Haptoglobin', '4542-7', 120, 'mg/dL', 30, 200, null],
            ['C3 complement', '4485-9', 110, 'mg/dL', 90, 180, null],
            ['C4 complement', '4498-2', 28, 'mg/dL', 10, 40, null],
            ['D-dimer', '48065-7', 0.4, 'ug/mL FEU', 0, 0.5, null],
        ]);

        // Year 4 DVT (2022-04-10)
        $this->addLabPanel($patient, '2022-04-10', [
            ['aCL IgG', '53998-1', 72, 'GPL', 0, 20, 'H'],
            ['Anti-beta2-glycoprotein I IgG', '53981-7', 60, 'U/mL', 0, 20, 'H'],
            ['Platelet count', '777-3', 165, 'K/uL', 150, 400, null],
            ['Creatinine', '2160-0', 0.9, 'mg/dL', 0.6, 1.2, null],
            ['LDH', '2532-0', 195, 'U/L', 120, 246, null],
            ['INR', '6301-6', 2.4, null, 2.0, 3.0, null],
            ['D-dimer', '48065-7', 3.8, 'ug/mL FEU', 0, 0.5, 'H'],
        ]);

        // Year 7 Nephropathy (2025-04-05)
        $this->addLabPanel($patient, '2025-04-05', [
            ['aCL IgG', '53998-1', 85, 'GPL', 0, 20, 'H'],
            ['Anti-beta2-glycoprotein I IgG', '53981-7', 78, 'U/mL', 0, 20, 'H'],
            ['Platelet count', '777-3', 155, 'K/uL', 150, 400, null],
            ['Creatinine', '2160-0', 1.1, 'mg/dL', 0.6, 1.2, null],
            ['LDH', '2532-0', 210, 'U/L', 120, 246, null],
            ['C3 complement', '4485-9', 95, 'mg/dL', 90, 180, null],
            ['C4 complement', '4498-2', 22, 'mg/dL', 10, 40, null],
            ['Urine protein/creatinine ratio', '2890-2', 0.8, 'g/g', 0, 0.2, 'H'],
        ]);

        // CAPS Day 0 (2026-04-01)
        $this->addLabPanel($patient, '2026-04-01', [
            ['Platelet count', '777-3', 180, 'K/uL', 150, 400, null],
            ['Creatinine', '2160-0', 1.1, 'mg/dL', 0.6, 1.2, null],
            ['INR', '6301-6', 1.6, null, 3.0, 4.0, 'L'],
            ['WBC', '6690-2', 12.4, 'K/uL', 4.5, 11.0, 'H'],
            ['Temperature', '8310-5', 39.1, '°C', 36.1, 37.2, 'H'],
        ]);

        // CAPS Day 2 (2026-04-02) — peak crisis
        $this->addLabPanel($patient, '2026-04-02', [
            ['Platelet count', '777-3', 42, 'K/uL', 150, 400, 'CL'],
            ['Creatinine', '2160-0', 4.2, 'mg/dL', 0.6, 1.2, 'CH'],
            ['LDH', '2532-0', 2800, 'U/L', 120, 246, 'H'],
            ['Haptoglobin', '4542-7', 10, 'mg/dL', 30, 200, 'CL'],
            ['AST', '1920-8', 1200, 'U/L', 10, 40, 'H'],
            ['ALT', '1742-6', 980, 'U/L', 7, 56, 'H'],
            ['C3 complement', '4485-9', 52, 'mg/dL', 90, 180, 'L'],
            ['C4 complement', '4498-2', 8, 'mg/dL', 10, 40, 'L'],
            ['D-dimer', '48065-7', 20, 'ug/mL FEU', 0, 0.5, 'H'],
            ['Urine protein/creatinine ratio', '2890-2', 3.5, 'g/g', 0, 0.2, 'H'],
        ]);

        $this->addMeasurement($patient, [
            'measurement_name' => 'Schistocytes',
            'concept_code' => '800-3',
            'vocabulary' => 'LOINC',
            'value_text' => '4-5/HPF',
            'unit' => '/HPF',
            'abnormal_flag' => 'H',
            'measured_at' => '2026-04-02',
        ]);

        // CAPS Day 21 Discharge (2026-04-21)
        $this->addLabPanel($patient, '2026-04-21', [
            ['Platelet count', '777-3', 145, 'K/uL', 150, 400, 'L'],
            ['Creatinine', '2160-0', 2.1, 'mg/dL', 0.6, 1.2, 'H'],
            ['LDH', '2532-0', 450, 'U/L', 120, 246, 'H'],
            ['AST', '1920-8', 65, 'U/L', 10, 40, 'H'],
            ['ALT', '1742-6', 72, 'U/L', 7, 56, 'H'],
            ['INR', '6301-6', 3.2, null, 3.0, 4.0, null],
            ['C3 complement', '4485-9', 78, 'mg/dL', 90, 180, 'L'],
            ['C4 complement', '4498-2', 15, 'mg/dL', 10, 40, null],
            ['D-dimer', '48065-7', 2.1, 'ug/mL FEU', 0, 0.5, 'H'],
        ]);

        // Year 10 Follow-up (2026-10-15)
        $this->addLabPanel($patient, '2026-10-15', [
            ['aCL IgG', '53998-1', 45, 'GPL', 0, 20, 'H'],
            ['Anti-beta2-glycoprotein I IgG', '53981-7', 35, 'U/mL', 0, 20, 'H'],
            ['Platelet count', '777-3', 175, 'K/uL', 150, 400, null],
            ['Creatinine', '2160-0', 1.6, 'mg/dL', 0.6, 1.2, 'H'],
            ['LDH', '2532-0', 195, 'U/L', 120, 246, null],
            ['Haptoglobin', '4542-7', 90, 'mg/dL', 30, 200, null],
            ['C3 complement', '4485-9', 100, 'mg/dL', 90, 180, null],
            ['C4 complement', '4498-2', 22, 'mg/dL', 10, 40, null],
            ['Urine protein/creatinine ratio', '2890-2', 0.6, 'g/g', 0, 0.2, 'H'],
            ['INR', '6301-6', 3.4, null, 3.0, 4.0, null],
        ]);

        // ── Observations ────────────────────────────────────────

        // aPL profile — triple positive at multiple timepoints
        $this->addObservation($patient, [
            'observation_name' => 'aPL profile',
            'category' => 'lab_interpretation',
            'value_text' => 'Triple-positive (LA+, aCL IgG+, anti-beta2-GPI IgG+)',
            'observed_at' => '2019-04-15',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'aPL profile',
            'category' => 'lab_interpretation',
            'value_text' => 'Triple-positive (LA+, aCL IgG+, anti-beta2-GPI IgG+) — confirmed at 12 weeks',
            'observed_at' => '2019-10-15',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'aPL profile',
            'category' => 'lab_interpretation',
            'value_text' => 'Triple-positive (LA+, aCL IgG+, anti-beta2-GPI IgG+) — persistently elevated',
            'observed_at' => '2026-04-02',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'aPL profile',
            'category' => 'lab_interpretation',
            'value_text' => 'Triple-positive (LA+, aCL IgG+, anti-beta2-GPI IgG+) — titers decreasing on rituximab',
            'observed_at' => '2026-10-15',
        ]);

        // SOFA scores
        $this->addObservation($patient, [
            'observation_name' => 'SOFA score',
            'category' => 'clinical_score',
            'value_numeric' => 12,
            'observed_at' => '2026-04-02',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'SOFA score',
            'category' => 'clinical_score',
            'value_numeric' => 6,
            'observed_at' => '2026-04-08',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'SOFA score',
            'category' => 'clinical_score',
            'value_numeric' => 2,
            'observed_at' => '2026-04-15',
        ]);

        // P/F ratios
        $this->addObservation($patient, [
            'observation_name' => 'P/F ratio',
            'category' => 'respiratory',
            'value_numeric' => 110,
            'observed_at' => '2026-04-02',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'P/F ratio',
            'category' => 'respiratory',
            'value_numeric' => 250,
            'observed_at' => '2026-04-08',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'P/F ratio',
            'category' => 'respiratory',
            'value_numeric' => 380,
            'observed_at' => '2026-04-11',
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $this->addImagingStudy($patient, [
            'study_date' => '2018-10-14',
            'modality' => 'US',
            'body_part' => 'Pelvis',
            'description' => 'Obstetric ultrasound — intrauterine fetal demise at 14 weeks. No fetal cardiac activity. Placenta appears small for gestational age.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2019-04-10',
            'modality' => 'US',
            'body_part' => 'Pelvis',
            'description' => 'Obstetric ultrasound — intrauterine fetal demise at 10 weeks. No fetal cardiac activity detected. Second pregnancy loss.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2022-04-08',
            'modality' => 'US',
            'body_part' => 'Left lower extremity',
            'description' => 'Lower extremity duplex — acute DVT in left common femoral, superficial femoral, and popliteal veins. No flow augmentation with compression.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2024-04-15',
            'modality' => 'MRI',
            'body_part' => 'Brain',
            'description' => 'Brain MRI — three chronic periventricular white matter T2/FLAIR hyperintensities consistent with prior small vessel ischemia. No acute infarct on DWI. No hemorrhage.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2026-04-01',
            'modality' => 'US',
            'body_part' => 'Bilateral lower extremity',
            'description' => 'Bilateral lower extremity duplex — acute DVT in bilateral common femoral, superficial femoral, and popliteal veins. Bilateral involvement in setting of known APS.',
        ]);

        $ctpa = $this->addImagingStudy($patient, [
            'study_date' => '2026-04-02',
            'modality' => 'CT',
            'body_part' => 'Chest',
            'description' => 'CT pulmonary angiography — bilateral pulmonary emboli involving segmental and subsegmental branches. RV/LV ratio 1.3 suggesting right heart strain.',
        ]);

        $this->addImagingMeasurement($ctpa, [
            'measurement_type' => 'RV/LV ratio',
            'value_numeric' => 1.3,
            'unit' => 'ratio',
        ]);

        $ctAbd = $this->addImagingStudy($patient, [
            'study_date' => '2026-04-02',
            'modality' => 'CT',
            'body_part' => 'Abdomen',
            'description' => 'CT abdomen/pelvis with contrast — bilateral wedge-shaped renal infarcts involving upper and lower poles bilaterally. Multiple hepatic infarcts in right lobe. Findings consistent with multiorgan thrombotic event.',
        ]);

        $echo = $this->addImagingStudy($patient, [
            'study_date' => '2026-04-02',
            'modality' => 'US',
            'body_part' => 'Heart',
            'description' => 'Echocardiogram — RV dilation with reduced systolic function. TAPSE 12mm (reduced). Estimated RVSP 55 mmHg. No valvular vegetations. LV function preserved.',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_type' => 'TAPSE',
            'value_numeric' => 12,
            'unit' => 'mm',
        ]);

        $this->addImagingMeasurement($echo, [
            'measurement_type' => 'RVSP',
            'value_numeric' => 55,
            'unit' => 'mmHg',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2026-04-02',
            'modality' => 'XR',
            'body_part' => 'Chest',
            'description' => 'Chest X-ray — bilateral diffuse ground-glass opacities consistent with ARDS. ET tube in satisfactory position. No pleural effusion.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2026-04-06',
            'modality' => 'XR',
            'body_part' => 'Chest',
            'description' => 'Chest X-ray — bilateral ground-glass opacities improving compared to prior. Patient remains intubated.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2026-04-10',
            'modality' => 'XR',
            'body_part' => 'Chest',
            'description' => 'Chest X-ray — resolving bilateral opacities. ET tube removed. No pneumothorax. Clear costophrenic angles.',
        ]);

        $this->addImagingStudy($patient, [
            'study_date' => '2026-08-15',
            'modality' => 'MRI',
            'body_part' => 'Kidneys',
            'description' => 'MRA renal — bilateral cortical scarring from prior infarcts. Bilateral kidneys mildly atrophic (right 9.5cm, left 9.8cm). No renal artery stenosis. Findings consistent with chronic APS nephropathy.',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'HLA-DRB1',
            'variant' => 'HLA-DRB1*04:01',
            'variant_type' => 'SNV',
            'chromosome' => 'chr6',
            'zygosity' => 'heterozygous',
            'clinical_significance' => 'VUS',
            'actionability' => 'risk_factor',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'CFH',
            'variant' => 'CFH c.2850G>T (p.Gln950His)',
            'variant_type' => 'SNV',
            'chromosome' => 'chr1',
            'zygosity' => 'heterozygous',
            'clinical_significance' => 'VUS',
            'actionability' => 'risk_factor',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'CYP2C9',
            'variant' => 'CYP2C9*3/*1',
            'variant_type' => 'SNV',
            'chromosome' => 'chr10',
            'zygosity' => 'heterozygous',
            'clinical_significance' => 'VUS',
            'actionability' => 'warfarin_dose_reduction',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'VKORC1',
            'variant' => 'VKORC1 -1639G>A',
            'variant_type' => 'SNV',
            'chromosome' => 'chr16',
            'zygosity' => 'heterozygous',
            'clinical_significance' => 'VUS',
            'actionability' => 'warfarin_dose_reduction',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Antiphospholipid syndrome',
            'era_start' => '2019-04-01',
            'era_end' => null,
            'occurrence_count' => 12,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Thrombotic events',
            'era_start' => '2022-04-01',
            'era_end' => null,
            'occurrence_count' => 5,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Chronic kidney disease',
            'era_start' => '2026-05-01',
            'era_end' => null,
            'occurrence_count' => 4,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Pregnancy loss',
            'era_start' => '2018-04-01',
            'era_end' => '2019-10-01',
            'occurrence_count' => 2,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Aspirin',
            'era_start' => '2019-10-01',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Warfarin',
            'era_start' => '2022-04-15',
            'era_end' => null,
            'gap_days' => 7,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Hydroxychloroquine',
            'era_start' => '2025-04-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Rituximab',
            'era_start' => '2026-04-05',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
