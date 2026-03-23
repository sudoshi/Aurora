<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient6_BreastBRCA
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
            'mrn' => 'TCIA-BRCA-001',
            'first_name' => 'Amara',
            'last_name' => 'Johnson-Williams',
            'date_of_birth' => '1975-04-22',
            'sex' => 'Female',
            'race' => 'Black or African American',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        $this->addIdentifier($patient, 'tcga_barcode', 'TCGA-BH-A0BD', 'TCGA-BRCA');
        $this->addIdentifier($patient, 'tcia_collection', 'TCGA-BRCA', 'TCIA');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Invasive ductal carcinoma, left breast, ER+/PR+/HER2-, Stage IIA',
            'concept_code' => 'C50.412',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-01-08',
            'severity' => 'moderate',
            'body_site' => 'Left breast, upper outer quadrant',
            'laterality' => 'Left',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'BRCA1 germline mutation carrier',
            'concept_code' => 'Z15.01',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-02-15',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Family history of breast and ovarian cancer',
            'concept_code' => 'Z80.3',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2024-01-08',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Dose-dense AC (doxorubicin/cyclophosphamide)',
            'concept_code' => 'J9000',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 14 days x4',
            'start_date' => '2024-02-20',
            'end_date' => '2024-04-15',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Paclitaxel 80mg/m² weekly',
            'concept_code' => 'J9267',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'weekly x12',
            'start_date' => '2024-04-29',
            'end_date' => '2024-07-15',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Olaparib 300mg',
            'concept_code' => '1789889',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 300,
            'dose_unit' => 'mg',
            'frequency' => 'twice daily',
            'start_date' => '2024-10-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Anastrozole 1mg',
            'concept_code' => '84857',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 1,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2024-10-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Stereotactic core needle biopsy, left breast',
            'concept_code' => '19083',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-01-12',
            'body_site' => 'Left breast',
            'laterality' => 'Left',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Left lumpectomy with sentinel lymph node biopsy',
            'concept_code' => '19301',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2024-08-20',
            'body_site' => 'Left breast',
            'laterality' => 'Left',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Adjuvant radiation therapy, left breast',
            'concept_code' => '77412',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-09-15',
            'notes' => 'Whole breast radiation 40 Gy in 15 fractions + boost',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-01-08',
            'facility' => 'Breast Cancer Center',
            'attending_provider' => 'Dr. Michelle Laurent',
            'department' => 'Breast Oncology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient_procedure',
            'admission_date' => '2024-08-20',
            'facility' => 'Breast Cancer Center',
            'attending_provider' => 'Dr. Susan Blake',
            'department' => 'Surgical Oncology',
        ]);

        // ── Notes ───────────────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Breast Core Biopsy & Surgical Pathology',
            'content' => "SPECIMEN: Left breast lumpectomy + sentinel lymph nodes\n\nFINAL DIAGNOSIS: Invasive ductal carcinoma, NOS\n- Size: 2.8 cm\n- Nottingham grade: 2 (tubule 3, nuclear 2, mitosis 2 = score 7)\n- Margins: Negative (closest 3mm)\n- Sentinel lymph nodes: 0/3 positive\n- LVI: Not identified\n- Stage: pT2N0\n\nBIOMARKERS:\n- ER: Positive (95%, Allred 8)\n- PR: Positive (60%, Allred 7)\n- HER2: Negative (IHC 1+)\n- Ki-67: 25%\n\nOncotype DX Recurrence Score: 28 (high)\n\nGERMLINE: BRCA1 c.5266dupC (5382insC) — pathogenic\n\nPLAN: Neoadjuvant chemo → surgery → adjuvant olaparib (OlympiA) + endocrine therapy",
            'author' => 'Dr. Ellen Rodriguez, Breast Pathology',
            'authored_at' => '2024-08-25',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2024-01-10', [
            ['CA 15-3', '6875-9', 42.0, 'U/mL', 0, 30, 'H'],
            ['CEA', '2039-6', 3.2, 'ng/mL', 0, 3.0, 'H'],
            ['Hemoglobin', '718-7', 12.5, 'g/dL', 12.0, 16.0, null],
            ['WBC', '6690-2', 7.8, 'x10^9/L', 4.5, 11.0, null],
            ['Platelets', '777-3', 280, 'x10^9/L', 150, 400, null],
            ['Creatinine', '2160-0', 0.7, 'mg/dL', 0.6, 1.1, null],
            ['ALT', '1742-6', 22, 'U/L', 7, 56, null],
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2024-01-15',
            'description' => 'MRI Breast bilateral with contrast',
            'body_part' => 'Breast',
            'num_series' => 10,
            'num_instances' => 600,
            'dicom_endpoint' => 'orthanc',
        ]);

        $mammo = $this->addImagingStudy($patient, [
            'modality' => 'MG',
            'study_date' => '2024-01-08',
            'description' => 'Diagnostic mammogram bilateral',
            'body_part' => 'Breast',
            'num_series' => 4,
            'num_instances' => 8,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($mammo, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 28.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Radiology',
            'measured_at' => '2024-01-08',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'BRCA1',
            'variant' => 'c.5266dupC (5382insC)',
            'variant_type' => 'indel',
            'chromosome' => '17',
            'position' => 43057065,
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'PARP inhibitor (olaparib) — FDA approved adjuvant (OlympiA)',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'PIK3CA',
            'variant' => 'H1047R',
            'variant_type' => 'SNV',
            'chromosome' => '3',
            'position' => 179234297,
            'ref_allele' => 'A',
            'alt_allele' => 'G',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.18,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Alpelisib (PI3K inhibitor) if progresses on endocrine therapy',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'TP53',
            'variant' => 'R248W',
            'variant_type' => 'SNV',
            'chromosome' => '17',
            'position' => 7577538,
            'ref_allele' => 'G',
            'alt_allele' => 'A',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.35,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Adverse prognostic marker',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Invasive ductal carcinoma, left breast',
            'era_start' => '2024-01-08',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Neoadjuvant AC-T',
            'era_start' => '2024-02-20',
            'era_end' => '2024-07-15',
            'gap_days' => 14,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Olaparib + Anastrozole',
            'era_start' => '2024-10-01',
            'gap_days' => 0,
        ]);
    }
}
