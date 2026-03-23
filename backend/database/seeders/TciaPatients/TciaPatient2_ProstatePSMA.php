<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient2_ProstatePSMA
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
            'mrn' => 'TCIA-PRAD-001',
            'first_name' => 'William',
            'last_name' => 'Okafor',
            'date_of_birth' => '1962-11-05',
            'sex' => 'Male',
            'race' => 'Black or African American',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'tcia_collection', 'PSMA-PET-CT-Lesions', 'TCIA');
        $this->addIdentifier($patient, 'tcga_barcode', 'TCGA-G9-6498', 'TCGA-PRAD');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Prostate adenocarcinoma, Gleason 4+5=9',
            'concept_code' => 'C61',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2023-03-10',
            'severity' => 'severe',
            'body_site' => 'Prostate',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Osseous metastases, lumbar spine and pelvis',
            'concept_code' => 'C79.51',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-01-15',
            'body_site' => 'Lumbar spine, pelvis',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Castration-resistant prostate cancer',
            'concept_code' => 'C61',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-08-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Leuprolide depot 22.5mg',
            'concept_code' => 'J9217',
            'vocabulary' => 'HCPCS',
            'route' => 'IM',
            'dose_value' => 22.5,
            'dose_unit' => 'mg',
            'frequency' => 'every 3 months',
            'start_date' => '2023-04-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Enzalutamide 160mg',
            'concept_code' => '1312395',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 160,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2024-08-15',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Docetaxel 75mg/m²',
            'concept_code' => 'J9171',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 21 days x6',
            'start_date' => '2023-05-01',
            'end_date' => '2023-09-15',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Denosumab 120mg',
            'concept_code' => 'J0897',
            'vocabulary' => 'HCPCS',
            'route' => 'SC',
            'dose_value' => 120,
            'dose_unit' => 'mg',
            'frequency' => 'every 4 weeks',
            'start_date' => '2024-02-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Transrectal ultrasound-guided prostate biopsy (12-core)',
            'concept_code' => '55700',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2023-03-15',
            'body_site' => 'Prostate',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'PSMA PET/CT scan',
            'concept_code' => '78816',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-01-10',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2023-03-10',
            'facility' => 'Urology Cancer Center',
            'attending_provider' => 'Dr. James Rivera',
            'department' => 'Urology Oncology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-08-15',
            'facility' => 'Urology Cancer Center',
            'attending_provider' => 'Dr. James Rivera',
            'department' => 'Medical Oncology',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Prostate Biopsy Pathology Report',
            'content' => "SPECIMEN: Transrectal prostate biopsy, 12 cores\n\nFINAL DIAGNOSIS: Prostatic adenocarcinoma, acinar type\n- Gleason score: 4+5=9 (Grade Group 5)\n- 8/12 cores positive (67%)\n- Maximum core involvement: 90%\n- Perineural invasion: Present\n\nIMMUNOHISTOCHEMISTRY:\n- AMACR: Positive\n- p63: Negative in tumor\n- ERG: Positive (consistent with TMPRSS2-ERG fusion)\n- PTEN: Lost\n\nMOLECULAR: TMPRSS2-ERG fusion detected. PTEN loss by IHC confirmed.",
            'author' => 'Dr. Patricia Wong, Pathology',
            'authored_at' => '2023-03-20',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2023-03-10', [
            ['PSA', '2857-1', 48.6, 'ng/mL', 0, 4.0, 'H'],
            ['Free PSA', '10886-0', 4.2, '%', null, null, null],
            ['Testosterone', '2986-8', 320, 'ng/dL', 240, 950, null],
            ['Hemoglobin', '718-7', 12.8, 'g/dL', 13.5, 17.5, 'L'],
            ['Alkaline Phosphatase', '6768-6', 210, 'U/L', 44, 147, 'H'],
            ['LDH', '2532-0', 280, 'U/L', 140, 280, null],
        ]);

        $this->addLabPanel($patient, '2025-01-15', [
            ['PSA', '2857-1', 2.1, 'ng/mL', 0, 4.0, null],
            ['Testosterone', '2986-8', 18, 'ng/dL', 240, 950, 'L'],
            ['Hemoglobin', '718-7', 11.5, 'g/dL', 13.5, 17.5, 'L'],
            ['Alkaline Phosphatase', '6768-6', 128, 'U/L', 44, 147, null],
            ['Creatinine', '2160-0', 1.1, 'mg/dL', 0.7, 1.3, null],
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'PET',
            'study_date' => '2024-01-10',
            'description' => 'PSMA PET/CT whole body',
            'body_part' => 'Whole body',
            'num_series' => 6,
            'num_instances' => 800,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2023-03-12',
            'description' => 'MRI Prostate multiparametric',
            'body_part' => 'Pelvis',
            'num_series' => 8,
            'num_instances' => 400,
            'dicom_endpoint' => 'orthanc',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'TMPRSS2-ERG',
            'variant' => 'Fusion (T1:E4)',
            'variant_type' => 'fusion',
            'chromosome' => '21',
            'position' => 41498119,
            'allele_frequency' => 0.55,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Diagnostic marker; associated with response to abiraterone',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'PTEN',
            'variant' => 'Homozygous deletion',
            'variant_type' => 'CNV',
            'chromosome' => '10',
            'position' => 89623195,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'AKT inhibitor (ipatasertib/capivasertib) — clinical trial eligible',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'SPOP',
            'variant' => 'F133V',
            'variant_type' => 'SNV',
            'chromosome' => '17',
            'position' => 49621756,
            'ref_allele' => 'T',
            'alt_allele' => 'G',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.28,
            'clinical_significance' => 'likely_pathogenic',
            'actionability' => 'May predict sensitivity to BET inhibitors (investigational)',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Prostate adenocarcinoma',
            'era_start' => '2023-03-10',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'ADT (Leuprolide)',
            'era_start' => '2023-04-01',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Docetaxel',
            'era_start' => '2023-05-01',
            'era_end' => '2023-09-15',
            'gap_days' => 0,
        ]);
    }
}
