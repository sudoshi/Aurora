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
                'time_model' => 'episodic',
                'data_schema' => json_encode([
                    ['key' => 'primary_site', 'label' => 'Primary site', 'type' => 'string', 'required' => true],
                    ['key' => 'histology', 'label' => 'Histology', 'type' => 'string', 'required' => false],
                    ['key' => 'stage', 'label' => 'Stage', 'type' => 'string', 'required' => false],
                    ['key' => 'key_biomarkers', 'label' => 'Key biomarkers', 'type' => 'string', 'required' => false],
                ]),
                'candidacy_rubric' => null,
                'agenda' => json_encode(['Presentation', 'Imaging review', 'Molecular review', 'Recommendation']),
                'state_machine' => null,
                'is_active' => true,
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
                'time_model' => 'diagnostic_odyssey',
                'data_schema' => json_encode([
                    ['key' => 'hpo_terms', 'label' => 'HPO terms', 'type' => 'string', 'required' => false],
                    ['key' => 'candidate_genes', 'label' => 'Candidate genes', 'type' => 'string', 'required' => false],
                    ['key' => 'prior_testing', 'label' => 'Prior testing', 'type' => 'string', 'required' => false],
                ]),
                'candidacy_rubric' => null,
                'agenda' => json_encode(['Phenotype review', 'Prior testing', 'Differential', 'Next test / matchmaking']),
                'state_machine' => json_encode([
                    'initial' => 'referral',
                    'states' => ['referral', 'deep_phenotyping', 'testing', 'mdt_review', 'matchmaking', 'diagnosed', 'undiagnosed', 'reanalysis'],
                    'transitions' => [
                        ['from' => 'referral', 'to' => 'deep_phenotyping', 'event' => 'phenotype'],
                        ['from' => 'deep_phenotyping', 'to' => 'testing', 'event' => 'order_testing'],
                        ['from' => 'testing', 'to' => 'mdt_review', 'event' => 'results_in'],
                        ['from' => 'mdt_review', 'to' => 'matchmaking', 'event' => 'seek_matches'],
                        ['from' => 'mdt_review', 'to' => 'diagnosed', 'event' => 'diagnose'],
                        ['from' => 'matchmaking', 'to' => 'diagnosed', 'event' => 'diagnose'],
                        ['from' => 'matchmaking', 'to' => 'undiagnosed', 'event' => 'close_unsolved'],
                        ['from' => 'undiagnosed', 'to' => 'reanalysis', 'event' => 'reanalyze'],
                        ['from' => 'reanalysis', 'to' => 'mdt_review', 'event' => 'new_findings'],
                    ],
                ]),
                'is_active' => true,
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
                'time_model' => 'episode_of_care',
                'data_schema' => json_encode([
                    ['key' => 'procedure', 'label' => 'Planned procedure', 'type' => 'string', 'required' => true],
                    ['key' => 'asa_class', 'label' => 'ASA class', 'type' => 'string', 'required' => false],
                ]),
                'candidacy_rubric' => json_encode([
                    ['key' => 'cardiology_clearance', 'label' => 'Cardiology clearance', 'required' => true],
                    ['key' => 'anesthesia_review', 'label' => 'Anesthesia review', 'required' => true],
                    ['key' => 'frailty_assessment', 'label' => 'Frailty assessment', 'required' => false],
                ]),
                'agenda' => json_encode(['Candidacy', 'Imaging', 'Risk', 'Plan']),
                'state_machine' => json_encode([
                    'initial' => 'referred',
                    'states' => ['referred', 'workup', 'optimization', 'decision', 'procedure', 'recovery', 'closed'],
                    'transitions' => [
                        ['from' => 'referred', 'to' => 'workup', 'event' => 'begin_workup'],
                        ['from' => 'workup', 'to' => 'optimization', 'event' => 'optimize'],
                        ['from' => 'optimization', 'to' => 'decision', 'event' => 'review'],
                        ['from' => 'decision', 'to' => 'procedure', 'event' => 'proceed'],
                        ['from' => 'procedure', 'to' => 'recovery', 'event' => 'operate'],
                        ['from' => 'recovery', 'to' => 'closed', 'event' => 'discharge'],
                    ],
                ]),
                'is_active' => true,
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
                'time_model' => 'longitudinal',
                'data_schema' => json_encode([
                    ['key' => 'problem_list', 'label' => 'Problem list', 'type' => 'string', 'required' => false],
                    ['key' => 'goals_of_care', 'label' => 'Goals of care', 'type' => 'string', 'required' => false],
                ]),
                'candidacy_rubric' => null,
                'agenda' => json_encode(['Problem list', 'Med reconciliation', 'Goals of care', 'Plan']),
                'state_machine' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($templates as $t) {
            // Idempotent upsert by slug. created_at is insert-only so re-running
            // the seeder never clobbers the original creation timestamp.
            if (DB::table('app.case_templates')->where('slug', $t['slug'])->exists()) {
                $update = $t;
                unset($update['created_at']);
                DB::table('app.case_templates')->where('slug', $t['slug'])->update($update);
            } else {
                DB::table('app.case_templates')->insert($t);
            }
        }
    }
}
