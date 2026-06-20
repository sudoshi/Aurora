<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingResponseAssessment;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinical\ImagingResponseAssessment>
 */
class ImagingResponseAssessmentFactory extends Factory
{
    protected $model = ImagingResponseAssessment::class;

    public function definition(): array
    {
        $patient = ClinicalPatient::factory()->create();
        $baseline = ImagingStudy::create([
            'patient_id' => $patient->id,
            'study_uid' => $this->faker->unique()->uuid(),
            'modality' => 'CT',
            'study_date' => now()->subMonths(3)->toDateString(),
            'description' => 'Baseline CT',
        ]);
        $current = ImagingStudy::create([
            'patient_id' => $patient->id,
            'study_uid' => $this->faker->unique()->uuid(),
            'modality' => 'CT',
            'study_date' => now()->toDateString(),
            'description' => 'Follow-up CT',
        ]);

        return [
            'patient_id' => $patient->id,
            'criteria_type' => 'recist',
            'assessment_date' => now()->toDateString(),
            'body_site' => 'Chest',
            'baseline_study_id' => $baseline->id,
            'current_study_id' => $current->id,
            'baseline_value' => 42,
            'nadir_value' => null,
            'current_value' => 28,
            'percent_change_from_baseline' => -33.33,
            'percent_change_from_nadir' => null,
            'response_category' => 'PR',
            'rationale' => 'Factory response assessment',
            'assessed_by' => null,
            'is_confirmed' => false,
            'source_type' => 'factory',
            'source_id' => null,
        ];
    }
}
