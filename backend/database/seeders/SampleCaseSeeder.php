<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleCaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('app.users')->where('email', 'admin@acumenus.net')->value('id');

        if (!$adminId) {
            $this->command->error('Admin user not found. Run SuperuserSeeder first.');
            return;
        }

        $cases = [
            [
                'title' => 'Pancreatic Adenocarcinoma - Multidisciplinary Treatment Planning',
                'specialty' => 'oncology',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'tumor_board',
                'clinical_question' => 'Patient presents with borderline resectable pancreatic head adenocarcinoma (T3N1M0). FOLFIRINOX neoadjuvant vs. upfront Whipple with adjuvant gemcitabine-capecitabine? Vascular involvement at SMA margin is 120 degrees — does this preclude surgery?',
                'summary' => '68-year-old male with newly diagnosed pancreatic adenocarcinoma. CT shows 3.2cm mass at pancreatic head with borderline SMA abutment. CA 19-9 elevated at 842 U/mL. ECOG performance status 1. No distant metastases on staging PET-CT.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(3)->setTime(14, 0),
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'title' => 'Complex Aortic Root Replacement with Concurrent CABG',
                'specialty' => 'surgical',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'surgical_review',
                'clinical_question' => 'Bicuspid aortic valve with severe regurgitation and ascending aortic aneurysm (5.8cm). Concurrent 3-vessel CAD requiring CABG. Bentall vs. valve-sparing root replacement (David procedure) given the bicuspid morphology? Optimal surgical sequencing?',
                'summary' => '55-year-old male with bicuspid aortic valve, severe AR, ascending aortic aneurysm 5.8cm, and triple-vessel coronary artery disease. EF 45%. Previous sternotomy for ASD repair age 12. High-risk re-operative case.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(5)->setTime(8, 0),
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ],
            [
                'title' => 'Undiagnosed Periodic Fever Syndrome in Pediatric Patient',
                'specialty' => 'rare_disease',
                'urgency' => 'routine',
                'status' => 'in_review',
                'case_type' => 'rare_disease',
                'clinical_question' => 'Recurrent febrile episodes (39.5C) every 3-4 weeks lasting 4-5 days since age 2, now age 7. Elevated CRP/ESR during episodes, normal between. Negative genetic panel for FMF, TRAPS, HIDS, CAPS. Consider undifferentiated autoinflammatory syndrome? Role of empiric colchicine or IL-1 blockade?',
                'summary' => '7-year-old female with 5-year history of periodic fevers of unknown origin. Extensive infectious, malignant, and autoimmune workup negative. Genetic panel for known periodic fever syndromes negative. Episodes cause significant school absence and family distress.',
                'created_by' => $adminId,
                'scheduled_at' => null,
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(3),
            ],
            [
                'title' => 'Treatment-Resistant Depression with Comorbid Chronic Pain',
                'specialty' => 'complex_medical',
                'urgency' => 'routine',
                'status' => 'active',
                'case_type' => 'medical_complex',
                'clinical_question' => 'Failed adequate trials of 4 SSRIs, 2 SNRIs, mirtazapine, and augmentation with lithium and aripiprazole. Comorbid fibromyalgia on pregabalin. Esketamine nasal spray vs. TMS vs. combination approach? Risk of ketamine in patient with history of substance use disorder (alcohol, 3 years sober)?',
                'summary' => '42-year-old female with MDD (PHQ-9: 24), GAD, fibromyalgia, and alcohol use disorder in sustained remission. Multiple medication failures over 8 years. Currently on duloxetine 120mg and pregabalin 300mg with minimal benefit. Functional impairment significant — on disability.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(7)->setTime(10, 0),
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'Triple-Negative Breast Cancer - Neoadjuvant Protocol Review',
                'specialty' => 'oncology',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'tumor_board',
                'clinical_question' => 'Stage IIB TNBC (cT3N1). KEYNOTE-522 regimen (pembrolizumab + carboplatin/paclitaxel then AC) vs. dose-dense AC-T with carboplatin? PD-L1 CPS 15. BRCA1 pathogenic variant detected — implications for neoadjuvant and adjuvant strategy including olaparib?',
                'summary' => '38-year-old female with newly diagnosed triple-negative breast cancer, 4.5cm primary tumor with ipsilateral axillary lymphadenopathy. BRCA1 positive. Ki-67 85%. PD-L1 CPS 15. No distant metastases. Strong family history — mother with ovarian cancer at 45.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(2)->setTime(13, 0),
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'Post-Transplant Lymphoproliferative Disorder Workup',
                'specialty' => 'oncology',
                'urgency' => 'emergent',
                'status' => 'active',
                'case_type' => 'tumor_board',
                'clinical_question' => 'Renal transplant recipient (5 years post) with new cervical and mediastinal lymphadenopathy. Biopsy shows monomorphic PTLD (DLBCL subtype), EBV-positive. Reduce immunosuppression alone first vs. immediate R-CHOP? Risk of graft rejection with IS reduction? Role of EBV-specific CTL therapy?',
                'summary' => '52-year-old male, 5 years post deceased-donor renal transplant on tacrolimus/MMF/prednisone. Presents with B symptoms, weight loss, and bulky lymphadenopathy. Biopsy confirms monomorphic PTLD (DLBCL). EBV viral load markedly elevated. Creatinine at baseline 1.4.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(1)->setTime(9, 0),
                'created_at' => $now->copy()->subHours(18),
                'updated_at' => $now->copy()->subHours(6),
            ],
            [
                'title' => 'Robotic-Assisted Pyeloplasty for UPJ Obstruction',
                'specialty' => 'surgical',
                'urgency' => 'routine',
                'status' => 'draft',
                'case_type' => 'surgical_review',
                'clinical_question' => 'Right UPJ obstruction with split renal function 35% on MAG3 renogram. Crossing vessel identified on CTA. Robotic pyeloplasty vs. endopyelotomy given the crossing vessel? Stent duration post-operatively? Need for concurrent nephropexy?',
                'summary' => '29-year-old female with intermittent right flank pain and recurrent UTIs. CT urogram shows right hydronephrosis with UPJ obstruction. MAG3 confirms obstructive pattern with 35% split function. Crossing lower pole vessel identified.',
                'created_by' => $adminId,
                'scheduled_at' => null,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'Suspected Hereditary Hemorrhagic Telangiectasia',
                'specialty' => 'rare_disease',
                'urgency' => 'routine',
                'status' => 'draft',
                'case_type' => 'rare_disease',
                'clinical_question' => 'Meets 3 of 4 Curacao criteria (epistaxis, telangiectasias, family history). Screening for pulmonary AVMs (bubble echo positive, CT pending) and hepatic AVMs. Genetic testing for ENG vs. ACVRL1 vs. SMAD4 — which gene panel? Role of bevacizumab for recurrent severe epistaxis failing cauterization?',
                'summary' => '34-year-old male with recurrent severe epistaxis since adolescence requiring multiple cauterizations and one embolization. Multiple mucocutaneous telangiectasias on lips, tongue, and fingertips. Mother and maternal uncle with similar symptoms. Chronic iron deficiency anemia (Hb 9.2).',
                'created_by' => $adminId,
                'scheduled_at' => null,
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now->copy()->subHours(6),
            ],
        ];

        foreach ($cases as $case) {
            $exists = DB::table('app.cases')
                ->where('title', $case['title'])
                ->whereNull('deleted_at')
                ->exists();

            if (!$exists) {
                DB::table('app.cases')->insert($case);
            }
        }

        $this->command->info('Seeded ' . count($cases) . ' sample clinical cases.');
    }
}
