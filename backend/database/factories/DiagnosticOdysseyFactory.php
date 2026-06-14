<?php

namespace Database\Factories;

use App\Models\DiagnosticOdyssey;
use App\Models\User;
use Database\Factories\Clinical\ClinicalPatientFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosticOdysseyFactory extends Factory
{
    protected $model = DiagnosticOdyssey::class;

    public function definition(): array
    {
        return [
            'patient_id' => ClinicalPatientFactory::new(),
            'title' => 'Undiagnosed multisystem disorder',
            'status' => 'referral',
            'progress_status' => 'in_progress',
            'referral_reason' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
