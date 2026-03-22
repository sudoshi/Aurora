<?php

namespace Database\Seeders\DemoPatients;

class RareDiseasePatient2_TSC
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-RD-002',
            'first_name' => 'Isabella',
            'last_name' => 'Ramirez',
            'date_of_birth' => '2012-01-08',
            'sex' => 'Female',
            'race' => 'White',
            'ethnicity' => 'Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-IR-77234');
        $this->addIdentifier($patient, 'hospital_mrn', 'CHM-201289', "Children's Medical Center");

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Tuberous sclerosis complex',
            'concept_code' => 'Q85.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2012-01-08',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Infantile spasms (West syndrome)',
            'concept_code' => 'G40.822',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2012-06-01',
            'severity' => 'severe',
            'resolution_date' => '2012-08-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Drug-resistant focal epilepsy',
            'concept_code' => 'G40.119',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2013-07-01',
            'severity' => 'severe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Subependymal giant cell astrocytoma',
            'concept_code' => 'D33.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2018-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Bilateral renal angiomyolipomas',
            'concept_code' => 'D30.00',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2020-01-01',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Autism spectrum disorder',
            'concept_code' => 'F84.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-01-01',
            'severity' => 'moderate',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Mild intellectual disability',
            'concept_code' => 'F70',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Facial angiofibromas',
            'concept_code' => 'L98.8',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2017-01-01',
            'severity' => 'mild',
            'body_site' => 'Face',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Retinal hamartomas',
            'concept_code' => 'D31.20',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2013-01-01',
            'severity' => 'mild',
            'laterality' => 'right',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Cardiac rhabdomyomas',
            'concept_code' => 'D15.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'resolved',
            'onset_date' => '2012-01-08',
            'resolution_date' => '2015-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hypomelanotic macules',
            'concept_code' => 'L81.5',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2014-01-01',
            'severity' => 'mild',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Shagreen patch',
            'concept_code' => 'Q85.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2014-01-01',
            'body_site' => 'Lumbosacral',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Vigabatrin',
            'concept_code' => 'N03AG04',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 50,
            'dose_unit' => 'mg/kg/day',
            'frequency' => 'BID',
            'start_date' => '2012-06-15',
            'end_date' => '2013-07-01',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Oxcarbazepine',
            'concept_code' => 'N03AF02',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 30,
            'dose_unit' => 'mg/kg/day',
            'frequency' => 'BID',
            'start_date' => '2013-07-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Everolimus',
            'concept_code' => 'L04AA18',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 4.5,
            'dose_unit' => 'mg/m2/day',
            'frequency' => 'daily',
            'start_date' => '2018-01-20',
            'status' => 'active',
            'prescriber' => 'Dr. Maria Santos',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Topical sirolimus 0.1%',
            'concept_code' => 'L04AA10',
            'vocabulary' => 'ATC',
            'route' => 'topical',
            'dose_value' => 0.1,
            'dose_unit' => '%',
            'frequency' => 'daily',
            'start_date' => '2017-06-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Cannabidiol (Epidiolex)',
            'concept_code' => 'N03AX24',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 10,
            'dose_unit' => 'mg/kg/day',
            'frequency' => 'BID',
            'start_date' => '2024-01-10',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Melatonin',
            'concept_code' => 'N05CH01',
            'vocabulary' => 'ATC',
            'route' => 'oral',
            'dose_value' => 3,
            'dose_unit' => 'mg',
            'frequency' => 'nightly',
            'start_date' => '2015-06-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Fetal echocardiogram',
            'concept_code' => '76825',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2011-09-15',
            'performer' => 'Maternal-Fetal Medicine',
            'body_site' => 'Heart',
            'notes' => 'Performed at 32 weeks gestation. Multiple cardiac rhabdomyomas identified, largest 1.2cm in left ventricle.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Neonatal brain MRI',
            'concept_code' => '70553',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2012-01-10',
            'performer' => 'Neonatology/Radiology',
            'body_site' => 'Brain',
            'notes' => 'Multiple cortical tubers and subependymal nodules identified. Consistent with tuberous sclerosis complex.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'EEG — infantile spasms onset',
            'concept_code' => '95816',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2012-06-10',
            'performer' => 'Pediatric Neurology',
            'body_site' => 'Brain',
            'notes' => 'Hypsarrhythmia pattern consistent with infantile spasms (West syndrome).',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'EEG — post vigabatrin',
            'concept_code' => '95816',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2012-08-15',
            'performer' => 'Pediatric Neurology',
            'body_site' => 'Brain',
            'notes' => 'Resolution of hypsarrhythmia. Spasms ceased on vigabatrin.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'EEG — focal epilepsy onset',
            'concept_code' => '95816',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2013-07-10',
            'performer' => 'Pediatric Neurology',
            'body_site' => 'Brain',
            'notes' => 'Right temporal focal discharges with secondary generalization. New seizure type post spasm resolution.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'EEG — annual surveillance',
            'concept_code' => '95816',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2025-06-10',
            'performer' => 'Pediatric Neurology',
            'body_site' => 'Brain',
            'notes' => 'Multifocal epileptiform activity, improved frequency post VNS placement.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Stereo-EEG evaluation',
            'concept_code' => '95700',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2025-01-15',
            'performer' => 'Epilepsy Surgery',
            'body_site' => 'Brain',
            'notes' => 'Multi-electrode depth recording to localize seizure foci. Multiple independent foci identified — resective surgery not recommended.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'VNS implantation',
            'concept_code' => '64568',
            'vocabulary' => 'CPT',
            'domain' => 'complex_medical',
            'performed_date' => '2025-03-10',
            'performer' => 'Neurosurgery',
            'body_site' => 'Left cervical',
            'notes' => 'Vagus nerve stimulator implanted for drug-resistant multifocal epilepsy. Procedure uncomplicated.',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Ophthalmologic exam',
            'concept_code' => '92004',
            'vocabulary' => 'CPT',
            'domain' => 'rare_disease',
            'performed_date' => '2013-01-15',
            'performer' => 'Ophthalmology',
            'body_site' => 'Eyes',
            'notes' => 'Right retinal hamartoma identified near optic disc. Non-visually significant. No intervention required.',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $facility = "Children's Medical Center";

        // Prenatal
        $visitPrenatal = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2011-09-15',
            'discharge_date' => '2011-09-15',
            'attending_provider' => 'Dr. Rebecca Torres',
            'department' => 'Maternal-Fetal Medicine',
        ]);

        // Neonatal admission
        $visitNeonatal = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2012-01-08',
            'discharge_date' => '2012-01-15',
            'attending_provider' => 'Dr. James Liu',
            'department' => 'Neonatology',
        ]);

        // Pediatric cardiology — serial echos
        $visitCardio1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2012-02-10',
            'discharge_date' => '2012-02-10',
            'attending_provider' => 'Dr. Anita Patel',
            'department' => 'Pediatric Cardiology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2012-07-10',
            'discharge_date' => '2012-07-10',
            'attending_provider' => 'Dr. Anita Patel',
            'department' => 'Pediatric Cardiology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2013-01-10',
            'discharge_date' => '2013-01-10',
            'attending_provider' => 'Dr. Anita Patel',
            'department' => 'Pediatric Cardiology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2014-01-10',
            'discharge_date' => '2014-01-10',
            'attending_provider' => 'Dr. Anita Patel',
            'department' => 'Pediatric Cardiology',
        ]);

        // Genetics
        $visitGenetics = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2012-03-15',
            'discharge_date' => '2012-03-15',
            'attending_provider' => 'Dr. Sarah Kim',
            'department' => 'Genetics',
        ]);

        // Infantile spasms onset — emergency
        $visitSpasmsER = $this->addVisit($patient, [
            'visit_type' => 'emergency',
            'facility' => $facility,
            'admission_date' => '2012-06-08',
            'discharge_date' => '2012-06-08',
            'attending_provider' => 'Dr. Michael Chen',
            'department' => 'Pediatric Emergency',
        ]);

        // Infantile spasms — inpatient for vigabatrin initiation
        $visitSpasmsIP = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2012-06-08',
            'discharge_date' => '2012-06-18',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        // Neurology follow-ups (many)
        $visitNeuro1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2012-08-15',
            'discharge_date' => '2012-08-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2013-01-15',
            'discharge_date' => '2013-01-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2013-07-15',
            'discharge_date' => '2013-07-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2014-07-15',
            'discharge_date' => '2014-07-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2015-07-15',
            'discharge_date' => '2015-07-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2016-07-15',
            'discharge_date' => '2016-07-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2017-07-15',
            'discharge_date' => '2017-07-15',
            'attending_provider' => 'Dr. Lisa Nguyen',
            'department' => 'Pediatric Neurology',
        ]);

        $visitNeuroSEGA = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2018-01-20',
            'discharge_date' => '2018-01-20',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2019-01-15',
            'discharge_date' => '2019-01-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2020-07-15',
            'discharge_date' => '2020-07-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2021-07-15',
            'discharge_date' => '2021-07-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2022-07-15',
            'discharge_date' => '2022-07-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2023-07-15',
            'discharge_date' => '2023-07-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2024-01-15',
            'discharge_date' => '2024-01-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Pediatric Neurology',
        ]);

        $visitEpilepsySurgery = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2025-01-15',
            'discharge_date' => '2025-01-15',
            'attending_provider' => 'Dr. Robert Hayes',
            'department' => 'Epilepsy Surgery',
        ]);

        $visitVNS = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'facility' => $facility,
            'admission_date' => '2025-03-10',
            'discharge_date' => '2025-03-12',
            'attending_provider' => 'Dr. Robert Hayes',
            'department' => 'Neurosurgery',
        ]);

        // Ophthalmology
        $visitOphtho = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2013-01-15',
            'discharge_date' => '2013-01-15',
            'attending_provider' => 'Dr. Emily Park',
            'department' => 'Ophthalmology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2016-01-15',
            'discharge_date' => '2016-01-15',
            'attending_provider' => 'Dr. Emily Park',
            'department' => 'Ophthalmology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2022-01-15',
            'discharge_date' => '2022-01-15',
            'attending_provider' => 'Dr. Emily Park',
            'department' => 'Ophthalmology',
        ]);

        // Dermatology
        $visitDerm = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2017-06-01',
            'discharge_date' => '2017-06-01',
            'attending_provider' => 'Dr. Karen Walsh',
            'department' => 'Dermatology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2020-06-01',
            'discharge_date' => '2020-06-01',
            'attending_provider' => 'Dr. Karen Walsh',
            'department' => 'Dermatology',
        ]);

        // Developmental Pediatrics / ASD diagnosis
        $visitDevPeds = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2015-01-20',
            'discharge_date' => '2015-01-20',
            'attending_provider' => 'Dr. Rachel Adams',
            'department' => 'Developmental Pediatrics',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2016-06-15',
            'discharge_date' => '2016-06-15',
            'attending_provider' => 'Dr. Rachel Adams',
            'department' => 'Developmental Pediatrics',
        ]);

        // Child Psychiatry
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2017-01-15',
            'discharge_date' => '2017-01-15',
            'attending_provider' => 'Dr. David Ortiz',
            'department' => 'Child Psychiatry',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2019-06-15',
            'discharge_date' => '2019-06-15',
            'attending_provider' => 'Dr. David Ortiz',
            'department' => 'Child Psychiatry',
        ]);

        // Pediatric Nephrology
        $visitNephro = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2020-01-15',
            'discharge_date' => '2020-01-15',
            'attending_provider' => 'Dr. Claudia Reyes',
            'department' => 'Pediatric Nephrology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2022-01-15',
            'discharge_date' => '2022-01-15',
            'attending_provider' => 'Dr. Claudia Reyes',
            'department' => 'Pediatric Nephrology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2024-07-15',
            'discharge_date' => '2024-07-15',
            'attending_provider' => 'Dr. Claudia Reyes',
            'department' => 'Pediatric Nephrology',
        ]);

        // Pulmonology — LAM screening
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2023-01-10',
            'discharge_date' => '2023-01-10',
            'attending_provider' => 'Dr. Thomas Grant',
            'department' => 'Pulmonology',
        ]);

        // Multidisciplinary TSC Clinic
        $visitTSCClinic1 = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2018-06-15',
            'discharge_date' => '2018-06-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Multidisciplinary TSC Clinic',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2020-06-15',
            'discharge_date' => '2020-06-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Multidisciplinary TSC Clinic',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2022-06-15',
            'discharge_date' => '2022-06-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Multidisciplinary TSC Clinic',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2024-06-15',
            'discharge_date' => '2024-06-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Multidisciplinary TSC Clinic',
        ]);

        $visitTransition = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => $facility,
            'admission_date' => '2026-01-15',
            'discharge_date' => '2026-01-15',
            'attending_provider' => 'Dr. Maria Santos',
            'department' => 'Multidisciplinary TSC Clinic',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $visitPrenatal->id,
            'note_type' => 'radiology_report',
            'title' => 'Prenatal ultrasound — cardiac rhabdomyomas',
            'content' => 'Routine 32-week growth ultrasound reveals multiple echogenic intracardiac masses consistent with rhabdomyomas. Largest measures 1.2 cm in left ventricle. No hemodynamic compromise. Findings raise concern for tuberous sclerosis complex. Genetic counseling and postnatal workup recommended.',
            'author' => 'Dr. Rebecca Torres',
            'authored_at' => '2011-09-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNeonatal->id,
            'note_type' => 'consultation',
            'title' => 'Neonatal cardiology echocardiogram',
            'content' => 'Postnatal echo confirms multiple cardiac rhabdomyomas: LV (1.2cm, 0.8cm), RV (0.6cm). No outflow tract obstruction. Normal biventricular function. These are expected to regress over first few years of life. Serial echocardiography planned every 6 months.',
            'author' => 'Dr. Anita Patel',
            'authored_at' => '2012-01-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNeonatal->id,
            'note_type' => 'radiology_report',
            'title' => 'Neonatal brain MRI',
            'content' => 'MRI brain demonstrates at least 12 cortical tubers predominantly in frontal and temporal lobes. Multiple subependymal nodules (SENs) along lateral ventricles, largest 5mm near foramen of Monro. No hydrocephalus. Findings diagnostic for tuberous sclerosis complex.',
            'author' => 'Dr. James Liu',
            'authored_at' => '2012-01-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitGenetics->id,
            'note_type' => 'consultation',
            'title' => 'Genetics counseling — TSC2 pathogenic variant',
            'content' => 'Genetic testing confirms heterozygous pathogenic variant in TSC2: c.5024C>T (p.Pro1675Leu). Both parents tested negative — variant is de novo. TSC2 mutations associated with more severe neurological phenotype. Comprehensive TSC surveillance protocol initiated per international guidelines.',
            'author' => 'Dr. Sarah Kim',
            'authored_at' => '2012-03-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitSpasmsER->id,
            'note_type' => 'emergency',
            'title' => 'Infantile spasms onset',
            'content' => 'Five-month-old female presents with clusters of flexion spasms occurring 5-6 times daily, each cluster with 10-15 spasms. Mother reports developmental regression over past week. EEG shows hypsarrhythmia. Diagnosis: infantile spasms (West syndrome) in setting of TSC. Vigabatrin initiated as first-line per TSC guidelines.',
            'author' => 'Dr. Michael Chen',
            'authored_at' => '2012-06-08',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitSpasmsIP->id,
            'note_type' => 'progress',
            'title' => 'Vigabatrin treatment — spasm resolution',
            'content' => 'Isabella admitted for vigabatrin initiation at 50 mg/kg/day. Spasms resolved by day 7 of treatment. Repeat EEG shows resolution of hypsarrhythmia. Ophthalmology baseline visual field assessment obtained. Parents counseled on vigabatrin retinal toxicity monitoring.',
            'author' => 'Dr. Lisa Nguyen',
            'authored_at' => '2012-06-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNeuro1->id,
            'note_type' => 'progress',
            'title' => 'EEG follow-up — spasm-free',
            'content' => 'Follow-up EEG at 7 months of age shows no recurrence of hypsarrhythmia. Background activity improved but still showing multifocal sharp waves predominantly right temporal. Developmental assessment shows some recovery but remains delayed. Continue vigabatrin with ophthalmology monitoring.',
            'author' => 'Dr. Lisa Nguyen',
            'authored_at' => '2012-08-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitOphtho->id,
            'note_type' => 'consultation',
            'title' => 'Ophthalmologic exam — retinal hamartoma',
            'content' => 'Dilated fundoscopic exam reveals a solitary retinal astrocytic hamartoma near right optic disc, approximately 1mm. Left eye unremarkable. No evidence of vigabatrin retinal toxicity on electroretinography. Hamartoma is non-visually significant. Annual surveillance recommended.',
            'author' => 'Dr. Emily Park',
            'authored_at' => '2013-01-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDevPeds->id,
            'note_type' => 'consultation',
            'title' => 'Developmental assessment — ASD and ID diagnosis',
            'content' => 'Comprehensive developmental evaluation at age 3. Bayley-III Cognitive Composite: 72 (borderline). ADOS-2 positive for autism spectrum disorder. Language significantly delayed (18-month equivalent). Diagnoses: autism spectrum disorder (moderate) and mild intellectual disability. ABA therapy, speech therapy, and occupational therapy recommended.',
            'author' => 'Dr. Rachel Adams',
            'authored_at' => '2015-01-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitDerm->id,
            'note_type' => 'consultation',
            'title' => 'Dermatology — facial angiofibromas',
            'content' => 'Exam reveals multiple small facial angiofibromas (adenoma sebaceum) across malar region, typical TSC distribution. Also noted: >3 hypomelanotic macules on trunk and lumbosacral shagreen patch. Topical sirolimus 0.1% initiated for facial angiofibromas with good evidence base in TSC.',
            'author' => 'Dr. Karen Walsh',
            'authored_at' => '2017-06-01',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNeuroSEGA->id,
            'note_type' => 'consultation',
            'title' => 'SEGA discovery and everolimus initiation',
            'content' => 'Brain MRI surveillance reveals subependymal nodule near left foramen of Monro has grown to 1.3 cm, now meeting criteria for subependymal giant cell astrocytoma (SEGA). Early signs of ipsilateral ventricular enlargement. Given bilateral renal AMLs also identified on recent imaging, systemic everolimus initiated at 4.5 mg/m2 daily to address both SEGA and renal disease.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2018-01-20',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitTSCClinic1->id,
            'note_type' => 'progress',
            'title' => 'TSC Clinic — 6-month everolimus follow-up',
            'content' => 'Multi-disciplinary TSC clinic review. Everolimus trough therapeutic at 7.2 ng/mL. Brain MRI shows SEGA regression from 1.3cm to 0.9cm. Notable metabolic side effects: hypercholesterolemia (198 mg/dL) and hypertriglyceridemia (165 mg/dL). Mild transaminase elevation (AST 42, ALT 48). Stomatitis episode managed with topical dexamethasone. Continue current dose with monitoring.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2018-06-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitNephro->id,
            'note_type' => 'consultation',
            'title' => 'Nephrology — renal AML surveillance',
            'content' => 'Renal MRI reveals bilateral angiomyolipomas: right kidney 2.1cm, left kidney 1.5cm and 0.8cm. No evidence of hemorrhage. GFR estimated >120 mL/min. Everolimus being administered for SEGA is also expected to stabilize AML growth. Continue annual renal MRI surveillance.',
            'author' => 'Dr. Claudia Reyes',
            'authored_at' => '2020-01-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitEpilepsySurgery->id,
            'note_type' => 'consultation',
            'title' => 'Epilepsy surgery evaluation — stereo-EEG',
            'content' => 'Stereo-EEG with 14 depth electrodes over 5 days reveals at least 4 independent seizure foci involving bilateral frontal and right temporal regions. Given multifocal onset, resective epilepsy surgery is not recommended. VNS implantation offered as palliative neuromodulation approach.',
            'author' => 'Dr. Robert Hayes',
            'authored_at' => '2025-01-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitVNS->id,
            'note_type' => 'operative',
            'title' => 'VNS implantation operative note',
            'content' => 'Vagus nerve stimulator (VNS Therapy System) implanted via left cervical approach. Left vagus nerve identified and electrode coils wrapped. Generator placed in left infraclavicular pocket. Intraoperative impedance check normal. Initial settings: 0.25mA output, 30-second on time, 5-minute off time. Titration schedule planned over 3 months.',
            'author' => 'Dr. Robert Hayes',
            'authored_at' => '2025-03-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitTransition->id,
            'note_type' => 'consultation',
            'title' => 'Transition planning — pediatric to adult care',
            'content' => 'Isabella is now 14 years old. Transition planning initiated for eventual transfer to adult TSC clinic. Current disease burden: stable SEGA on everolimus, bilateral renal AMLs stable, drug-resistant epilepsy with VNS (seizure frequency reduced from 4/month to 2/month), ASD with supported education. LAM screening chest CT negative at age 11. Comprehensive transition checklist started.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2026-01-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitCardio1->id,
            'note_type' => 'progress',
            'title' => 'Cardiology follow-up — rhabdomyoma regression',
            'content' => 'Serial echocardiography at 1 month of age shows stable cardiac rhabdomyomas. Natural regression expected. No arrhythmias on Holter monitor. Normal biventricular function maintained. Continue serial echo every 6 months until regression documented.',
            'author' => 'Dr. Anita Patel',
            'authored_at' => '2012-02-10',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitTransition->id,
            'note_type' => 'progress',
            'title' => 'Everolimus monitoring — long-term follow-up',
            'content' => 'Eight years on everolimus therapy. Current trough level 8.4 ng/mL (therapeutic range 5-15). Persistent but stable dyslipidemia managed with dietary counseling. Hepatic function mildly elevated but stable. GFR 98 mL/min — appropriate for age. One drug holiday of 14 days in 2020 for stomatitis episode. Overall well-tolerated.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2026-01-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitTransition->id,
            'note_type' => 'progress',
            'title' => 'Seizure management update — post VNS',
            'content' => 'VNS implanted March 2025, titrated to therapeutic settings over 3 months. Seizure frequency reduced from approximately 4/month pre-VNS to 2/month. Current AED regimen: oxcarbazepine 30 mg/kg/day and cannabidiol (Epidiolex) 10 mg/kg/day. Family reports improved alertness and reduced postictal periods.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2026-01-15',
        ]);

        $this->addNote($patient, [
            'visit_id' => $visitTransition->id,
            'note_type' => 'progress',
            'title' => 'Behavioral and educational update',
            'content' => 'Isabella attends special education program with 1:1 aide. ABA therapy discontinued at age 10 per family preference. Speech therapy continues twice weekly. Functional communication via AAC device with some verbal phrases. Sleep improved on melatonin 3mg nightly. No significant behavioral crises in past year.',
            'author' => 'Dr. Maria Santos',
            'authored_at' => '2026-01-15',
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Age 0.5yr (2012-07-10)
        $this->addLabPanel($patient, '2012-07-10', [
            ['White Blood Cell Count', '6690-2', 11.2, 'x10^9/L', 5.0, 13.0, null],
            ['Hemoglobin', '718-7', 11.0, 'g/dL', 10.0, 14.0, null],
            ['Platelet Count', '777-3', 310, 'x10^9/L', 150, 400, null],
        ]);

        // Age 6 Pre-Everolimus (2018-01-10)
        $this->addLabPanel($patient, '2018-01-10', [
            ['Total Cholesterol', '2093-3', 155, 'mg/dL', null, 170, null],
            ['Triglycerides', '2571-8', 90, 'mg/dL', null, 90, null],
            ['White Blood Cell Count', '6690-2', 8.5, 'x10^9/L', 4.5, 11.0, null],
            ['Platelet Count', '777-3', 280, 'x10^9/L', 150, 400, null],
            ['Creatinine', '2160-0', 0.3, 'mg/dL', 0.2, 0.5, null],
            ['eGFR', '33914-3', 120, 'mL/min/1.73m2', 90, null, null],
            ['AST', '1920-8', 28, 'U/L', 10, 40, null],
            ['ALT', '1742-6', 22, 'U/L', 7, 35, null],
            ['Fasting Glucose', '1558-6', 82, 'mg/dL', 70, 100, null],
        ]);

        // Age 7 On-Everolimus (2019-01-10)
        $this->addLabPanel($patient, '2019-01-10', [
            ['Everolimus Trough Level', '57370-3', 7.2, 'ng/mL', 5.0, 15.0, null],
            ['Total Cholesterol', '2093-3', 198, 'mg/dL', null, 170, 'H'],
            ['Triglycerides', '2571-8', 165, 'mg/dL', null, 90, 'H'],
            ['White Blood Cell Count', '6690-2', 6.2, 'x10^9/L', 4.5, 11.0, null],
            ['Platelet Count', '777-3', 195, 'x10^9/L', 150, 400, null],
            ['Creatinine', '2160-0', 0.35, 'mg/dL', 0.2, 0.6, null],
            ['eGFR', '33914-3', 115, 'mL/min/1.73m2', 90, null, null],
            ['AST', '1920-8', 42, 'U/L', 10, 40, 'H'],
            ['ALT', '1742-6', 48, 'U/L', 7, 35, 'H'],
            ['Fasting Glucose', '1558-6', 95, 'mg/dL', 70, 100, null],
        ]);

        // Age 10 (2022-01-10)
        $this->addLabPanel($patient, '2022-01-10', [
            ['Everolimus Trough Level', '57370-3', 9.1, 'ng/mL', 5.0, 15.0, null],
            ['Total Cholesterol', '2093-3', 210, 'mg/dL', null, 170, 'H'],
            ['Triglycerides', '2571-8', 180, 'mg/dL', null, 90, 'H'],
            ['White Blood Cell Count', '6690-2', 5.8, 'x10^9/L', 4.5, 11.0, null],
            ['Platelet Count', '777-3', 185, 'x10^9/L', 150, 400, null],
            ['Creatinine', '2160-0', 0.45, 'mg/dL', 0.3, 0.7, null],
            ['eGFR', '33914-3', 105, 'mL/min/1.73m2', 90, null, null],
            ['AST', '1920-8', 38, 'U/L', 10, 40, null],
            ['ALT', '1742-6', 40, 'U/L', 7, 35, 'H'],
            ['Fasting Glucose', '1558-6', 98, 'mg/dL', 70, 100, null],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Urinalysis Protein',
            'concept_code' => '5804-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'trace',
            'unit' => 'mg/dL',
            'abnormal_flag' => 'A',
            'measured_at' => '2022-01-10',
        ]);

        // Age 14 (2026-01-10)
        $this->addLabPanel($patient, '2026-01-10', [
            ['Everolimus Trough Level', '57370-3', 8.4, 'ng/mL', 5.0, 15.0, null],
            ['Total Cholesterol', '2093-3', 225, 'mg/dL', null, 170, 'H'],
            ['Triglycerides', '2571-8', 195, 'mg/dL', null, 90, 'H'],
            ['White Blood Cell Count', '6690-2', 5.5, 'x10^9/L', 4.5, 11.0, null],
            ['Platelet Count', '777-3', 175, 'x10^9/L', 150, 400, null],
            ['Creatinine', '2160-0', 0.55, 'mg/dL', 0.3, 0.8, null],
            ['eGFR', '33914-3', 98, 'mL/min/1.73m2', 90, null, null],
            ['AST', '1920-8', 35, 'U/L', 10, 40, null],
            ['ALT', '1742-6', 36, 'U/L', 7, 35, 'H'],
            ['Fasting Glucose', '1558-6', 102, 'mg/dL', 70, 100, 'H'],
        ]);
        $this->addMeasurement($patient, [
            'measurement_name' => 'Urinalysis Protein',
            'concept_code' => '5804-0',
            'vocabulary' => 'LOINC',
            'value_text' => 'trace',
            'unit' => 'mg/dL',
            'abnormal_flag' => 'A',
            'measured_at' => '2026-01-10',
        ]);

        // ── Observations ────────────────────────────────────────
        $this->addObservation($patient, [
            'observation_name' => 'Bayley-III Cognitive Composite',
            'concept_code' => '77565-0',
            'vocabulary' => 'LOINC',
            'value_numeric' => 72,
            'value_text' => 'Borderline — age 3',
            'observed_at' => '2015-01-20',
            'category' => 'developmental',
        ]);

        // Seizure frequency longitudinal
        $this->addObservation($patient, [
            'observation_name' => 'Seizure frequency',
            'concept_code' => '75325-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 5,
            'value_text' => '5 per day — infantile spasms onset (age 5 months)',
            'observed_at' => '2012-06-08',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Seizure frequency',
            'concept_code' => '75325-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 0,
            'value_text' => '0 per day — resolved on vigabatrin (age 6 months)',
            'observed_at' => '2012-07-10',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Seizure frequency',
            'concept_code' => '75325-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 2,
            'value_text' => '2 per week — focal epilepsy (age 2)',
            'observed_at' => '2014-01-10',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Seizure frequency',
            'concept_code' => '75325-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 4,
            'value_text' => '4 per month (age 10)',
            'observed_at' => '2022-01-10',
            'category' => 'clinical_assessment',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Seizure frequency',
            'concept_code' => '75325-1',
            'vocabulary' => 'LOINC',
            'value_numeric' => 2,
            'value_text' => '2 per month — post VNS (age 14)',
            'observed_at' => '2026-01-10',
            'category' => 'clinical_assessment',
        ]);

        // SEGA size longitudinal
        $this->addObservation($patient, [
            'observation_name' => 'SEGA size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 5,
            'value_text' => '5 mm — SEN at birth',
            'observed_at' => '2012-01-10',
            'category' => 'tumor_measurement',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'SEGA size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 9,
            'value_text' => '9 mm — growing SEN (age 4)',
            'observed_at' => '2016-01-10',
            'category' => 'tumor_measurement',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'SEGA size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 13,
            'value_text' => '13 mm — SEGA with early hydrocephalus (age 6)',
            'observed_at' => '2018-01-10',
            'category' => 'tumor_measurement',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'SEGA size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 9,
            'value_text' => '9 mm — regression on everolimus (age 7)',
            'observed_at' => '2019-01-10',
            'category' => 'tumor_measurement',
        ]);

        // Right renal AML size longitudinal
        $this->addObservation($patient, [
            'observation_name' => 'Right renal AML size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 21,
            'value_text' => '21 mm — first identified (age 8)',
            'observed_at' => '2020-01-10',
            'category' => 'tumor_measurement',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Right renal AML size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 35,
            'value_text' => '35 mm — interval growth (age 10)',
            'observed_at' => '2022-01-10',
            'category' => 'tumor_measurement',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Right renal AML size',
            'concept_code' => '33726-3',
            'vocabulary' => 'LOINC',
            'value_numeric' => 32,
            'value_text' => '32 mm — stable on everolimus (age 14)',
            'observed_at' => '2026-01-10',
            'category' => 'tumor_measurement',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2011-09-15',
            'description' => 'Fetal echocardiogram — multiple cardiac rhabdomyomas identified at 32 weeks gestation',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 45,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2012-01-10',
            'description' => 'Neonatal echocardiogram — cardiac rhabdomyomas LV (1.2cm, 0.8cm), RV (0.6cm)',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 52,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2012-07-10',
            'description' => 'Echocardiogram 6 months — rhabdomyomas regressing, largest now 0.9cm',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 48,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2013-01-10',
            'description' => 'Echocardiogram 1 year — rhabdomyomas 0.4cm, continued regression',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 40,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2014-01-10',
            'description' => 'Echocardiogram 2 years — rhabdomyomas barely visible, near-complete regression',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 38,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2012-01-10',
            'description' => 'Brain MRI neonatal — 12+ cortical tubers, multiple SENs, largest 5mm near foramen of Monro',
            'body_part' => 'Brain',
            'num_series' => 5,
            'num_instances' => 180,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2016-01-10',
            'description' => 'Brain MRI age 4 — SEN near left foramen of Monro grown to 9mm, monitoring for SEGA transformation',
            'body_part' => 'Brain',
            'num_series' => 5,
            'num_instances' => 200,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2018-01-10',
            'description' => 'Brain MRI age 6 — SEGA 1.3cm with early ipsilateral ventricular enlargement, everolimus initiated',
            'body_part' => 'Brain',
            'num_series' => 6,
            'num_instances' => 220,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2019-01-10',
            'description' => 'Brain MRI age 7 — SEGA regressed to 0.9cm on everolimus, hydrocephalus resolved',
            'body_part' => 'Brain',
            'num_series' => 5,
            'num_instances' => 210,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2022-01-10',
            'description' => 'Brain MRI age 10 — SEGA stable at 0.9cm, no new lesions',
            'body_part' => 'Brain',
            'num_series' => 5,
            'num_instances' => 215,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2026-01-10',
            'description' => 'Brain MRI age 14 — SEGA stable, VNS artifact noted, cortical tubers unchanged',
            'body_part' => 'Brain',
            'num_series' => 5,
            'num_instances' => 225,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2020-01-10',
            'description' => 'Renal MRI age 8 — bilateral AMLs: right 2.1cm, left 1.5cm and 0.8cm',
            'body_part' => 'Kidneys',
            'num_series' => 3,
            'num_instances' => 120,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2022-01-10',
            'description' => 'Renal MRI age 10 — right AML grown to 3.5cm, left stable',
            'body_part' => 'Kidneys',
            'num_series' => 3,
            'num_instances' => 125,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2026-01-10',
            'description' => 'Renal MRI age 14 — AMLs stable on everolimus, right 3.2cm, left 1.4cm',
            'body_part' => 'Kidneys',
            'num_series' => 3,
            'num_instances' => 130,
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2023-01-10',
            'description' => 'Chest CT age 11 — LAM screening baseline, no cystic changes identified',
            'body_part' => 'Chest',
            'num_series' => 2,
            'num_instances' => 150,
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'TSC2',
            'variant' => 'c.5024C>T (p.Pro1675Leu)',
            'variant_type' => 'SNV',
            'chromosome' => '16',
            'position' => 2134900,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'mTOR_inhibitor_therapy',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Epilepsy',
            'era_start' => '2012-06-01',
            'era_end' => null,
            'occurrence_count' => 100,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Subependymal giant cell astrocytoma',
            'era_start' => '2018-01-01',
            'era_end' => null,
            'occurrence_count' => 6,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Renal angiomyolipomas',
            'era_start' => '2020-01-01',
            'era_end' => null,
            'occurrence_count' => 4,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Autism spectrum disorder',
            'era_start' => '2015-01-01',
            'era_end' => null,
            'occurrence_count' => 20,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Cardiac rhabdomyomas',
            'era_start' => '2012-01-08',
            'era_end' => '2015-01-01',
            'occurrence_count' => 5,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Vigabatrin',
            'era_start' => '2012-06-15',
            'era_end' => '2013-07-01',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Oxcarbazepine',
            'era_start' => '2013-07-15',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Everolimus',
            'era_start' => '2018-01-20',
            'era_end' => null,
            'gap_days' => 14,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Topical sirolimus',
            'era_start' => '2017-06-01',
            'era_end' => null,
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Cannabidiol (Epidiolex)',
            'era_start' => '2024-01-10',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
