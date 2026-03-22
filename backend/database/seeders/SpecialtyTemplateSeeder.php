<?php

namespace Database\Seeders;

use App\Models\CaseTemplate;
use Illuminate\Database\Seeder;

class SpecialtyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Oncology Tumor Board',
                'slug' => 'oncology-tumor-board',
                'specialty' => 'Oncology',
                'case_type' => 'tumor_board',
                'description' => 'Multidisciplinary tumor board review for treatment planning, staging, and consensus recommendations. Integrates imaging, pathology, genomics, and clinical data for comprehensive case evaluation.',
                'clinical_question_prompt' => 'What is the recommended treatment plan for this patient given their staging, molecular profile, and comorbidities?',
                'recommended_tabs' => [
                    'imaging',
                    'pathology',
                    'genomics',
                    'medications',
                    'labs',
                    'clinical_notes',
                    'treatment_history',
                ],
                'decision_types' => [
                    'treatment_recommendation',
                    'staging_consensus',
                    'surgical_candidacy',
                    'radiation_planning',
                    'systemic_therapy',
                    'clinical_trial_eligibility',
                ],
                'guideline_sets' => [
                    'NCCN',
                    'ASCO',
                    'ESMO',
                    'AJCC_Staging',
                ],
                'default_team_roles' => [
                    'medical_oncologist',
                    'surgical_oncologist',
                    'radiation_oncologist',
                    'pathologist',
                    'radiologist',
                    'oncology_nurse',
                    'tumor_board_coordinator',
                ],
            ],
            [
                'name' => 'Molecular Tumor Board',
                'slug' => 'molecular-tumor-board',
                'specialty' => 'Genomic Medicine',
                'case_type' => 'molecular_tumor_board',
                'description' => 'Genomics-focused tumor board for interpreting molecular profiling results, identifying actionable mutations, and matching patients to targeted therapies or clinical trials.',
                'clinical_question_prompt' => 'Based on the molecular profiling results, what targeted therapies or clinical trials are appropriate for this patient?',
                'recommended_tabs' => [
                    'genomics',
                    'pathology',
                    'medications',
                    'treatment_history',
                    'clinical_trials',
                    'biomarkers',
                ],
                'decision_types' => [
                    'variant_interpretation',
                    'targeted_therapy_recommendation',
                    'clinical_trial_matching',
                    'biomarker_assessment',
                    'germline_referral',
                    'companion_diagnostic',
                ],
                'guideline_sets' => [
                    'NCCN',
                    'OncoKB',
                    'ClinGen',
                    'AMP_ASCO_CAP',
                    'ESCAT',
                ],
                'default_team_roles' => [
                    'molecular_pathologist',
                    'medical_oncologist',
                    'genetic_counselor',
                    'bioinformatician',
                    'clinical_trial_coordinator',
                    'pharmacologist',
                ],
            ],
            [
                'name' => 'Rare Disease Diagnostic Odyssey',
                'slug' => 'rare-disease-diagnostic-odyssey',
                'specialty' => 'Rare Disease',
                'case_type' => 'diagnostic_odyssey',
                'description' => 'Multidisciplinary diagnostic workup for undiagnosed or rare conditions. Focuses on phenotype matching, genomic analysis, and systematic differential diagnosis.',
                'clinical_question_prompt' => 'What is the most likely diagnosis given the patient\'s phenotypic features, family history, and genomic findings? What additional workup is recommended?',
                'recommended_tabs' => [
                    'phenotype',
                    'genomics',
                    'family_history',
                    'labs',
                    'imaging',
                    'clinical_notes',
                    'prior_workup',
                    'similar_patients',
                ],
                'decision_types' => [
                    'differential_diagnosis',
                    'additional_testing',
                    'phenotype_matching',
                    'variant_reclassification',
                    'specialist_referral',
                    'research_enrollment',
                ],
                'guideline_sets' => [
                    'ACMG',
                    'Orphanet',
                    'HPO',
                    'OMIM',
                    'GeneReviews',
                ],
                'default_team_roles' => [
                    'clinical_geneticist',
                    'genetic_counselor',
                    'referring_specialist',
                    'molecular_pathologist',
                    'bioinformatician',
                    'metabolic_specialist',
                    'care_coordinator',
                ],
            ],
            [
                'name' => 'Complex Surgical Planning',
                'slug' => 'complex-surgical-planning',
                'specialty' => 'Surgery',
                'case_type' => 'surgical_planning',
                'description' => 'Multidisciplinary surgical planning for complex cases requiring advanced imaging review, risk assessment, and coordinated operative strategy.',
                'clinical_question_prompt' => 'What is the optimal surgical approach considering the patient\'s anatomy, comorbidities, and risk profile? What perioperative preparations are needed?',
                'recommended_tabs' => [
                    'imaging',
                    'imaging_3d',
                    'anatomy',
                    'labs',
                    'medications',
                    'comorbidities',
                    'risk_scores',
                    'anesthesia_notes',
                ],
                'decision_types' => [
                    'surgical_approach',
                    'risk_assessment',
                    'perioperative_planning',
                    'anesthesia_strategy',
                    'neoadjuvant_therapy',
                    'reconstruction_plan',
                    'complication_contingency',
                ],
                'guideline_sets' => [
                    'ACS_NSQIP',
                    'ASA_Classification',
                    'ERAS',
                    'WHO_Surgical_Checklist',
                ],
                'default_team_roles' => [
                    'primary_surgeon',
                    'assisting_surgeon',
                    'anesthesiologist',
                    'radiologist',
                    'interventional_radiologist',
                    'surgical_nurse',
                    'patient_navigator',
                ],
            ],
            [
                'name' => 'Complex Medical Case Review',
                'slug' => 'complex-medical-case-review',
                'specialty' => 'Internal Medicine',
                'case_type' => 'complex_medical',
                'description' => 'Multidisciplinary review of patients with multiple comorbidities, polypharmacy, or diagnostic uncertainty requiring coordinated specialist input.',
                'clinical_question_prompt' => 'How should we optimize management of this patient\'s multiple conditions while minimizing drug interactions and balancing competing treatment goals?',
                'recommended_tabs' => [
                    'conditions',
                    'medications',
                    'labs',
                    'vitals',
                    'clinical_notes',
                    'imaging',
                    'social_history',
                    'drug_interactions',
                ],
                'decision_types' => [
                    'treatment_optimization',
                    'medication_reconciliation',
                    'specialist_referral',
                    'care_coordination',
                    'goals_of_care',
                    'risk_stratification',
                    'discharge_planning',
                ],
                'guideline_sets' => [
                    'ACP',
                    'Beers_Criteria',
                    'STOPP_START',
                    'CKD_KDIGO',
                    'AHA_ACC',
                    'ADA_Standards',
                ],
                'default_team_roles' => [
                    'internist',
                    'hospitalist',
                    'clinical_pharmacist',
                    'care_coordinator',
                    'social_worker',
                    'consulting_specialist',
                    'primary_nurse',
                ],
            ],
        ];

        foreach ($templates as $template) {
            CaseTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }
}
