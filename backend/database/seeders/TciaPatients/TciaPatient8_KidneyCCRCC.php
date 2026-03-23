<?php

namespace Database\Seeders\TciaPatients;

use Database\Seeders\DemoPatients\DemoSeederHelper;

class TciaPatient8_KidneyCCRCC
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
            'mrn' => 'TCIA-CCRCC-001',
            'first_name' => 'Eleanor',
            'last_name' => 'Petrov',
            'date_of_birth' => '1970-06-30',
            'sex' => 'Female',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        $this->addIdentifier($patient, 'tcia_collection', 'CPTAC-CCRCC', 'TCIA');
        $this->addIdentifier($patient, 'cptac_barcode', 'CPT0000790001', 'CPTAC-3');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Clear cell renal cell carcinoma, right kidney, Stage II (pT2aN0M0)',
            'concept_code' => 'C64.2',
            'vocabulary' => 'ICD10CM',
            'domain' => 'oncology',
            'status' => 'active',
            'onset_date' => '2024-04-10',
            'severity' => 'moderate',
            'body_site' => 'Right kidney',
            'laterality' => 'Right',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Von Hippel-Lindau syndrome',
            'concept_code' => 'Q85.8',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2024-05-20',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Retinal hemangioblastoma, right eye',
            'concept_code' => 'D31.20',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2019-08-01',
            'laterality' => 'Right',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Belzutifan 120mg',
            'concept_code' => '2560352',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 120,
            'dose_unit' => 'mg',
            'frequency' => 'daily',
            'start_date' => '2024-07-15',
            'status' => 'active',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Right partial nephrectomy (nephron-sparing)',
            'concept_code' => '50240',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2024-05-08',
            'body_site' => 'Right kidney',
            'laterality' => 'Right',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Retinal laser photocoagulation',
            'concept_code' => '67210',
            'vocabulary' => 'CPT',
            'domain' => 'surgical',
            'performed_date' => '2019-09-01',
            'body_site' => 'Right eye',
            'laterality' => 'Right',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $this->addVisit($patient, [
            'visit_type' => 'inpatient',
            'admission_date' => '2024-05-07',
            'discharge_date' => '2024-05-11',
            'facility' => 'VHL Center of Excellence',
            'attending_provider' => 'Dr. Sandra Kim',
            'department' => 'Urologic Surgery',
        ]);

        $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'admission_date' => '2024-07-15',
            'facility' => 'VHL Center of Excellence',
            'attending_provider' => 'Dr. Sandra Kim',
            'department' => 'Medical Oncology',
        ]);

        // ── Notes ───────────────────────────────────────────────
        $this->addNote($patient, [
            'note_type' => 'pathology',
            'title' => 'Partial Nephrectomy Pathology Report',
            'content' => "SPECIMEN: Right partial nephrectomy\n\nFINAL DIAGNOSIS: Clear cell renal cell carcinoma\n- Size: 7.8 cm\n- Fuhrman nuclear grade: 2\n- Margins: Negative (closest 5mm)\n- No renal vein or sinus invasion\n- Stage: pT2a\n\nIHC: PAX8+, CA-IX+, CK7-\n\nMOLECULAR:\n- VHL germline: c.499C>T (R167W) — confirmed VHL disease\n- VHL somatic LOH chromosome 3p confirmed\n- BAP1: Retained (intact)\n- SETD2: Wild type\n- PBRM1: Truncating mutation (c.2590C>T, Q864*)\n\nCOMMENT: VHL-associated clear cell RCC. Belzutifan (HIF-2α inhibitor) FDA-approved for VHL-associated RCC.",
            'author' => 'Dr. Ian Douglas, Genitourinary Pathology',
            'authored_at' => '2024-05-12',
        ]);

        $this->addNote($patient, [
            'note_type' => 'genetics_consult',
            'title' => 'Clinical Genetics — VHL Disease Confirmation',
            'content' => "ASSESSMENT:\n54-year-old female with clear cell RCC + retinal hemangioblastoma. Germline VHL c.499C>T (R167W) confirmed.\n\nVHL SURVEILLANCE PLAN:\n- Annual MRI brain/spine (hemangioblastoma screening)\n- Annual CT or MRI abdomen (RCC and pheochromocytoma)\n- Annual ophthalmology (retinal hemangioblastoma)\n- Annual metanephrines (pheochromocytoma screening)\n- Genetic counseling for first-degree relatives",
            'author' => 'Dr. Claire Huang, Clinical Genetics',
            'authored_at' => '2024-06-01',
        ]);

        // ── Labs ────────────────────────────────────────────────
        $this->addLabPanel($patient, '2024-04-12', [
            ['Hemoglobin', '718-7', 14.8, 'g/dL', 12.0, 16.0, null],
            ['Creatinine', '2160-0', 0.9, 'mg/dL', 0.6, 1.1, null],
            ['eGFR', '33914-3', 78, 'mL/min/1.73m²', 60, null, null],
            ['LDH', '2532-0', 180, 'U/L', 140, 280, null],
            ['Calcium', '17861-6', 9.8, 'mg/dL', 8.5, 10.5, null],
            ['Plasma Metanephrines', '2668-2', 42, 'pg/mL', null, 57, null],
            ['Plasma Normetanephrines', '2668-2', 108, 'pg/mL', null, 148, null],
        ]);

        // ── Imaging ─────────────────────────────────────────────
        $this->addImagingStudy($patient, [
            'modality' => 'CT',
            'study_date' => '2024-04-10',
            'description' => 'CT Abdomen with/without contrast',
            'body_part' => 'Abdomen',
            'num_series' => 4,
            'num_instances' => 350,
            'dicom_endpoint' => 'orthanc',
        ]);

        $this->addImagingStudy($patient, [
            'modality' => 'MRI',
            'study_date' => '2024-04-15',
            'description' => 'MRI Brain with contrast (VHL surveillance)',
            'body_part' => 'Brain',
            'num_series' => 8,
            'num_instances' => 400,
            'dicom_endpoint' => 'orthanc',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'VHL',
            'variant' => 'R167W (germline)',
            'variant_type' => 'SNV',
            'chromosome' => '3',
            'position' => 10183874,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Belzutifan (Welireg) — FDA approved for VHL-associated RCC',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'VHL',
            'variant' => 'LOH chromosome 3p (somatic)',
            'variant_type' => 'CNV',
            'chromosome' => '3',
            'position' => 10183874,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Biallelic VHL inactivation — classic ccRCC driver',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'PBRM1',
            'variant' => 'Q864*',
            'variant_type' => 'SNV',
            'chromosome' => '3',
            'position' => 52609453,
            'ref_allele' => 'C',
            'alt_allele' => 'T',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.42,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'Favorable prognostic; may predict IO response',
        ]);

        // ── Eras ────────────────────────────────────────────────
        $this->addConditionEra($patient, [
            'concept_name' => 'Clear cell renal cell carcinoma (VHL-associated)',
            'era_start' => '2024-04-10',
            'occurrence_count' => 1,
        ]);

        $this->addConditionEra($patient, [
            'concept_name' => 'Von Hippel-Lindau disease',
            'era_start' => '2019-08-01',
            'occurrence_count' => 1,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Belzutifan',
            'era_start' => '2024-07-15',
            'gap_days' => 0,
        ]);
    }
}
