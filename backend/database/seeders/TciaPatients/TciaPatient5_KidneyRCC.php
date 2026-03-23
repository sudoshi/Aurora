<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient5_KidneyRCC
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
            'mrn' => 'TCIA-KIRC-001',
            'first_name' => 'Robert',
            'last_name' => 'Andersen',
            'date_of_birth' => '1960-10-03',
            'sex' => 'Male',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        $this->addIdentifier($patient, 'tcga_barcode', 'TCGA-CJ-4900', 'TCGA-KIRC');
        $this->addIdentifier($patient, 'tcia_collection', 'TCGA-KIRC', 'TCIA');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Renal cell carcinoma, clear cell type, Stage III (pT3aN1M0)',
            'concept_code' => 'C64.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2023-11-12',
            'severity' => 'severe',
            'body_site' => 'Left kidney',
            'laterality' => 'Left',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Pulmonary metastases',
            'concept_code' => 'C78.00',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-06-15',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hypertension',
            'concept_code' => 'I10',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-01-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Nivolumab 3mg/kg + Ipilimumab 1mg/kg',
            'concept_code' => 'J9299',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 21 days x4, then nivo q14d',
            'start_date' => '2024-07-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Cabozantinib 40mg',
            'concept_code' => '1867949',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 40,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2024-07-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Amlodipine 10mg',
            'concept_code' => '329528',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 10,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2015-03-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Left radical nephrectomy with hilar lymph node dissection',
            'concept_code' => '50230',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2023-12-10',
            'body_site' => 'Left kidney',
            'laterality' => 'Left',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'admission_date' => '2023-12-09',
            'discharge_date' => '2023-12-14',
            'facility' => 'University Medical Center',
            'attending_provider' => 'Dr. Nathan Brooks',
            'department' => 'Urologic Surgery',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-07-01',
            'facility' => 'University Cancer Center',
            'attending_provider' => 'Dr. Priya Desai',
            'department' => 'Genitourinary Oncology',
        ]);

        // ── Notes ───────────────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Nephrectomy Pathology Report',
            'content' => "SPECIMEN: Left radical nephrectomy with hilar lymph nodes\n\nFINAL DIAGNOSIS: Clear cell renal cell carcinoma\n- Size: 8.2 cm\n- Fuhrman nuclear grade: 3\n- Renal vein invasion: Present\n- Perinephric fat invasion: Absent\n- Surgical margins: Negative\n- Lymph nodes: 1/6 positive\n- Stage: pT3aN1\n\nIHC: PAX8+, CA-IX+, CD10+, CK7-\n\nMOLECULAR:\n- VHL biallelic inactivation (c.227T>G missense + LOH chr3p)\n- PBRM1 truncating (Q1298*)\n- SETD2 frameshift\n- No MTOR/BAP1/PTEN alterations",
            'author' => 'Dr. Gregory Adams, Genitourinary Pathology',
            'authored_at' => '2023-12-15',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2024-06-20', [
            ['Hemoglobin', '718-7', 10.2, 'g/dL', 13.5, 17.5, 'L'],
            ['LDH', '2532-0', 310, 'U/L', 140, 280, 'H'],
            ['Corrected Calcium', '17861-6', 10.8, 'mg/dL', 8.5, 10.5, 'H'],
            ['Creatinine', '2160-0', 1.4, 'mg/dL', 0.7, 1.3, 'H'],
            ['eGFR', '33914-3', 52, 'mL/min/1.73m²', 60, null, 'L'],
            ['Neutrophils', '751-8', 6.8, 'x10^9/L', 1.5, 8.0, null],
            ['Platelets', '777-3', 420, 'x10^9/L', 150, 400, 'H'],
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2023-11-12',
            'description' => 'CT Abdomen Pelvis with/without contrast',
            'body_part' => 'Abdomen',
            'num_series' => 4,
            'num_instances' => 400,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-06-15',
            'description' => 'CT Chest Abdomen Pelvis restaging',
            'body_part' => 'Chest/Abdomen/Pelvis',
            'num_series' => 5,
            'num_instances' => 500,
            'dicom_endpoint' => 'orthanc',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'VHL',
            'variant' => 'L89P',
            'variant_type' => 'SNV',
            'chromosome' => '3',
            'position' => 10183842,
            'ref_allele' => 'T',
            'alt_allele' => 'C',
            'zygosity' => 'homozygous',
            'allele_frequency' => 0.85,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'HIF-2α inhibitor (belzutifan) approved for VHL-driven RCC',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'PBRM1',
            'variant' => 'Q1298*',
            'variant_type' => 'SNV',
            'chromosome' => '3',
            'position' => 52609977,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.40,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'May predict favorable response to checkpoint immunotherapy',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'SETD2',
            'variant' => 'p.K1601fs',
            'variant_type' => 'indel',
            'chromosome' => '3',
            'position' => 47057898,
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.30,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Adverse prognostic; no targeted therapy',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Clear cell renal cell carcinoma',
            'era_start' => '2023-11-12',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Nivolumab/Ipilimumab + Cabozantinib',
            'era_start' => '2024-07-01',
            'gap_days' => 0,
        ]);
    }
}
