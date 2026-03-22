<?php

namespace Database\Seeders\DemoPatients;

use App\Models\Clinical\ClinicalNote;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\ConditionEra;
use App\Models\Clinical\DrugEra;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\Measurement;
use App\Models\Clinical\Medication;
use App\Models\Clinical\Observation;
use App\Models\Clinical\PatientIdentifier;
use App\Models\Clinical\Procedure;
use App\Models\Clinical\Visit;
use Illuminate\Support\Str;

trait DemoSeederHelper
{
    protected function provenance(): array
    {
        return [
            'source_type' => 'synthetic',
            'source_id' => 'demo_seeder_v1',
        ];
    }

    protected function createPatient(array $attrs): ClinicalPatient
    {
        return ClinicalPatient::create(array_merge($attrs, $this->provenance()));
    }

    protected function addIdentifier(
        ClinicalPatient $patient,
        string $type,
        string $value,
        ?string $sourceSystem = null,
    ): PatientIdentifier {
        return PatientIdentifier::create(array_merge([
            'patient_id' => $patient->id,
            'identifier_type' => $type,
            'identifier_value' => $value,
            'source_system' => $sourceSystem,
        ], $this->provenance()));
    }

    protected function addCondition(ClinicalPatient $patient, array $attrs): Condition
    {
        return Condition::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addMedication(ClinicalPatient $patient, array $attrs): Medication
    {
        return Medication::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addProcedure(ClinicalPatient $patient, array $attrs): Procedure
    {
        return Procedure::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addMeasurement(ClinicalPatient $patient, array $attrs): Measurement
    {
        return Measurement::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addObservation(ClinicalPatient $patient, array $attrs): Observation
    {
        return Observation::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addVisit(ClinicalPatient $patient, array $attrs): Visit
    {
        return Visit::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addNote(ClinicalPatient $patient, array $attrs): ClinicalNote
    {
        return ClinicalNote::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addImagingStudy(ClinicalPatient $patient, array $attrs): ImagingStudy
    {
        $study = ImagingStudy::create(array_merge(
            [
                'patient_id' => $patient->id,
                'study_uid' => '2.25.'.Str::random(32),
            ],
            $attrs,
            $this->provenance(),
        ));

        ImagingSeries::create(array_merge([
            'imaging_study_id' => $study->id,
            'series_uid' => '2.25.'.Str::random(32),
            'series_number' => 1,
            'modality' => $study->modality,
            'description' => $study->description,
        ], $this->provenance()));

        return $study;
    }

    protected function addImagingMeasurement(ImagingStudy $study, array $attrs): ImagingMeasurement
    {
        return ImagingMeasurement::create(array_merge(
            ['imaging_study_id' => $study->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addGenomicVariant(ClinicalPatient $patient, array $attrs): GenomicVariant
    {
        return GenomicVariant::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addConditionEra(ClinicalPatient $patient, array $attrs): ConditionEra
    {
        return ConditionEra::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    protected function addDrugEra(ClinicalPatient $patient, array $attrs): DrugEra
    {
        return DrugEra::create(array_merge(
            ['patient_id' => $patient->id],
            $attrs,
            $this->provenance(),
        ));
    }

    /**
     * Batch-create measurements from a lab panel.
     *
     * @param  array<int, array{0: string, 1: string, 2: float|string, 3: string, 4: float|null, 5: float|null, 6: string|null}>  $labs
     *                                                                                                                                   Each entry: [name, LOINC code, value, unit, refLow, refHigh, abnormalFlag]
     * @return array<int, Measurement>
     */
    protected function addLabPanel(ClinicalPatient $patient, string $measuredAt, array $labs): array
    {
        $measurements = [];

        foreach ($labs as $lab) {
            $measurements[] = $this->addMeasurement($patient, [
                'measurement_name' => $lab[0],
                'concept_code' => $lab[1],
                'vocabulary' => 'LOINC',
                'value_numeric' => $lab[2],
                'reference_range_low' => $lab[4] ?? null,
                'reference_range_high' => $lab[5] ?? null,
                'abnormal_flag' => $lab[6] ?? null,
                'measured_at' => $measuredAt,
            ]);
        }

        return $measurements;
    }
}
