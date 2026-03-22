<?php

namespace Database\Seeders;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleCaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('app.users')->where('email', 'admin@acumenus.net')->value('id');

        if (!$adminId) {
            $this->command->error('Admin user not found.');
            return;
        }

        // Delete existing sample cases to re-seed with linked patients
        DB::table('app.cases')->whereNull('deleted_at')->delete();

        // Map MRN → patient_id for linking
        $patients = ClinicalPatient::where('mrn', 'like', 'DEMO-%')
            ->pluck('id', 'mrn')
            ->toArray();

        $cases = [
            // === RARE DISEASE ===
            [
                'title' => 'hATTR Amyloidosis — Cardiac and Neurologic Progression Assessment',
                'specialty' => 'rare_disease',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'rare_disease',
                'patient_id' => $patients['DEMO-RD-001'] ?? null,
                'clinical_question' => 'Marcus Washington, 58yo AA male with hATTR amyloidosis (Val142Ile). Progressive HFpEF with rising NT-proBNP (4500→3100 on tafamidis). Worsening autonomic neuropathy and gastroparesis. Should we escalate from tafamidis to patisiran given cardiac progression? Role of ICD given sustained VT episodes?',
                'summary' => 'Diagnosed 2019 via endomyocardial biopsy showing TTR amyloid deposits with Val142Ile variant. 6-year disease course with bilateral CTS → cardiac → autonomic → GI progression. Currently on tafamidis 61mg with partial cardiac stabilization. NT-proBNP trending 1850→3200→4500→3100→2400. NYHA Class III. Multiple specialist involvement.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(2)->setTime(14, 0),
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'Tuberous Sclerosis Complex — Pediatric Multisystem Management',
                'specialty' => 'rare_disease',
                'urgency' => 'routine',
                'status' => 'in_review',
                'case_type' => 'rare_disease',
                'patient_id' => $patients['DEMO-RD-002'] ?? null,
                'clinical_question' => 'Isabella Ramirez, 8yo Hispanic female with TSC (TSC2 mutation). Growing SEGA approaching foramen of Monro. Renal angiomyolipomas bilateral. Refractory epilepsy on 3 AEDs. Everolimus for SEGA vs. surgical resection? Impact on renal AMLs? Vigabatrin addition for seizures?',
                'summary' => 'TSC diagnosed at 10 months via cortical tubers on MRI and infantile spasms. TSC2 pathogenic variant confirmed. Current burden: SEGA 2.1cm (growing), bilateral renal AMLs (largest 3.2cm), facial angiofibromas, cardiac rhabdomyomas (regressing). Seizures average 3-4/month on levetiracetam + lacosamide + clobazam.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(5)->setTime(10, 0),
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'CAPS Flare Management — Canakinumab Dose Escalation',
                'specialty' => 'rare_disease',
                'urgency' => 'routine',
                'status' => 'active',
                'case_type' => 'rare_disease',
                'patient_id' => $patients['DEMO-RD-003'] ?? null,
                'clinical_question' => 'Ananya Patel, 32yo South Asian female with CAPS/MWS (NLRP3 T348M). Breakthrough flares on canakinumab 150mg q8w. CRP rising to 28 during flares despite treatment. Dose escalation to 300mg vs. switch to rilonacept? Monitoring for AA amyloidosis given persistent inflammation?',
                'summary' => 'CAPS diagnosed age 26 after decade-long diagnostic odyssey. NLRP3 T348M confirmed. Classic MWS phenotype: urticarial rash, arthralgia, sensorineural hearing loss (bilateral, progressive), chronic fatigue, episodic fevers. On canakinumab 150mg q8w with partial response. SAA levels intermittently elevated — concern for AA amyloidosis.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(4)->setTime(11, 0),
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],

            // === PRE-SURGICAL ===
            [
                'title' => 'Redo CABG + AVR — High-Risk Cardiac Surgical Planning',
                'specialty' => 'surgical',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'surgical_review',
                'patient_id' => $patients['DEMO-PS-001'] ?? null,
                'clinical_question' => 'Robert Kowalski, 68yo male. Prior CABG (2015) with patent LIMA-LAD but occluded SVG-OM and SVG-RCA. Now severe aortic stenosis (AVA 0.7cm², mean gradient 48mmHg) with LVEF 35%. Redo sternotomy with AVR+CABG vs. TAVR with PCI? STS score 8.2%. Frailty assessment?',
                'summary' => 'Prior CABG x3 (2015). Progressive aortic stenosis now severe. Occluded vein grafts with ischemic territory. CKD 3b (eGFR 38), diabetes, COPD. Multiple comorbidities drive high surgical risk. Multidisciplinary heart team review needed for optimal intervention strategy.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(1)->setTime(7, 30),
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subHours(12),
            ],
            [
                'title' => 'CRS-HIPEC for Peritoneal Carcinomatosis — Surgical Candidacy',
                'specialty' => 'surgical',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'surgical_review',
                'patient_id' => $patients['DEMO-PS-002'] ?? null,
                'clinical_question' => 'Carmen Delgado, 54yo Hispanic female. Appendiceal mucinous adenocarcinoma with peritoneal carcinomatosis (PCI score 18). Completed 6 cycles FOLFOX with partial response. CRS-HIPEC candidacy? PCI threshold for benefit? Resectability of disease in pelvis and right diaphragm?',
                'summary' => 'Appendiceal primary discovered incidentally during cholecystectomy. Staging reveals moderate-volume peritoneal carcinomatosis. Post-FOLFOX imaging shows partial response with PCI reduction from 24→18. Albumin 3.1, performance status ECOG 1. Multidisciplinary discussion on optimal timing and extent of CRS-HIPEC.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(3)->setTime(13, 0),
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'VHL + HHT Posterior Fossa Hemangioblastoma — Neurosurgical Planning',
                'specialty' => 'surgical',
                'urgency' => 'emergent',
                'status' => 'active',
                'case_type' => 'surgical_review',
                'patient_id' => $patients['DEMO-PS-003'] ?? null,
                'clinical_question' => 'Erik Lindgren, 41yo male with VHL and coexisting HHT. Growing posterior fossa hemangioblastoma (3.8cm) causing obstructive hydrocephalus. Multiple hepatic and pancreatic lesions. HHT complicates surgery with pulmonary AVMs. Pre-operative embolization? Approach to hydrocephalus — EVD vs. shunt vs. direct tumor resection?',
                'summary' => 'VHL diagnosed age 28 with bilateral renal cell carcinomas (bilateral partial nephrectomies). HHT diagnosed age 32 via genetic testing (dual diagnosis). Now presenting with progressive cerebellar symptoms — ataxia, nausea, papilledema. MRI shows 3.8cm hemangioblastoma with peritumoral edema and early hydrocephalus. Pulmonary AVMs on screening CTA.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addHours(18)->setTime(8, 0),
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now->copy()->subHours(2),
            ],

            // === ONCOLOGY ===
            [
                'title' => 'EGFR+ NSCLC — Osimertinib Resistance and Next-Line Strategy',
                'specialty' => 'oncology',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'tumor_board',
                'patient_id' => $patients['DEMO-ON-001'] ?? null,
                'clinical_question' => 'James Whitfield, 62yo male with Stage IIIA EGFR L858R NSCLC. Post-lobectomy, progressed on adjuvant osimertinib with new brain metastases. Repeat biopsy shows MET amplification as resistance mechanism. Osimertinib + savolitinib vs. chemo-IO? Role of SRS for brain mets?',
                'summary' => 'Diagnosed 2024 with right upper lobe NSCLC. EGFR L858R, PD-L1 TPS 80%. s/p RUL lobectomy and mediastinal lymph node dissection. Started adjuvant osimertinib. At 14 months, surveillance MRI shows 3 brain metastases. Liquid biopsy confirms MET amplification. Molecular tumor board review for next-line therapy.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(1)->setTime(13, 0),
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subHours(6),
            ],
            [
                'title' => 'BRAF V600E CRC — Encorafenib-Cetuximab Response Assessment',
                'specialty' => 'oncology',
                'urgency' => 'routine',
                'status' => 'active',
                'case_type' => 'tumor_board',
                'patient_id' => $patients['DEMO-ON-002'] ?? null,
                'clinical_question' => 'Margaret Okafor, 54yo Black female. Stage IV BRAF V600E MSS CRC with liver and lung metastases. Completed 4 cycles encorafenib-cetuximab with 40% tumor reduction. Continue current regimen vs. consolidation? Surgical candidacy for remaining liver lesion? Role of ctDNA monitoring?',
                'summary' => 'Metastatic CRC diagnosed 2024. BRAF V600E, MSS (not MSI-H), RAS wild-type. Prior FOLFOX progression. Switched to BEACON regimen (encorafenib + cetuximab). Restaging CT shows 40% reduction in liver mets, stable lung nodule. CEA trending down 842→320→180. ctDNA clearance rate being monitored.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(4)->setTime(14, 0),
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'BRCA1 Triple-Negative Breast Cancer — Neoadjuvant Protocol',
                'specialty' => 'oncology',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'tumor_board',
                'patient_id' => $patients['DEMO-ON-003'] ?? null,
                'clinical_question' => 'Priya Sharma, 41yo South Asian female. Stage IIB BRCA1+ TNBC. KEYNOTE-522 (pembrolizumab + carboplatin/paclitaxel → AC) vs. dose-dense AC-T + carboplatin? PD-L1 CPS 15, Ki-67 85%. Post-neoadjuvant plan: adjuvant olaparib if residual disease? Prophylactic bilateral salpingo-oophorectomy timing?',
                'summary' => 'TNBC diagnosed via screening MRI (BRCA1 carrier). 4.2cm mass with positive sentinel node. Germline BRCA1 c.68_69delAG (pathogenic). PD-L1 CPS 15. Ki-67 85%. Mother had ovarian cancer at 45. Planning neoadjuvant chemo-immunotherapy followed by surgical assessment of pathologic complete response.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(2)->setTime(11, 0),
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subHours(8),
            ],

            // === UNDIAGNOSED ===
            [
                'title' => 'Erdheim-Chester Disease — Multisystem Diagnostic Workup',
                'specialty' => 'rare_disease',
                'urgency' => 'urgent',
                'status' => 'in_review',
                'case_type' => 'rare_disease',
                'patient_id' => $patients['DEMO-UD-001'] ?? null,
                'clinical_question' => 'Marcus Thompson, 54yo AA male. Progressive bilateral leg pain, diabetes insipidus, periaortic fibrosis, and bilateral periorbital xanthogranulomas. Bone scan shows symmetric long bone uptake. BRAF V600E detected on tissue biopsy. Erdheim-Chester disease? Vemurafenib vs. cobimetinib? Cardiac MRI for occult cardiac involvement?',
                'summary' => '3-year diagnostic odyssey. Initially presented with bilateral tibial pain misdiagnosed as shin splints. Progressive multisystem involvement: central DI (2022), retroperitoneal fibrosis (2023), periorbital masses (2024). Bone biopsy shows foamy histiocytes CD68+/CD1a-. BRAF V600E positive. Consistent with Erdheim-Chester disease.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(3)->setTime(9, 0),
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(1),
            ],
            [
                'title' => 'VEXAS Syndrome — Refractory Cytopenias and Systemic Inflammation',
                'specialty' => 'rare_disease',
                'urgency' => 'urgent',
                'status' => 'active',
                'case_type' => 'rare_disease',
                'patient_id' => $patients['DEMO-UD-002'] ?? null,
                'clinical_question' => 'Gerald Kowalczyk, 67yo male. UBA1 M41T confirmed. Transfusion-dependent macrocytic anemia, recurrent chondritis, DVT, neutrophilic dermatosis. Failed azacitidine and ruxolitinib. Allogeneic stem cell transplant candidacy? Age and comorbidity assessment? Alternative: JAK2 inhibitor switch or clinical trial?',
                'summary' => 'VEXAS diagnosed 2024 after 4-year diagnostic odyssey. Initially MDS/CMML evaluation → negative. Somatic UBA1 M41T in bone marrow. Progressive transfusion dependence (2-3 units PRBCs/month), relapsing polychondritis, venous thromboembolism, and Sweet syndrome. Refractory to steroids, azacitidine, and ruxolitinib.',
                'created_by' => $adminId,
                'scheduled_at' => now()->addDays(2)->setTime(15, 0),
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subHours(4),
            ],
            [
                'title' => 'APS-1/APECED — Pediatric Autoimmune Polyendocrinopathy',
                'specialty' => 'rare_disease',
                'urgency' => 'routine',
                'status' => 'draft',
                'case_type' => 'rare_disease',
                'patient_id' => $patients['DEMO-UD-003'] ?? null,
                'clinical_question' => 'Sofia Reyes, 11yo Hispanic female. AIRE compound heterozygous mutations. Classic triad: chronic mucocutaneous candidiasis (age 3), hypoparathyroidism (age 6), adrenal insufficiency (age 9). Now developing autoimmune hepatitis (ALT 180) and anti-IFN-ω antibodies. Rituximab for hepatitis vs. standard immunosuppression? Screening protocol for additional endocrinopathies?',
                'summary' => 'APS-1/APECED diagnosed age 8 via AIRE gene testing. Progressive accumulation of autoimmune manifestations over 8 years. Currently managing hypoparathyroidism (calcium + calcitriol), adrenal insufficiency (hydrocortisone), and chronic candidiasis (fluconazole). New autoimmune hepatitis adds complexity. Anti-cytokine antibodies panel positive.',
                'created_by' => $adminId,
                'scheduled_at' => null,
                'created_at' => $now->copy()->subDays(1),
                'updated_at' => $now->copy()->subDays(1),
            ],
        ];

        foreach ($cases as $case) {
            DB::table('app.cases')->insert($case);
        }

        $this->command->info('Seeded ' . count($cases) . ' clinical cases linked to demo patients.');
    }
}
