<?php

namespace Database\Seeders\DemoPatients;

class PreSurgicalPatient3_VHL_HHT
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // ── Patient ──────────────────────────────────────────────
        $patient = $this->createPatient([
            'mrn' => 'DEMO-PS-003',
            'first_name' => 'Erik',
            'last_name' => 'Lindgren',
            'date_of_birth' => '1985-06-12',
            'sex' => 'Male',
            'race' => 'White',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // ── Identifiers ─────────────────────────────────────────
        $this->addIdentifier($patient, 'insurance_id', 'INS-EL-92017');
        $this->addIdentifier($patient, 'facility_mrn', 'UNH-334589', 'University Hospital');

        // ── Conditions ──────────────────────────────────────────
        $this->addCondition($patient, [
            'concept_name' => 'Cerebellar hemangioblastoma',
            'concept_code' => 'D33.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2026-01-01',
            'severity' => 'severe',
            'body_site' => 'Cerebellum',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Von Hippel-Lindau disease type 1',
            'concept_code' => 'Q85.8',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2010-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hereditary hemorrhagic telangiectasia type 1',
            'concept_code' => 'I78.0',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2015-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Bilateral pulmonary arteriovenous malformations',
            'concept_code' => 'Q25.72',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2015-06-01',
            'laterality' => 'bilateral',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Chronic hypoxemia',
            'concept_code' => 'R09.02',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2015-06-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Secondary erythrocytosis',
            'concept_code' => 'D75.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2016-01-01',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Obstructive hydrocephalus',
            'concept_code' => 'G91.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'active',
            'onset_date' => '2026-01-01',
            'body_site' => 'Brain',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Hepatic arteriovenous malformations',
            'concept_code' => 'Q25.72',
            'vocabulary' => 'ICD10CM',
            'domain' => 'complex_medical',
            'status' => 'active',
            'onset_date' => '2020-01-01',
            'severity' => 'mild',
            'body_site' => 'Liver',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Prior cerebellar hemangioblastoma, resected',
            'concept_code' => 'Z87.39',
            'vocabulary' => 'ICD10CM',
            'domain' => 'surgical',
            'status' => 'resolved',
            'onset_date' => '2018-03-01',
            'resolved_date' => '2018-03-15',
        ]);

        $this->addCondition($patient, [
            'concept_name' => 'Retinal angioma, left eye',
            'concept_code' => 'H35.00',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2014-01-01',
            'severity' => 'mild',
            'laterality' => 'left',
        ]);

        // ── Medications ─────────────────────────────────────────
        $this->addMedication($patient, [
            'drug_name' => 'Dexamethasone 4mg IV q6h (5 days pre-op)',
            'concept_code' => '3264',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2026-02-20',
            'route' => 'intravenous',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Levetiracetam 500mg PO BID (seizure prophylaxis)',
            'concept_code' => '187832',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2026-01-20',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Ferrous sulfate 325mg PO TID (chronic HHT blood loss)',
            'concept_code' => '4167',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2020-01-01',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Omeprazole 20mg PO daily (GI protection)',
            'concept_code' => '7646',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2026-01-20',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Bevacizumab 5mg/kg IV q2wk (held 6 weeks pre-op)',
            'concept_code' => '480167',
            'vocabulary' => 'RxNorm',
            'status' => 'discontinued',
            'start_date' => '2024-01-01',
            'end_date' => '2026-01-15',
        ]);

        $this->addMedication($patient, [
            'drug_name' => 'Tranexamic acid 1g IV (planned pre-op)',
            'concept_code' => '10600',
            'vocabulary' => 'RxNorm',
            'status' => 'active',
            'start_date' => '2026-03-01',
            'route' => 'intravenous',
        ]);

        // ── Procedures ──────────────────────────────────────────
        $this->addProcedure($patient, [
            'procedure_name' => 'Prior cerebellar hemangioblastoma resection',
            'concept_code' => '61510',
            'vocabulary' => 'CPT',
            'performed_at' => '2018-03-15',
            'specialty' => 'Neurosurgery',
            'body_site' => 'Left cerebellum',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Retinal angioma laser photocoagulation',
            'concept_code' => '67228',
            'vocabulary' => 'CPT',
            'performed_at' => '2014-06-10',
            'specialty' => 'Ophthalmology',
            'laterality' => 'left',
        ]);

        $this->addProcedure($patient, [
            'procedure_name' => 'Isovolumic phlebotomy (planned pre-op, reduce Hct from 56% to <50%)',
            'concept_code' => '99195',
            'vocabulary' => 'CPT',
            'performed_at' => '2026-02-28',
            'specialty' => 'Hematology',
        ]);

        // ── Visits ──────────────────────────────────────────────
        $neuroVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-18',
            'department' => 'Neurosurgery',
            'provider_name' => 'Dr. Anders Bergström',
            'reason' => 'Tumor assessment and surgical planning for cerebellar hemangioblastoma',
        ]);

        $neuroradVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-01-25',
            'department' => 'Interventional Neuroradiology',
            'provider_name' => 'Dr. Kenji Tanaka',
            'reason' => 'Pre-operative embolization assessment — deferred due to PAVM shunt risk',
        ]);

        $pulmVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-01',
            'department' => 'Pulmonology / HHT Center',
            'provider_name' => 'Dr. Claire Dupont',
            'reason' => 'Pulmonary AVM management and pre-operative assessment',
        ]);

        $irVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-05',
            'department' => 'Interventional Radiology',
            'provider_name' => 'Dr. Michael Torres',
            'reason' => 'PAVM embolization planning — staged post-neurosurgery',
        ]);

        $hemeVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-15',
            'department' => 'Hematology',
            'provider_name' => 'Dr. Nadia Petrov',
            'reason' => 'Erythrocytosis management, pre-operative phlebotomy planning',
        ]);

        $geneticsVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-08',
            'department' => 'Medical Genetics',
            'provider_name' => 'Dr. Sarah Whitfield',
            'reason' => 'VHL + HHT confirmation, family screening coordination',
        ]);

        $ophthVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-10',
            'department' => 'Ophthalmology',
            'provider_name' => 'Dr. David Okafor',
            'reason' => 'Retinal surveillance — VHL-associated retinal angioma',
        ]);

        $anesthVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-22',
            'department' => 'Neuroanesthesia',
            'provider_name' => 'Dr. Lisa Chang',
            'reason' => 'Pre-operative anesthesia assessment — paradoxical embolism prevention',
        ]);

        $entVisit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'visit_date' => '2026-02-12',
            'department' => 'Otolaryngology (ENT)',
            'provider_name' => 'Dr. Henrik Johansson',
            'reason' => 'HHT epistaxis protocol — nasal intubation contraindicated',
        ]);

        // ── Clinical Notes ──────────────────────────────────────
        $this->addNote($patient, [
            'visit_id' => $neuroVisit->id,
            'note_type' => 'Neurosurgery Operative Plan',
            'note_date' => '2026-01-18',
            'author' => 'Dr. Anders Bergström',
            'content' => "NEUROSURGERY CONSULTATION — OPERATIVE PLAN\n\nPatient: Erik Lindgren, 40M\nDate: 2026-01-18\n\nDIAGNOSIS: 4.2cm solid-cystic hemangioblastoma of the cerebellar vermis with obstructive hydrocephalus. Known VHL Type 1 (germline VHL c.499C>T). Prior left cerebellar hemangioblastoma resected 2018 — surgical cavity stable, no recurrence.\n\nPRESENTATION: 6-week history of progressive headache, truncal ataxia, nausea. MRI demonstrates 4.2cm enhancing mass in cerebellar vermis with large cystic component causing fourth ventricle compression and triventricular hydrocephalus.\n\nSURGICAL PLAN:\n- Suboccipital craniotomy, midline approach\n- Neuronavigation-guided resection with intraoperative MRI capability\n- External ventricular drain (EVD) placement at induction — mandatory given hydrocephalus\n- En bloc resection preferred — hemangioblastomas are highly vascular, piecemeal resection causes catastrophic hemorrhage\n- ICG videoangiography to map tumor vascularity intraoperatively\n\nCRITICAL COMPLICATING FACTORS:\n1. BILATERAL PAVMs (HHT) — Any IV air will cross PAVMs and reach systemic/cerebral circulation (paradoxical embolism). ALL IV lines must be air-free. Air filters on all infusion sets. NO nitrous oxide.\n2. Chronic hypoxemia (baseline SpO2 89%) — Limited physiologic reserve. Anesthesia team alerted.\n3. Secondary erythrocytosis (Hct 56%) — Hyperviscosity increases thrombotic risk. Pre-op phlebotomy to target Hct <50%.\n4. Prior posterior fossa surgery — Adhesions expected, particularly along left cerebellar surface.\n\nPOSITIONING: Prone, Mayfield pins, head flexed. Precordial Doppler mandatory (air embolism detection given PAVM shunt).\n\nTARGET SURGERY DATE: 2026-03-01\nESTIMATED OR TIME: 6-8 hours",
        ]);

        $this->addNote($patient, [
            'visit_id' => $anesthVisit->id,
            'note_type' => 'Neuroanesthesia Paradoxical Embolism Prevention Protocol',
            'note_date' => '2026-02-22',
            'author' => 'Dr. Lisa Chang',
            'content' => "NEUROANESTHESIA PRE-OPERATIVE ASSESSMENT — PARADOXICAL EMBOLISM PREVENTION\n\nPatient: Erik Lindgren, 40M\nASA Physical Status: III\nDate: 2026-02-22\n\nKEY RISK: Bilateral PAVMs create obligate right-to-left shunt (Grade 3 on bubble echo). ANY venous air embolism will cross to systemic circulation causing stroke, coronary air embolism, or death. This patient has both posterior fossa surgery (air embolism risk) AND PAVMs (paradoxical embolism conduit).\n\nMANDATORY PROTOCOL:\n1. NO NITROUS OXIDE — expands any trapped air bubbles\n2. NO NASAL INTUBATION — HHT telangiectasias in nasal mucosa risk catastrophic epistaxis\n3. AIR-FREE IV LINES — All infusion sets must have 0.2-micron air-eliminating filters. No gravity drips.\n4. Positioning: PRONE — mandatory for posterior fossa access. Increases venous air embolism risk vs sitting position but sitting position absolutely contraindicated with PAVMs.\n5. Precordial Doppler + end-tidal CO2 monitoring for air embolism detection\n6. Central venous catheter for air aspiration if VAE detected\n7. Jugular venous compression available (Queckenstedt maneuver)\n\nAIRWAY: Oral intubation only. Video laryngoscopy preferred. Mallampati II.\n\nHEMODYNAMIC CONSIDERATIONS:\n- Baseline SpO2 89% on room air — will not improve significantly with supplemental O2 (fixed shunt)\n- Target SpO2 during surgery: 88-92% (patient's baseline)\n- Erythrocytosis (Hct 56% pre-phlebotomy, target <50% post-phlebotomy)\n- Arterial line mandatory, CVP monitoring\n\nBLOOD PRODUCTS: Type and crossmatch 4 units PRBCs. Cell saver requested but must ensure no tumor contamination (hemangioblastoma is benign but highly vascular).\n\nPOST-OP: ICU bed reserved. EVD management per neurosurgery protocol.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $pulmVisit->id,
            'note_type' => 'Pulmonology PAVM Assessment',
            'note_date' => '2026-02-01',
            'author' => 'Dr. Claire Dupont',
            'content' => "PULMONOLOGY / HHT CENTER CONSULTATION — PAVM ASSESSMENT\n\nPatient: Erik Lindgren, 40M\nDate: 2026-02-01\n\nDIAGNOSIS: Hereditary hemorrhagic telangiectasia type 1 (ENG mutation) with bilateral pulmonary AVMs and chronic hypoxemia.\n\nPAVM BURDEN:\n- Right lower lobe: Complex PAVM, 18mm feeding artery (2 feeding arteries on angiography)\n- Left lower lobe: Simple PAVM, 8mm feeding artery\n- Left upper lobe: Small PAVM, 4mm feeding artery\n- Bubble echocardiogram: Grade 3 right-to-left shunt (bubbles in LA within 3-5 cardiac cycles)\n\nHYPOXEMIA: PaO2 58 mmHg, SaO2 89% on room air. A-a gradient 48 mmHg (markedly elevated, consistent with large anatomic R-to-L shunt). This is fixed shunt physiology — supplemental O2 has limited benefit.\n\nSTAGED TREATMENT DECISION:\nPAVM embolization is indicated (feeding arteries >3mm) but DEFERRED until after neurosurgery because:\n1. Neurosurgery is urgent (symptomatic hydrocephalus)\n2. PAVM embolization requires anticoagulation peri-procedurally — contraindicated before craniotomy\n3. Post-embolization pleurisy could compromise prone positioning for craniotomy\n\nPLAN: Proceed with neurosurgery first with strict paradoxical embolism precautions. Stage PAVM embolization 6-8 weeks post-craniotomy. RLL complex PAVM first (largest shunt contributor), then LLL PAVM. LUL PAVM (4mm feeder) may be observed.\n\nANTIBIOTIC PROPHYLAXIS: All PAVMs are conduits for paradoxical septic emboli. Patient should receive antibiotic prophylaxis for any dental or invasive procedure indefinitely.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $hemeVisit->id,
            'note_type' => 'Hematology Phlebotomy Protocol',
            'note_date' => '2026-02-15',
            'author' => 'Dr. Nadia Petrov',
            'content' => "HEMATOLOGY CONSULTATION — ERYTHROCYTOSIS MANAGEMENT\n\nPatient: Erik Lindgren, 40M\nDate: 2026-02-15\n\nDIAGNOSIS: Secondary erythrocytosis due to chronic hypoxemia from pulmonary AVMs (HHT).\n\nLABORATORY: Hgb 18.4, Hct 56%, EPO 42 mIU/mL (appropriately elevated for hypoxemia — confirms secondary, not primary erythrocytosis). Ferritin 18 (depleted from chronic HHT blood loss + iron supplementation redirected to erythropoiesis).\n\nPATHOPHYSIOLOGY: Chronic hypoxemia (PaO2 58) drives EPO-mediated erythrocytosis as compensatory mechanism. However, Hct >50% creates hyperviscosity that paradoxically worsens oxygen delivery and increases thrombotic risk — especially concerning for posterior fossa surgery where venous sinus thrombosis is a recognized complication.\n\nPRE-OPERATIVE PHLEBOTOMY PROTOCOL:\n- Isovolumic phlebotomy on 2026-02-28 (1 day before surgery)\n- Remove 500mL whole blood, replace with 500mL normal saline\n- Target Hct <50% (ideally 48-50%)\n- Do NOT target normal Hct — patient needs compensatory erythrocytosis for tissue oxygenation\n- Repeat Hct 4 hours post-phlebotomy to confirm target achieved\n\nIRON STATUS: Ferritin 18 (depleted). Continue ferrous sulfate TID despite phlebotomy — iron deficiency worsens symptoms independent of Hct. Monitor reticulocyte count.\n\nTRANEXAMIC ACID: 1g IV pre-incision approved for neurosurgery. Hemangioblastomas are extremely vascular and TXA reduces surgical blood loss. No contraindication in this patient despite erythrocytosis — thrombotic risk mitigated by phlebotomy.",
        ]);

        $this->addNote($patient, [
            'visit_id' => $geneticsVisit->id,
            'note_type' => 'Genetics Counseling — Dual Autosomal Dominant Conditions',
            'note_date' => '2026-02-08',
            'author' => 'Dr. Sarah Whitfield',
            'content' => "MEDICAL GENETICS CONSULTATION — DUAL GENETIC SYNDROMES\n\nPatient: Erik Lindgren, 40M\nDate: 2026-02-08\n\nGENETIC DIAGNOSES:\n1. Von Hippel-Lindau disease Type 1 — VHL c.499C>T (p.Arg167Trp), chromosome 3p25.3, heterozygous, pathogenic\n2. Hereditary hemorrhagic telangiectasia Type 1 — ENG c.1088G>A (p.Arg363Gln), chromosome 9q34.11, heterozygous, pathogenic\n\nBoth conditions are autosomal dominant with high penetrance. This patient carries TWO independent germline pathogenic variants on different chromosomes — an exceptionally rare combination that creates unique management challenges.\n\nVHL TYPE 1 SURVEILLANCE PROTOCOL:\n- Annual brain/spine MRI (hemangioblastomas — current presentation)\n- Annual abdominal MRI (renal clear cell carcinoma, pheochromocytoma, pancreatic NETs)\n- Annual ophthalmologic exam (retinal hemangioblastomas)\n- Type 1 VHL: hemangioblastomas + renal cancer risk, LOW pheochromocytoma risk\n- Current pheo screening NEGATIVE (plasma metanephrines normal)\n\nHHT TYPE 1 (ENG MUTATION) SURVEILLANCE:\n- CT chest for PAVMs every 5 years (or sooner if symptoms change)\n- Hepatic AVM monitoring by MRI\n- 50%+ lifetime prevalence of PAVMs with ENG mutations (higher than ALK1/HHT2)\n- Cerebral AVM screening (done — negative on current MRA)\n\nFAMILY SCREENING RECOMMENDATIONS:\n- First-degree relatives should be tested for BOTH variants\n- Each child of patient has 50% chance of inheriting VHL AND 50% chance of inheriting ENG (independent assortment)\n- 25% chance a child inherits both conditions\n- Genetic counseling for reproductive planning recommended",
        ]);

        $this->addNote($patient, [
            'visit_id' => $pulmVisit->id,
            'note_type' => 'Bevacizumab Hold Rationale — Pharmacogenomic Tension',
            'note_date' => '2026-02-01',
            'author' => 'Dr. Claire Dupont',
            'content' => "CLINICAL NOTE — BEVACIZUMAB HOLD RATIONALE\n\nPatient: Erik Lindgren, 40M\nDate: 2026-02-01\n\nPHARMACOGENOMIC TENSION:\nBevacizumab (anti-VEGF monoclonal antibody) was prescribed for HHT-related epistaxis and PAVM management. Bevacizumab reduces VEGF-driven angiogenesis, which is the pathologic driver of telangiectasias and AVMs in HHT.\n\nHowever, VHL disease is also VEGF-driven — VHL protein normally degrades HIF-1α, and loss-of-function VHL mutations cause constitutive HIF-1α activation and VEGF overproduction, driving hemangioblastoma growth.\n\nTHERAPEUTIC PARADOX:\n- FOR HHT: Bevacizumab reduces PAVM growth, epistaxis, and GI bleeding\n- FOR VHL: Anti-VEGF theoretically beneficial (reduces hemangioblastoma vascularity)\n- HOWEVER: Bevacizumab is held 6 weeks pre-operatively because:\n  1. Wound healing impairment (major craniotomy)\n  2. Hemorrhagic risk (posterior fossa surgery)\n  3. Thrombotic microangiopathy risk\n\nPLAN: Bevacizumab discontinued 2026-01-15 (6 weeks pre-surgery). Resume 6-8 weeks post-operatively once craniotomy wound fully healed and confirmed no CSF leak. Consider ongoing bevacizumab as dual-indication therapy (HHT + VHL) long-term.\n\nNOTE: This dual-syndrome pharmacogenomic scenario is exceptionally rare and warrants multidisciplinary discussion at tumor board.",
        ]);

        // ── Lab Panels ──────────────────────────────────────────

        // Hematology (2026-02-15)
        $this->addLabPanel($patient, '2026-02-15', [
            ['Hemoglobin',     '718-7',   18.4, 'g/dL',   13.5, 17.5, 'H'],
            ['Hematocrit',     '4544-3',  56,   '%',      38.3, 48.6, 'H'],
            ['Platelet Count', '777-3',   198,  'K/uL',   150,  400,  null],
            ['WBC',            '6690-2',  6.8,  'K/uL',   4.5,  11.0, null],
            ['Reticulocytes',  '4679-7',  2.8,  '%',      0.5,  2.5,  'H'],
            ['EPO',            '2053-7',  42,   'mIU/mL', 4,    24,   'H'],
            ['Ferritin',       '2276-4',  18,   'ng/mL',  30,   400,  'L'],
        ]);

        // Coagulation (2026-02-15)
        $this->addLabPanel($patient, '2026-02-15', [
            ['INR',        '6301-6',  1.0, null,     0.8, 1.2, null],
            ['aPTT',       '3173-2',  30,  'sec',    25,  35,  null],
            ['Fibrinogen', '3255-7',  310, 'mg/dL',  200, 400, null],
            ['D-dimer',    '48065-7', 0.8, 'mcg/mL', null, 0.5, 'H'],
        ]);

        // Pheochromocytoma Screening (2026-02-10)
        $this->addLabPanel($patient, '2026-02-10', [
            ['Plasma free metanephrines',     '44628-0', 0.3, 'nmol/L',    null, 0.5,  null],
            ['Plasma free normetanephrines',  '44629-8', 0.8, 'nmol/L',    null, 0.9,  null],
            ['24hr urine metanephrines',      '2680-7',  180, 'mcg/24hr',  null, 400,  null],
            ['24hr urine VMA',                '2926-4',  5.2, 'mg/24hr',   null, 6.8,  null],
        ]);

        // ABG Room Air (2026-02-15)
        $this->addLabPanel($patient, '2026-02-15', [
            ['PaO2',         '2703-7',  58,  'mmHg', 80,   100,  'L'],
            ['PaCO2',        '2019-8',  34,  'mmHg', 35,   45,   'L'],
            ['pH',           '2744-1',  7.44, null,  7.35, 7.45, null],
            ['SaO2',         '2708-6',  89,  '%',    95,   100,  'L'],
            ['A-a gradient', '19991-9', 48,  'mmHg', null, 15,   'H'],
        ]);

        // Renal/Hepatic (2026-02-15)
        $this->addLabPanel($patient, '2026-02-15', [
            ['Creatinine', '2160-0',  0.8, 'mg/dL',         0.6,  1.1, null],
            ['eGFR',       '33914-3', 90,  'mL/min/1.73m2', 60,   null, null],
            ['ALT',        '1742-6',  32,  'U/L',           7,    56,   null],
            ['AST',        '1920-8',  28,  'U/L',           10,   40,   null],
        ]);

        // ── Observations ────────────────────────────────────────
        $this->addObservation($patient, [
            'observation_name' => 'ASA Physical Status',
            'category' => 'clinical_score',
            'value_text' => 'III',
            'observed_at' => '2026-02-22',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Karnofsky Performance Status (KPS)',
            'category' => 'functional_status',
            'value_numeric' => 60,
            'observed_at' => '2026-02-22',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Modified Rankin Scale',
            'category' => 'functional_status',
            'value_numeric' => 3,
            'observed_at' => '2026-02-22',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'SpO2 room air',
            'category' => 'vital_signs',
            'value_numeric' => 89,
            'unit' => '%',
            'observed_at' => '2026-02-15',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Paradoxical Embolism Risk',
            'category' => 'clinical_assessment',
            'value_text' => 'HIGH — any IV air crosses PAVMs to systemic circulation',
            'observed_at' => '2026-02-22',
        ]);

        $this->addObservation($patient, [
            'observation_name' => 'Largest PAVM feeding artery diameter',
            'category' => 'tumor_measurement',
            'value_numeric' => 18,
            'unit' => 'mm',
            'observed_at' => '2026-01-20',
        ]);

        // ── Imaging Studies ─────────────────────────────────────
        $brainMri = $this->addImagingStudy($patient, [
            'study_date' => '2026-01-15',
            'modality' => 'MRI',
            'body_site' => 'Brain',
            'description' => 'Brain MRI with gadolinium',
            'indication' => 'Progressive headache and ataxia in VHL patient',
            'findings' => '4.2cm solid-cystic enhancing mass in cerebellar vermis consistent with hemangioblastoma. Large cystic component with enhancing mural nodule (2.1cm). Fourth ventricle compressed with resultant triventricular hydrocephalus (lateral ventricles 18mm, third ventricle 12mm). Prior left cerebellar surgical cavity — stable, no recurrence. No supratentorial hemangioblastomas. No leptomeningeal enhancement.',
        ]);

        $this->addImagingMeasurement($brainMri, [
            'measurement_name' => 'Hemangioblastoma greatest dimension',
            'value_numeric' => 4.2,
            'unit' => 'cm',
        ]);

        $this->addImagingMeasurement($brainMri, [
            'measurement_name' => 'Mural nodule diameter',
            'value_numeric' => 2.1,
            'unit' => 'cm',
        ]);

        $mraBrain = $this->addImagingStudy($patient, [
            'study_date' => '2026-01-16',
            'modality' => 'MRI',
            'body_site' => 'Brain',
            'description' => 'MR Angiography of the brain',
            'indication' => 'Vascular mapping for hemangioblastoma resection',
            'findings' => 'Tumor supplied by branches of PICA and SCA. No associated aneurysm. No cerebral AVM (important negative in HHT patient). Circle of Willis intact. No vascular stenosis.',
        ]);

        $ctChest = $this->addImagingStudy($patient, [
            'study_date' => '2026-01-20',
            'modality' => 'CT',
            'body_site' => 'Chest',
            'description' => 'CT Chest — HHT protocol',
            'indication' => 'Pulmonary AVM surveillance in HHT Type 1',
            'findings' => 'Right lower lobe: complex pulmonary AVM with 18mm feeding artery and two draining veins. Left lower lobe: simple PAVM with 8mm feeding artery. Left upper lobe: small PAVM with 4mm feeding artery. All PAVMs stable compared to prior imaging. No pulmonary nodules. No pleural effusion.',
        ]);

        $this->addImagingMeasurement($ctChest, [
            'measurement_name' => 'RLL PAVM feeding artery diameter',
            'value_numeric' => 18,
            'unit' => 'mm',
        ]);

        $this->addImagingMeasurement($ctChest, [
            'measurement_name' => 'LLL PAVM feeding artery diameter',
            'value_numeric' => 8,
            'unit' => 'mm',
        ]);

        $this->addImagingMeasurement($ctChest, [
            'measurement_name' => 'LUL PAVM feeding artery diameter',
            'value_numeric' => 4,
            'unit' => 'mm',
        ]);

        $bubbleEcho = $this->addImagingStudy($patient, [
            'study_date' => '2026-01-22',
            'modality' => 'US',
            'body_site' => 'Heart',
            'description' => 'Bubble contrast echocardiogram',
            'indication' => 'Quantify right-to-left shunt via PAVMs',
            'findings' => 'Grade 3 right-to-left shunt — agitated saline bubbles appear in left atrium within 3-5 cardiac cycles (consistent with pulmonary AVM shunt, not intracardiac). LVEF 60%, normal biventricular function. No intracardiac septal defect. Normal valvular function.',
        ]);

        $this->addImagingMeasurement($bubbleEcho, [
            'measurement_name' => 'LVEF',
            'value_numeric' => 60,
            'unit' => '%',
        ]);

        $pulmAngio = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-01',
            'modality' => 'XR',
            'body_site' => 'Chest',
            'description' => 'Pulmonary angiography',
            'indication' => 'Detailed PAVM anatomy for embolization planning',
            'findings' => 'Three pulmonary AVMs confirmed. RLL PAVM is complex with 2 feeding arteries (18mm and 6mm) and 2 draining veins. LLL PAVM is simple with single 8mm feeding artery. LUL PAVM is simple with 4mm feeding artery. No additional occult PAVMs identified.',
        ]);

        $abdMri = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-05',
            'modality' => 'MRI',
            'body_site' => 'Abdomen',
            'description' => 'Abdominal MRI — VHL protocol',
            'indication' => 'VHL surveillance — renal, adrenal, pancreatic screening',
            'findings' => 'Hepatic AVMs measuring 2.3cm in right lobe (HHT-related, stable). No renal masses. No renal cysts suspicious for clear cell carcinoma. Normal adrenal glands bilaterally — no pheochromocytoma. No pancreatic neuroendocrine tumors. No pancreatic cysts.',
        ]);

        $this->addImagingMeasurement($abdMri, [
            'measurement_name' => 'Hepatic AVM diameter',
            'value_numeric' => 2.3,
            'unit' => 'cm',
        ]);

        $spinalMri = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-05',
            'modality' => 'MRI',
            'body_site' => 'Spine',
            'description' => 'Spinal MRI with gadolinium — VHL surveillance',
            'indication' => 'Screen for spinal hemangioblastomas in VHL',
            'findings' => 'No spinal cord hemangioblastomas. No intramedullary or extramedullary enhancing lesions. Normal conus medullaris and cauda equina.',
        ]);

        $priorBrainMri = $this->addImagingStudy($patient, [
            'study_date' => '2025-06-15',
            'modality' => 'MRI',
            'body_site' => 'Brain',
            'description' => 'Brain MRI with gadolinium (prior surveillance)',
            'indication' => 'Annual VHL brain surveillance',
            'findings' => 'Prior left cerebellar surgical cavity — stable. No new enhancing lesions in cerebellar vermis or hemispheres. No hemangioblastoma visible at current tumor site — demonstrates interval development over 6 months. No hydrocephalus at this time. No supratentorial abnormality.',
        ]);

        $fundoscopy = $this->addImagingStudy($patient, [
            'study_date' => '2026-02-10',
            'modality' => 'US',
            'body_site' => 'Eyes',
            'description' => 'Fundoscopic photography',
            'indication' => 'VHL retinal angioma surveillance',
            'findings' => 'Stable retinal angioma left eye, inferotemporal arcade. Feeder arteriole and draining venule visible. No growth compared to prior exam. No new retinal lesions in either eye. No vitreous hemorrhage. Macula normal bilaterally.',
        ]);

        // ── Genomic Variants ────────────────────────────────────
        $this->addGenomicVariant($patient, [
            'gene' => 'VHL',
            'variant' => 'c.499C>T (p.Arg167Trp)',
            'variant_type' => 'SNV',
            'chromosome' => 'chr3',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'classification' => 'pathogenic',
            'actionability' => 'surveillance_protocol',
            'report_date' => '2010-06-01',
        ]);

        $this->addGenomicVariant($patient, [
            'gene' => 'ENG',
            'variant' => 'c.1088G>A (p.Arg363Gln)',
            'variant_type' => 'SNV',
            'chromosome' => 'chr9',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'classification' => 'pathogenic',
            'actionability' => 'PAVM_screening',
            'report_date' => '2015-03-01',
        ]);

        // ── Condition Eras ──────────────────────────────────────
        $this->addConditionEra($patient, [
            'condition_name' => 'VHL surveillance era',
            'era_start' => '2010-01-01',
            'era_end' => null,
            'occurrence_count' => 20,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'HHT management era',
            'era_start' => '2015-01-01',
            'era_end' => null,
            'occurrence_count' => 12,
        ]);

        $this->addConditionEra($patient, [
            'condition_name' => 'Chronic hypoxemia era',
            'era_start' => '2015-06-01',
            'era_end' => null,
            'occurrence_count' => 10,
        ]);

        // ── Drug Eras ───────────────────────────────────────────
        $this->addDrugEra($patient, [
            'drug_name' => 'Bevacizumab',
            'era_start' => '2024-01-01',
            'era_end' => '2026-01-15',
            'gap_days' => 0,
        ]);

        $this->addDrugEra($patient, [
            'drug_name' => 'Ferrous sulfate',
            'era_start' => '2020-01-01',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
