<?php

namespace Database\Seeders;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\DrugEra;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingSegmentation;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\Measurement;
use App\Models\Clinical\Medication;
use App\Models\Clinical\OutcomeTrajectory;
use App\Models\Clinical\Visit;
use Illuminate\Database\Seeder;

class GoldenCohortSeeder extends Seeder
{
    public function run(): void
    {
        $files = glob(database_path('data/golden-cohort/*.json'));

        foreach ($files as $file) {
            $patients = json_decode(file_get_contents($file), true);

            foreach ($patients as $patientData) {
                $this->seedPatient($patientData);
            }
        }

        $this->command->info('Golden cohort seeded: '.ClinicalPatient::where('source_type', 'golden_cohort')->count().' patients.');
    }

    private function seedPatient(array $data): void
    {
        // Upsert patient by MRN (idempotent)
        $patient = ClinicalPatient::updateOrCreate(
            ['mrn' => $data['mrn']],
            array_merge($data['demographics'], ['source_type' => 'golden_cohort'])
        );

        // Seed each data layer
        $this->seedConditions($patient, $data['conditions'] ?? []);
        $this->seedMedications($patient, $data['medications'] ?? []);
        $this->seedDrugEras($patient, $data['drug_eras'] ?? []);
        $this->seedVariants($patient, $data['genomic_variants'] ?? []);
        $this->seedImagingStudies($patient, $data['imaging_studies'] ?? []);
        $this->seedMeasurements($patient, $data['measurements'] ?? []);
        $this->seedVisits($patient, $data['visits'] ?? []);
        $this->seedOutcome($patient, $data['outcome_trajectory'] ?? null);
    }

    private function seedConditions(ClinicalPatient $patient, array $conditions): void
    {
        foreach ($conditions as $c) {
            Condition::updateOrCreate(
                ['patient_id' => $patient->id, 'concept_name' => $c['concept_name'], 'source_type' => 'golden_cohort'],
                $c
            );
        }
    }

    private function seedMedications(ClinicalPatient $patient, array $medications): void
    {
        foreach ($medications as $m) {
            Medication::updateOrCreate(
                ['patient_id' => $patient->id, 'drug_name' => $m['drug_name'], 'start_date' => $m['start_date'] ?? null, 'source_type' => 'golden_cohort'],
                $m
            );
        }
    }

    private function seedDrugEras(ClinicalPatient $patient, array $eras): void
    {
        foreach ($eras as $e) {
            DrugEra::updateOrCreate(
                ['patient_id' => $patient->id, 'drug_name' => $e['drug_name'], 'era_start' => $e['era_start']],
                $e
            );
        }
    }

    private function seedVariants(ClinicalPatient $patient, array $variants): void
    {
        foreach ($variants as $v) {
            GenomicVariant::updateOrCreate(
                ['patient_id' => $patient->id, 'gene' => $v['gene'], 'variant' => $v['variant'] ?? null, 'source_type' => 'golden_cohort'],
                $v
            );
        }
    }

    private function seedImagingStudies(ClinicalPatient $patient, array $studies): void
    {
        foreach ($studies as $s) {
            $study = ImagingStudy::updateOrCreate(
                ['patient_id' => $patient->id, 'study_uid' => $s['study_uid'], 'source_type' => 'golden_cohort'],
                $s['study']
            );

            foreach ($s['measurements'] ?? [] as $m) {
                ImagingMeasurement::updateOrCreate(
                    ['imaging_study_id' => $study->id, 'measurement_type' => $m['measurement_type'], 'measured_at' => $m['measured_at'] ?? null],
                    $m
                );
            }

            foreach ($s['segmentations'] ?? [] as $seg) {
                ImagingSegmentation::updateOrCreate(
                    ['imaging_study_id' => $study->id, 'segmentation_uid' => $seg['segmentation_uid']],
                    $seg
                );
            }
        }
    }

    private function seedMeasurements(ClinicalPatient $patient, array $measurements): void
    {
        foreach ($measurements as $m) {
            Measurement::updateOrCreate(
                ['patient_id' => $patient->id, 'measurement_name' => $m['measurement_name'], 'measured_at' => $m['measured_at'], 'source_type' => 'golden_cohort'],
                $m
            );
        }
    }

    private function seedVisits(ClinicalPatient $patient, array $visits): void
    {
        foreach ($visits as $v) {
            Visit::updateOrCreate(
                ['patient_id' => $patient->id, 'visit_type' => $v['visit_type'], 'admission_date' => $v['admission_date'], 'source_type' => 'golden_cohort'],
                $v
            );
        }
    }

    private function seedOutcome(ClinicalPatient $patient, ?array $outcome): void
    {
        if (! $outcome) {
            return;
        }

        OutcomeTrajectory::updateOrCreate(
            ['patient_id' => $patient->id],
            array_merge($outcome, ['computed_at' => now()])
        );
    }
}
