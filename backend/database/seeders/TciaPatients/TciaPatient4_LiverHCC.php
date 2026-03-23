<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient4_LiverHCC
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
            'mrn' => 'TCIA-LIHC-001',
            'first_name' => 'Dae-Jung',
            'last_name' => 'Kim',
            'date_of_birth' => '1958-02-14',
            'sex' => 'Male',
            'race' => 'Asian',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        $this->addIdentifier($patient, 'tcia_subject', 'HCC_018', 'HCC-TACE-Seg');
        $this->addIdentifier($patient, 'tcia_collection', 'HCC-TACE-Seg', 'TCIA');
        $this->addIdentifier($patient, 'tcga_barcode', 'TCGA-CC-A7IE', 'TCGA-LIHC');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Hepatocellular carcinoma, BCLC stage B',
            'concept_code' => 'C22.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-02-20',
            'severity' => 'severe',
            'body_site' => 'Right hepatic lobe',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hepatitis B virus infection, chronic',
            'concept_code' => 'B18.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2005-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Cirrhosis of liver, Child-Pugh A',
            'concept_code' => 'K74.60',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2020-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Portal hypertension',
            'concept_code' => 'K76.6',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2021-01-01',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Entecavir 0.5mg',
            'concept_code' => '597723',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 0.5,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2005-03-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Atezolizumab 1200mg + Bevacizumab 15mg/kg',
            'concept_code' => 'J9022',
            'vocabulary' => 'HCPCS',
            'route' => 'IV',
            'frequency' => 'every 21 days',
            'start_date' => '2024-06-01',
            'status' => 'active',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Propranolol 40mg',
            'concept_code' => '8787',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 40,
            'dose_unit' => 'mg',
            'frequency' => 'twice daily',
            'start_date' => '2021-02-01',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Liver biopsy, percutaneous',
            'concept_code' => '47000',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-02-25',
            'body_site' => 'Right hepatic lobe',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Transarterial chemoembolization (TACE)',
            'concept_code' => '37243',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-03-15',
            'body_site' => 'Right hepatic artery',
            'notes' => 'Drug-eluting bead TACE with doxorubicin',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Transarterial chemoembolization (TACE), repeat',
            'concept_code' => '37243',
            'vocabulary' => 'CPT',
            'domain' => 'oncology',
            'performed_date' => '2024-05-10',
            'body_site' => 'Right hepatic artery',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'admission_date' => '2024-03-14',
            'discharge_date' => '2024-03-17',
            'facility' => 'Hepatobiliary Center',
            'attending_provider' => 'Dr. Amir Hassani',
            'department' => 'Interventional Radiology',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-06-01',
            'facility' => 'Hepatobiliary Center',
            'attending_provider' => 'Dr. Amir Hassani',
            'department' => 'Hepatology Oncology',
        ]);

        // ── Notes ───────────────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Liver Core Biopsy Pathology',
            'content' => "SPECIMEN: Percutaneous core biopsy, right hepatic lobe\n\nFINAL DIAGNOSIS: Hepatocellular carcinoma, moderately differentiated (Edmondson-Steiner Grade II-III)\n\nBACKGROUND: Chronic hepatitis B with established cirrhosis\n\nIHC: HepPar-1 positive, Arginase-1 positive, Glypican-3 positive, CK7 focal\n\nMOLECULAR:\n- TP53 Y220C: Detected\n- CTNNB1 S45P: Detected (beta-catenin activation)\n- TERT promoter C228T: Detected\n- TMB: 5 mut/Mb\n- MSI: Stable",
            'author' => 'Dr. Rachel Foster, Hepatopathology',
            'authored_at' => '2024-03-01',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2024-02-20', [
            ['AFP', '1834-1', 842.0, 'ng/mL', 0, 8.3, 'H'],
            ['AFP-L3', '59564-8', 22.0, '%', null, 10, 'H'],
            ['DCP (PIVKA-II)', '48345-3', 180, 'mAU/mL', 0, 40, 'H'],
            ['Total Bilirubin', '1975-2', 1.8, 'mg/dL', 0.1, 1.2, 'H'],
            ['Albumin', '1751-7', 3.2, 'g/dL', 3.5, 5.5, 'L'],
            ['INR', '6301-6', 1.3, '', 0.9, 1.1, 'H'],
            ['Platelets', '777-3', 88, 'x10^9/L', 150, 400, 'L'],
            ['AST', '1920-8', 72, 'U/L', 10, 40, 'H'],
            ['ALT', '1742-6', 58, 'U/L', 7, 56, 'H'],
            ['HBV DNA', '5009-6', 45, 'IU/mL', null, 20, 'H'],
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $ctStudy = $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-02-22',
            'description' => 'CT Liver triple-phase (arterial, portal, delayed)',
            'body_part' => 'Abdomen',
            'num_series' => 5,
            'num_instances' => 600,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($ctStudy, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 65.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Radiology',
            'measured_at' => '2024-02-22',
        ]);

        $postTace = $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-04-15',
            'description' => 'CT Liver post-TACE evaluation',
            'body_part' => 'Abdomen',
            'num_series' => 5,
            'num_instances' => 600,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingMeasurement($postTace, [
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => 58.0,
            'unit' => 'mm',
            'measured_by' => 'Dr. Radiology',
            'measured_at' => '2024-04-15',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'TP53',
            'variant' => 'Y220C',
            'variant_type' => 'SNV',
            'chromosome' => '17',
            'position' => 7578190,
            'ref_allele' => 'T',
            'alt_allele' => 'C',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.45,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'TP53 Y220C reactivator (PC14586) — clinical trial',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'CTNNB1',
            'variant' => 'S45P',
            'variant_type' => 'SNV',
            'chromosome' => '3',
            'position' => 41266101,
            'ref_allele' => 'T',
            'alt_allele' => 'C',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.38,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Wnt pathway activation; may predict immune-excluded phenotype',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'TERT',
            'variant' => 'Promoter C228T',
            'variant_type' => 'SNV',
            'chromosome' => '5',
            'position' => 1295228,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'allele_frequency' => 0.52,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Diagnostic marker for HCC; no direct targeted therapy',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Hepatocellular carcinoma',
            'era_start' => '2024-02-20',
            'occurrence_count' => 1,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Chronic hepatitis B',
            'era_start' => '2005-01-01',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Entecavir (HBV antiviral)',
            'era_start' => '2005-03-01',
            'gap_days' => 0,
        ]);
    }
}
