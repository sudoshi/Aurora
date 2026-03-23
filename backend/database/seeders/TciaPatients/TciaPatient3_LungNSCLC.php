<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient3_LungNSCLC
{
    use DemoSeederHelper;

    protected function provenance(): array
    {
        return [
            'source_type' => 'synthetic',
            'source_id' => 'tcia_seeder_v1',
        ];
    }

    public function seed(): void
    {
        $patient = $this->createPatient([
            'mrn' => 'TCIA-LUAD-001',
            'first_name' => 'Maria',
            'last_name' => 'Gonzalez-Reyes',
            'date_of_birth' => '1967-07-29',
            'sex' => 'Female',
            'race' => 'White',
            'ethnicity' => 'Hispanic or Latino',
        ]);

        $this->addIdentifier($patient, 'tcia_collection', 'NSCLC-Radiomics', 'TCIA');
        $this->addIdentifier($patient, 'tcga_barcode', 'TCGA-55-8085', 'TCGA-LUAD');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Non-small cell lung carcinoma, adenocarcinoma subtype, Stage IIIB',
            'concept_code' => 'C34.12',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2023-09-05',
            'severity' => 'severe',
            'body_site' => 'Left upper lobe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Mediastinal lymphadenopathy',
            'concept_code' => 'R59.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2023-09-05',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'COPD',
            'concept_code' => 'J44.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2019-06-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Carboplatin AUC 5 + Pemetrexed 500mg/m²',
            'concept_code' => 'J9045',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 21 days x4',
            'start_date' => '2023-10-15',
            'end_date' => '2024-01-20',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Pembrolizumab 200mg',
            'concept_code' => 'J9271',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'dose_value' => 200,
            'dose_unit' => 'mg',
            'frequency' => 'every 21 days',
            'start_date' => '2023-10-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Pemetrexed 500mg/m² maintenance',
            'concept_code' => 'J9305',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 21 days',
            'start_date' => '2024-02-10',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'CT-guided percutaneous lung biopsy',
            'concept_code' => '32405',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2023-09-10',
            'body_site' => 'Left upper lobe',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Endobronchial ultrasound with mediastinal lymph node biopsy',
            'concept_code' => '31652',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2023-09-12',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Concurrent chemoradiation therapy',
            'concept_code' => '77412',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2023-10-20',
            'notes' => '60 Gy in 30 fractions',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2023-09-05',
            'facility' => 'Thoracic Oncology Center',
            'attending_provider' => 'Dr. Kevin Park',
            'department' => 'Pulmonary Oncology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2025-01-20',
            'facility' => 'Thoracic Oncology Center',
            'attending_provider' => 'Dr. Kevin Park',
            'department' => 'Medical Oncology',
        ]);

        // ── Notes ───────────────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Lung Biopsy Pathology — Molecular Results',
            'content' => "SPECIMEN: CT-guided core biopsy, left upper lobe\n\nFINAL DIAGNOSIS: Non-small cell lung carcinoma, adenocarcinoma\n\nIHC: TTF-1 positive, Napsin A positive, CK7 positive, p40 negative\nPD-L1 TPS: 60% (22C3)\n\nMOLECULAR PANEL (NGS):\n- ALK rearrangement: Negative\n- EGFR mutations: Negative\n- ROS1 fusion: Negative\n- KRAS G12C: Detected\n- STK11 Q37*: Detected (loss of function)\n- KEAP1 R272C: Detected\n- TMB: 11 mut/Mb (intermediate)\n- MSI: Stable\n\nCOMMENT: KRAS G12C mutation is targetable with sotorasib or adagrasib. Concurrent STK11/KEAP1 alterations may attenuate immunotherapy response.",
            'author' => 'Dr. Lisa Chang, Molecular Pathology',
            'authored_at' => '2023-09-18',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2023-09-06', [
            ['CEA', '2039-6', 18.5, 'ng/mL', 0, 3.0, 'H'],
            ['Hemoglobin', '718-7', 11.8, 'g/dL', 12.0, 16.0, 'L'],
            ['WBC', '6690-2', 11.2, 'x10^9/L', 4.5, 11.0, 'H'],
            ['Platelets', '777-3', 380, 'x10^9/L', 150, 400, null],
            ['LDH', '2532-0', 320, 'U/L', 140, 280, 'H'],
            ['Albumin', '1751-7', 3.4, 'g/dL', 3.5, 5.5, 'L'],
            ['Creatinine', '2160-0', 0.8, 'mg/dL', 0.6, 1.1, null],
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2023-09-05',
            'description' => 'CT Chest with contrast',
            'body_part' => 'Chest',
            'num_series' => 4,
            'num_instances' => 350,
            'dicom_endpoint' => 'orthanc',
        ]);

        $petStudy = $this->addImagingStudy($patient, [
            'modality' => 'PET',
            'study_date' => '2023-09-08',
            'description' => 'FDG PET/CT staging',
            'body_part' => 'Whole body',
            'num_series' => 6,
            'num_instances' => 900,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($petStudy, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 55.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Radiology',
            'measured_at' => '2023-09-08',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'KRAS',
            'variant' => 'G12C',
            'variant_type' => 'SNV',
            'chromosome' => '12',
            'position' => 25245350,
            'ref_allele' => 'C',
            'alt_allele' => 'A',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.32,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Sotorasib 960mg daily or Adagrasib 600mg BID',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'STK11',
            'variant' => 'Q37*',
            'variant_type' => 'SNV',
            'chromosome' => '19',
            'position' => 1220321,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.40,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Associated with reduced immunotherapy efficacy',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'KEAP1',
            'variant' => 'R272C',
            'variant_type' => 'SNV',
            'chromosome' => '19',
            'position' => 10491654,
            'ref_allele' => 'G',
            'alt_allele' => 'A',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.25,
            'clinical_significance' => 'likely_pathogenic',
            'actionability' => 'Adverse prognostic; may attenuate chemo-IO benefit',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'NSCLC adenocarcinoma',
            'era_start' => '2023-09-05',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Carboplatin/Pemetrexed/Pembrolizumab',
            'era_start' => '2023-10-15',
            'era_end' => '2024-01-20',
            'gap_days' => 0,
        ]);
    }
}
