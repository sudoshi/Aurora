<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $templates = [
            [
                'slug' => 'oncology-tumor-board',
                'name' => 'Oncology Tumor Board',
                'specialty' => 'oncology',
                'case_type' => 'tumor_board',
                'description' => 'Molecular tumor board variant for multidisciplinary oncology case review.',
                'recommended_tabs' => json_encode(['overview', 'imaging', 'genomics', 'radiogenomics', 'timeline', 'decisions']),
                'decision_types' => json_encode(['treatment_plan', 'surgery_recommendation', 'clinical_trial_referral', 'palliative_care']),
                'guideline_sets' => json_encode(['NCCN', 'ASCO', 'ESMO']),
                'default_team_roles' => json_encode(['medical_oncologist', 'surgical_oncologist', 'radiation_oncologist', 'pathologist', 'radiologist', 'genetic_counselor', 'nurse_navigator']),
                'clinical_question_prompt' => 'What is the optimal treatment strategy for this patient given their molecular profile, imaging findings, and prior treatment history?',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'rare-disease-diagnostic-odyssey',
                'name' => 'Rare Disease Diagnostic Odyssey',
                'specialty' => 'rare_disease',
                'case_type' => 'diagnostic_odyssey',
                'description' => 'Structured workflow for undiagnosed and rare disease diagnostic evaluation.',
                'recommended_tabs' => json_encode(['overview', 'genomics', 'timeline', 'conditions', 'labs', 'decisions']),
                'decision_types' => json_encode(['differential_diagnosis', 'genetic_testing', 'specialist_referral', 'treatment_trial']),
                'guideline_sets' => json_encode(['ACMG', 'Orphanet', 'GARD']),
                'default_team_roles' => json_encode(['geneticist', 'pediatrician', 'neurologist', 'metabolic_specialist', 'genetic_counselor', 'social_worker']),
                'clinical_question_prompt' => 'What is the most likely unifying diagnosis for this patient\'s constellation of symptoms, and what additional workup would help confirm it?',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'complex-surgical-planning',
                'name' => 'Complex Surgical Planning',
                'specialty' => 'surgery',
                'case_type' => 'surgical_planning',
                'description' => 'Multidisciplinary surgical planning workflow for complex procedures.',
                'recommended_tabs' => json_encode(['overview', 'imaging', 'timeline', 'procedures', 'decisions']),
                'decision_types' => json_encode(['surgical_approach', 'risk_assessment', 'staging', 'pre_op_optimization']),
                'guideline_sets' => json_encode(['ACS', 'ERAS', 'SSI_Prevention']),
                'default_team_roles' => json_encode(['lead_surgeon', 'anesthesiologist', 'radiologist', 'internist', 'nurse_coordinator', 'surgical_resident']),
                'clinical_question_prompt' => 'What is the optimal surgical approach considering anatomic factors, patient comorbidities, and expected outcomes?',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'complex-medical-case-review',
                'name' => 'Complex Medical Case Review',
                'specialty' => 'internal_medicine',
                'case_type' => 'medical_review',
                'description' => 'Comprehensive review workflow for complex medical cases with multiple comorbidities.',
                'recommended_tabs' => json_encode(['overview', 'conditions', 'medications', 'labs', 'timeline', 'decisions']),
                'decision_types' => json_encode(['diagnosis_confirmation', 'treatment_modification', 'specialist_consultation', 'discharge_planning']),
                'guideline_sets' => json_encode(['ACP', 'NICE', 'UpToDate']),
                'default_team_roles' => json_encode(['attending_physician', 'hospitalist', 'pharmacist', 'case_manager', 'social_worker', 'specialist_consultant']),
                'clinical_question_prompt' => 'What is the optimal management plan for this patient\'s complex medical conditions, considering drug interactions and comorbidities?',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('app.case_templates')->insertOrIgnore($templates);
    }
}
