<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient1_PancreaticPDA
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
            'mrn' => 'TCIA-PDA-001',
            'first_name' => 'Harold',
            'last_name' => 'Nakamura',
            'date_of_birth' => '1954-03-17',
            'sex' => 'Male',
            'race' => 'Asian',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'tcia_subject', 'C3L-00189', 'CPTAC-PDA');
        $this->addIdentifier($patient, 'tcia_collection', 'CPTAC-PDA', 'TCIA');
        $this->addIdentifier($patient, 'cptac_barcode', 'CPT0019150009', 'CPTAC-3');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Pancreatic ductal adenocarcinoma, head of pancreas',
            'concept_code' => 'C25.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-06-15',
            'severity' => 'severe',
            'body_site' => 'Head of pancreas',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hepatic metastases',
            'concept_code' => 'C78.7',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-09-20',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Biliary obstruction',
            'concept_code' => 'K83.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'resolved',
            'onset_date' => '2024-06-18',
            'resolution_date' => '2024-07-02',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Type 2 diabetes mellitus',
            'concept_code' => 'E11.9',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2018-01-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'FOLFIRINOX (5-FU/leucovorin/irinotecan/oxaliplatin)',
            'concept_code' => 'J9999',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 14 days',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'status' => 'completed',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Gemcitabine 1000mg/m² + nab-paclitaxel 125mg/m²',
            'concept_code' => 'J9201',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'days 1,8,15 q28d',
            'start_date' => '2025-01-10',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Creon (pancrelipase) 50,000 units',
            'concept_code' => '861356',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 50000,
            'dose_unit' => 'units',
            'frequency' => 'with meals',
            'start_date' => '2024-07-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Metformin 1000mg',
            'concept_code' => '861007',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 1000,
            'dose_unit' => 'mg',
            'frequency' => 'twice daily',
            'start_date' => '2018-03-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'EUS-guided fine needle biopsy of pancreas',
            'concept_code' => '43242',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-06-20',
            'body_site' => 'Head of pancreas',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Biliary stent placement (ERCP)',
            'concept_code' => '43274',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2024-06-25',
            'body_site' => 'Common bile duct',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Port-a-cath placement',
            'concept_code' => '36561',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2024-07-10',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $diagVisit = $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'admission_date' => '2024-06-15',
            'discharge_date' => '2024-06-28',
            'facility' => 'NCI Comprehensive Cancer Center',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Hepatobiliary Oncology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-07-15',
            'facility' => 'NCI Infusion Center',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Medical Oncology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2025-02-01',
            'facility' => 'NCI Comprehensive Cancer Center',
            'attending_provider' => 'Dr. Sarah Chen',
            'department' => 'Medical Oncology',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Surgical Pathology Report — Pancreas FNB',
            'content' => "SPECIMEN: EUS-guided fine needle biopsy, head of pancreas\n\nFINAL DIAGNOSIS: Pancreatic ductal adenocarcinoma, moderately differentiated\n\nMICROSCOPIC: Sections show infiltrating glandular neoplasm with desmoplastic stroma. Tumor cells form irregular glands with nuclear atypia, prominent nucleoli, and mitotic activity. Perineural invasion present.\n\nIMMUNOHISTOCHEMISTRY:\n- CK7: Positive\n- CK20: Negative\n- CDX2: Negative\n- MUC1: Positive\n- SMAD4: Lost (absent)\n- p53: Aberrant overexpression\n\nMOLECULAR: KRAS G12D mutation detected by NGS. MSI-stable. TMB: 3 mut/Mb.\n\nDIAGNOSIS: Consistent with pancreatic ductal adenocarcinoma.",
            'author' => 'Dr. Michael Torres, Pathology',
            'authored_at' => '2024-06-22',
            'visit_id' => $diagVisit->id,
        ]);

        $this->addNote($patient, [
            'note_type' => 'oncology_consult',
            'title' => 'Medical Oncology Initial Consultation',
            'content' => "ASSESSMENT:\n70-year-old male with newly diagnosed pancreatic ductal adenocarcinoma, head of pancreas, with hepatic metastases (Stage IV). Molecular profiling: KRAS G12D, TP53 R175H, SMAD4 loss. MSI-stable, TMB-low.\n\nPLAN:\n1. Start FOLFIRINOX chemotherapy\n2. Biliary stent placed for obstruction — resolved\n3. Pancreatic enzyme replacement initiated\n4. Restaging CT after 4 cycles\n5. Consider clinical trial (KRAS G12D-specific inhibitor) if progression",
            'author' => 'Dr. Sarah Chen, Medical Oncology',
            'authored_at' => '2024-07-01',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2024-06-16', [
            ['CA 19-9', '24108-3', 1842.0, 'U/mL', 0, 37, 'H'],
            ['CEA', '2039-6', 12.4, 'ng/mL', 0, 3.0, 'H'],
            ['Total Bilirubin', '1975-2', 8.2, 'mg/dL', 0.1, 1.2, 'H'],
            ['Direct Bilirubin', '1968-7', 6.1, 'mg/dL', 0, 0.3, 'H'],
            ['ALT', '1742-6', 188, 'U/L', 7, 56, 'H'],
            ['AST', '1920-8', 142, 'U/L', 10, 40, 'H'],
            ['Alkaline Phosphatase', '6768-6', 540, 'U/L', 44, 147, 'H'],
            ['Hemoglobin', '718-7', 11.2, 'g/dL', 13.5, 17.5, 'L'],
            ['WBC', '6690-2', 8.4, 'x10^9/L', 4.5, 11.0, null],
            ['Platelets', '777-3', 310, 'x10^9/L', 150, 400, null],
            ['HbA1c', '4548-4', 7.8, '%', null, 6.5, 'H'],
            ['Albumin', '1751-7', 3.1, 'g/dL', 3.5, 5.5, 'L'],
        ]);

        // Post-treatment labs
        $this->addLabPanel($patient, '2025-01-05', [
            ['CA 19-9', '24108-3', 420.0, 'U/mL', 0, 37, 'H'],
            ['CEA', '2039-6', 5.8, 'ng/mL', 0, 3.0, 'H'],
            ['Total Bilirubin', '1975-2', 1.1, 'mg/dL', 0.1, 1.2, null],
            ['Hemoglobin', '718-7', 10.8, 'g/dL', 13.5, 17.5, 'L'],
            ['Albumin', '1751-7', 3.3, 'g/dL', 3.5, 5.5, 'L'],
            ['Neutrophils', '751-8', 3.2, 'x10^9/L', 1.5, 8.0, null],
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $ctDiag = $this->addImagingStudy($patient, [
            'study_uid' => '1.3.6.1.4.1.14519.5.2.1.1078.3273.382194720873684027956624363347',
            'modality' => 'CT',
            'study_date' => '2024-06-16',
            'description' => 'CT Abdomen Pelvis with contrast',
            'body_part' => 'Abdomen',
            'num_series' => 3,
            'num_instances' => 180,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($ctDiag, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 42.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Imaging',
            'measured_at' => '2024-06-16',
        ]);

        $ctRestage = $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-11-15',
            'description' => 'CT Abdomen Pelvis restaging',
            'body_part' => 'Abdomen',
            'num_series' => 3,
            'num_instances' => 200,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($ctRestage, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 35.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Imaging',
            'measured_at' => '2024-11-15',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'KRAS',
            'variant' => 'G12D',
            'variant_type' => 'SNV',
            'chromosome' => '12',
            'position' => 25245350,
            'ref_allele' => 'C',
            'alt_allele' => 'A',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.38,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'KRAS G12D inhibitor (MRTX1133) — clinical trial',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'TP53',
            'variant' => 'R175H',
            'variant_type' => 'SNV',
            'chromosome' => '17',
            'position' => 7578406,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.42,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'No approved targeted therapy',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'SMAD4',
            'variant' => 'R361C',
            'variant_type' => 'SNV',
            'chromosome' => '18',
            'position' => 51065544,
            'ref_allele' => 'G',
            'alt_allele' => 'A',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.35,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'No approved targeted therapy; prognostic — associated with worse survival',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'CDKN2A',
            'variant' => 'Homozygous deletion',
            'variant_type' => 'CNV',
            'chromosome' => '9',
            'position' => 21967751,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'CDK4/6 inhibitor sensitivity (investigational)',
        ]);

        // ── Condition & Drug Eras ───────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Pancreatic ductal adenocarcinoma',
            'era_start' => '2024-06-15',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'FOLFIRINOX',
            'era_start' => '2024-07-15',
            'era_end' => '2024-12-20',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Gemcitabine + nab-paclitaxel',
            'era_start' => '2025-01-10',
            'gap_days' => 0,
        ]);
    }
}
