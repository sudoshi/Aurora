<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient7_LungAdenoKRAS
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
            'mrn' => 'TCIA-LUAD-002',
            'first_name' => 'Thomas',
            'last_name' => 'McCarthy',
            'date_of_birth' => '1951-12-08',
            'sex' => 'Male',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        $this->addIdentifier($patient, 'tcga_barcode', 'TCGA-49-4488', 'TCGA-LUAD');
        $this->addIdentifier($patient, 'tcia_collection', 'TCGA-LUAD', 'TCIA');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Lung adenocarcinoma, right lower lobe, Stage IV (M1b)',
            'concept_code' => 'C34.31',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-03-22',
            'severity' => 'severe',
            'body_site' => 'Right lower lobe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Adrenal metastasis, left',
            'concept_code' => 'C79.71',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-03-25',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Atrial fibrillation, chronic',
            'concept_code' => 'I48.2',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2020-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'History of 40 pack-year tobacco use',
            'concept_code' => 'Z87.891',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '1970-01-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Sotorasib 960mg',
            'concept_code' => '2549088',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 960,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2024-05-01',
            'end_date' => '2024-11-15',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Adagrasib 600mg',
            'concept_code' => '2597061',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 600,
            'dose_unit' => 'mg',
            'frequency' => 'twice daily',
            'start_date' => '2024-12-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Apixaban 5mg',
            'concept_code' => '1364430',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 5,
            'dose_unit' => 'mg',
            'frequency' => 'twice daily',
            'start_date' => '2020-07-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Bronchoscopy with transbronchial biopsy',
            'concept_code' => '31628',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-03-28',
            'body_site' => 'Right lower lobe',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-03-22',
            'facility' => 'Lung Cancer Center of Excellence',
            'attending_provider' => 'Dr. Andrea Walsh',
            'department' => 'Thoracic Oncology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-12-01',
            'facility' => 'Lung Cancer Center of Excellence',
            'attending_provider' => 'Dr. Andrea Walsh',
            'department' => 'Thoracic Oncology',
        ]);

        // ── Notes ───────────────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'oncology_consult',
            'title' => 'Medical Oncology — KRAS G12C Targeted Therapy Plan',
            'content' => "ASSESSMENT:\n73-year-old male, former heavy smoker (40 pack-years), newly diagnosed Stage IV lung adenocarcinoma (right lower lobe primary, left adrenal metastasis). Molecular: KRAS G12C, STK11 loss-of-function, TP53 R273H. PD-L1 TPS 5%.\n\nIMSCORE: Intermediate risk\n\nDISCUSSION:\nGiven KRAS G12C driver + concurrent STK11 loss + low PD-L1, targeted therapy preferred over immunotherapy-based approach.\n\nPLAN:\n1. Start sotorasib 960mg daily\n2. CT restaging every 8 weeks\n3. Liquid biopsy at progression for resistance mechanisms\n4. If progression on sotorasib: switch to adagrasib (CodeBreaK 200 → KRYSTAL-7)\n5. Hold anticoagulation discussions with cardiology re: atrial fibrillation management",
            'author' => 'Dr. Andrea Walsh, Thoracic Oncology',
            'authored_at' => '2024-04-25',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2024-03-23', [
            ['CEA', '2039-6', 28.0, 'ng/mL', 0, 3.0, 'H'],
            ['Hemoglobin', '718-7', 13.2, 'g/dL', 13.5, 17.5, 'L'],
            ['WBC', '6690-2', 9.4, 'x10^9/L', 4.5, 11.0, null],
            ['LDH', '2532-0', 260, 'U/L', 140, 280, null],
            ['Albumin', '1751-7', 3.6, 'g/dL', 3.5, 5.5, null],
            ['AST', '1920-8', 35, 'U/L', 10, 40, null],
            ['ALT', '1742-6', 28, 'U/L', 7, 56, null],
            ['INR', '6301-6', 1.1, '', 0.9, 1.1, null],
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $ctStudy = $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-03-22',
            'description' => 'CT Chest Abdomen Pelvis with contrast',
            'body_part' => 'Chest/Abdomen/Pelvis',
            'num_series' => 5,
            'num_instances' => 500,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($ctStudy, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 48.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Radiology',
            'measured_at' => '2024-03-22',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'KRAS',
            'variant' => 'G12C',
            'variant_type' => 'SNV',
            'chromosome' => '12',
            'position' => 25245350,
            'ref_allele' => 'G',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.35,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Sotorasib (Lumakras) or Adagrasib (Krazati) — FDA approved',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'STK11',
            'variant' => 'E199*',
            'variant_type' => 'SNV',
            'chromosome' => '19',
            'position' => 1220446,
            'ref_allele' => 'G',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.42,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Reduces immunotherapy benefit; favors KRAS-targeted approach',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'TP53',
            'variant' => 'R273H',
            'variant_type' => 'SNV',
            'chromosome' => '17',
            'position' => 7577120,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.48,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'No direct targeted therapy',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Lung adenocarcinoma Stage IV',
            'era_start' => '2024-03-22',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Sotorasib',
            'era_start' => '2024-05-01',
            'era_end' => '2024-11-15',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Adagrasib',
            'era_start' => '2024-12-01',
            'gap_days' => 0,
        ]);
    }
}
