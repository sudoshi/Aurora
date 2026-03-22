# Synthetic Clinical Demo Patients — Design Spec

**Date**: 2026-03-21
**Status**: Approved
**Audience**: Clinical collaborators (physicians/researchers) — must be medically defensible

## Overview

12 synthetic, fully anonymized patient cases to demonstrate Aurora's clinical intelligence platform. Each case populates every data type in Aurora's OMOP-inspired clinical schema: patients, conditions, medications, procedures, measurements, observations, visits, clinical notes, imaging studies (with series/instances/measurements/segmentations), genomic variants, condition eras, and drug eras.

All data is hand-crafted using published clinical literature, NCCN guidelines, landmark trial data, and real reference ranges to ensure physician-level accuracy.

## Patient Roster

### Category A: Rare Disease (3 patients)

#### A1 — Hereditary Transthyretin Amyloidosis (hATTR)
- **ICD-10**: E85.1
- **Demographics**: 52-60yo African American Male
- **Temporal depth**: 8 years (3yr diagnostic odyssey + 5yr treatment)
- **Key genomic finding**: TTR c.364G>A (p.Val142Ile), pathogenic, heterozygous
- **Diagnostic journey**: PCP → Orthopedics (bilateral carpal tunnel release, amyloid not stained) → Cardiology (HFpEF, echo: LV hypertrophy 14mm, granular sparkling) → Neurology (axonal polyneuropathy) → GI (weight loss, IBS diagnosis) → Cardiac MRI (LGE, T1 elevated, ECV 0.55) → Tc-99m PYP scan (Grade 3 uptake, H/CL 1.8) → Hematology (normal free light chains, rules out AL) → Genetics (TTR Val122Ile) → Endomyocardial biopsy (Congo red+, mass spec confirms TTR)
- **Treatment**: Tafamidis 61mg daily, midodrine, gabapentin, diflunisal 250mg BID, ICD implantation
- **Labs tracked**: NT-proBNP (1,850→4,500→2,400), Troponin T, eGFR (declining 72→52), TTR/prealbumin, free light chains
- **Imaging**: Serial echo (progressive LV hypertrophy), cardiac MRI 1.5T, Tc-99m PYP, EMG/NCS
- **Pathology**: Carpal tunnel tenosynovium (retrospective Congo red+), endomyocardial biopsy (LC-MS/MS: TTR), fat pad aspirate
- **Comorbidities**: HFpEF/restrictive cardiomyopathy (I43), bilateral carpal tunnel (G56.0), autonomic neuropathy (G90.09), CKD 3a (N18.31), VT requiring ICD (I47.20)
- **Demo value**: Diagnostic odyssey (4.6yr avg delay), health equity (3-4% AA carrier rate), retrospective missed clue (unstained carpal tunnel tissue), multi-modal data richness

#### A2 — Tuberous Sclerosis Complex (TSC) [PEDIATRIC]
- **ICD-10**: Q85.1
- **Demographics**: Newborn → 14yo Hispanic Female
- **Temporal depth**: 14 years (prenatal to adolescence)
- **Key genomic finding**: TSC2 c.5024C>T (p.Pro1675Leu), pathogenic, de novo
- **Disease arc**: Prenatal cardiac rhabdomyomas → neonatal echo → brain MRI (cortical tubers, SENs) → infantile spasms at 5mo (vigabatrin) → retinal hamartomas → focal seizures (oxcarbazepine) → hypomelanotic macules/shagreen patch → ASD diagnosis → SEN→SEGA transformation at age 6 → everolimus initiated → bilateral renal AMLs at age 8 → facial angiofibromas (topical sirolimus) → drug-resistant epilepsy → CBD (Epidiolex) added → SEEG eval → VNS implanted → transition planning
- **Treatment**: Vigabatrin → oxcarbazepine → everolimus 4.5mg/m² → topical sirolimus 0.1% → cannabidiol 10mg/kg/day → VNS
- **Labs tracked**: Everolimus trough (target 5-15 ng/mL), fasting lipids (progressive dyslipidemia), CBC (mild cytopenias), eGFR, LFTs, fasting glucose
- **Imaging**: Fetal US, serial neonatal echo (rhabdomyoma regression), serial brain MRI (tuber mapping, SEN→SEGA→regression), serial renal MRI (AML growth), chest CT (LAM screening), SEEG
- **Pathology**: None (diagnosis clinical + genetic per TSC guidelines)
- **Comorbidities**: Infantile spasms/West syndrome (G40.822), drug-resistant focal epilepsy (G40.119), SEGA (D33.0), renal AMLs (D30.0), ASD (F84.0), mild intellectual disability (F70), retinal hamartomas (D31.20), everolimus side effects (dyslipidemia, stomatitis)
- **Demo value**: Pediatric longitudinal, 10+ specialties, genomics→therapeutics pipeline (TSC2→mTOR→everolimus), guideline-driven surveillance, evolving phenotype by age

#### A3 — Catastrophic Antiphospholipid Syndrome (CAPS)
- **ICD-10**: D68.61
- **Demographics**: 26-36yo South Asian Female
- **Temporal depth**: 10 years (8yr pre-catastrophic APS + 2yr post-CAPS)
- **Key genomic findings**: HLA-DRB1*04:01 (APS susceptibility), CFH c.2850G>T (complement variant), CYP2C9*3/*1 + VKORC1 -1639G>A (warfarin sensitivity)
- **Disease arc**: 2 pregnancy losses (placental infarction) → lupus anticoagulant positive → triple-positive aPL confirmed → successful pregnancy on enoxaparin → left DVT → warfarin lifelong → livedo reticularis (skin biopsy: thrombotic vasculopathy) → TIA (subtherapeutic INR) → renal biopsy (APS nephropathy, TMA) → **CAPS event** triggered by E. coli UTI: bilateral DVT→renal failure (Cr 4.2)→ARDS (P/F 110)→hepatic ischemia (AST 1200)→thrombocytopenia (42K)→digital gangrene
- **CAPS treatment**: IV heparin + methylprednisolone 1g x3 + PLEX x5 + IVIG 2g/kg + rituximab 375mg/m² x2
- **Labs tracked**: Lupus anticoagulant, anticardiolipin IgG (58→92→45), anti-β2GPI IgG, platelets, creatinine (0.8→4.2→1.6), LDH (180→2,800→195), haptoglobin, schistocytes, complement C3/C4, D-dimer, INR
- **Imaging**: Obstetric US, LE duplex, brain MRI (chronic WM lesions), CTPA (bilateral PE), CT abd (bilateral renal + hepatic infarcts), echo (RV dilation, TAPSE 12mm), serial CXR (ARDS), MRA renal (cortical scarring)
- **Pathology**: 2 placentas (villous infarction, decidual vasculopathy), skin biopsy (arteriolar thrombosis, no vasculitis), renal biopsy (TMA, fibrin thrombi, no immune complex), digital amputation specimen
- **Comorbidities**: Recurrent pregnancy loss (N96), bilateral DVT (I82.40), PE (I26.99), APS nephropathy/CKD 3b (N18.32), ARDS (J80), hepatic ischemia (K76.89), digital gangrene (I73.01), livedo reticularis (R23.1), TIA (G45.9)
- **Demo value**: Escalating severity pattern, ICU data density, pharmacogenomic warfarin sensitivity, triple-positive serology risk stratification, infection-as-precipitant detection

### Category B: Pre-Surgical (3 patients)

#### B1 — Redo CABG + Aortic Valve Replacement
- **Demographics**: 68yo White Male, BMI 32.4
- **Temporal depth**: 6-month pre-op workup
- **Surgical scenario**: Redo median sternotomy, CABG x3 (LIMA-LAD, SVG-LCx, SVG-RCA) + bioprosthetic AVR, on CPB
- **Risk scores**: ASA IV, STS 8.2%, EuroSCORE II 9.6%, MELD 17, Lee RCRI 4pts, CHA₂DS₂-VASc 5
- **Comorbidity burden**: Severe AS (AVA 0.7cm², mean gradient 48mmHg), 3-vessel CAD with occluded prior SVG-LAD, alcohol-related cirrhosis Child-Pugh B, CKD 3b (eGFR 38), T2DM insulin-dependent (HbA1c 8.1%), chronic AFib, COPD GOLD II (FEV1 58%), obesity, prior DVT
- **Key labs**: Hgb 10.2, Plt 78K, INR 1.6 (baseline off warfarin), fibrinogen 148, albumin 2.8, bilirubin 2.4, ammonia 62, NT-proBNP 2840, hs-TnI 42, cystatin C 1.8
- **Imaging**: TTE (AS + reduced EF 40% + MR), coronary angiography (3-vessel + occluded SVG), CT chest (RV adherent to sternum, porcelain aorta), abdominal US with Doppler (nodular liver, splenomegaly 16cm, ascites), PFTs
- **Medications**: 11 drugs including warfarin (held), spironolactone, insulin, metoprolol, lactulose, rifaximin
- **Anesthesia concerns**: Femoral crash-on capability for re-entry, TEG/ROTEM-guided transfusion, hepatorenal syndrome risk from CPB, NIRS cerebral oximetry
- **Multi-specialty**: Cardiac surgery, interventional cardiology (TAVR debate rejected), hepatology, nephrology, hematology, pulmonology, endocrinology, cardiac anesthesia
- **Demo value**: 5 converging risk scores, heart team TAVR vs. surgical decision, MELD trending (14→17 over 4mo), coagulopathy on CPB

#### B2 — Cytoreductive Surgery with HIPEC
- **Demographics**: 54yo Hispanic Female, BMI 26.8
- **Temporal depth**: 3-week pre-op snapshot
- **Surgical scenario**: Complete CRS (omentectomy, right hemicolectomy, splenectomy, cholecystectomy, BSO, bilateral diaphragmatic peritonectomy) + HIPEC with mitomycin C 35mg/m² at 42°C for low-grade appendiceal mucinous neoplasm (LAMN/pseudomyxoma peritonei), PCI 22/39
- **Risk scores**: ASA III, PCI 22/39, Lee RCRI 2pts, ACS NSQIP 34% complication rate, PNI 38.2
- **Comorbidity burden**: Pseudomyxoma peritonei (C78.6), CAD s/p DES to LAD 4 months ago (on DAPT), HTN, T2DM non-insulin, hypothyroidism, moderate malnutrition (albumin 3.0, prealbumin 12), iron deficiency anemia (Hgb 10.8, ferritin 12), depression (on sertraline)
- **Key labs**: VerifyNow P2Y12 68 PRU (significant platelet inhibition), CEA 14.2, CA-125 82, CA 19-9 48, Mg 1.6 (low), PO4 2.2 (low)
- **Imaging**: CT abd/pelvis (diffuse mucinous ascites, omental cake 12x8cm, liver capsule scalloping), CT chest (clear), PET-CT (SUVmax 3.2), echo (EF 55%), diagnostic laparoscopy (PCI confirmed)
- **Pathology**: Laparoscopic biopsy: LAMN, DPAM histology, Ki-67 8%, no signet ring cells
- **Medications**: Aspirin (continue) + clopidogrel (held 5d, cangrelor bridge discussed), metformin (held 48h), empagliflozin (held 3d — euglycemic DKA risk), sertraline (continue — serotonin syndrome awareness)
- **Anesthesia concerns**: 10-14hr case, PA catheter, HIPEC hyperthermia (core temp 39-40°C), massive fluid shifts (8-15L crystalloid), epidural T8-T10, granisetron over ondansetron (sertraline interaction)
- **Demo value**: Competing urgencies (cancer progression vs. stent protection), HIPEC physiology, nutritional pre-habilitation tracking (prealbumin trending)

#### B3 — Posterior Fossa Hemangioblastoma with VHL + HHT
- **Demographics**: 41yo Northern European Male, BMI 23.1
- **Temporal depth**: 2-month workup
- **Surgical scenario**: Suboccipital craniotomy, microsurgical resection of 4.2cm cerebellar vermian hemangioblastoma with neuronavigation and neurophysiology monitoring, EVD placement
- **Risk scores**: ASA III, KPS 60, mRS 3
- **Comorbidity burden**: Cerebellar hemangioblastoma/VHL (D33.1, Q85.8), HHT type 1 (I78.0), bilateral pulmonary AVMs (largest feeding artery 18mm), chronic hypoxemia (SpO2 88-91%, PaO2 58), secondary erythrocytosis (Hgb 18.4, Hct 56%), obstructive hydrocephalus (G91.1), hepatic AVMs, prior cerebellar hemangioblastoma resection 8yr ago, prior retinal angioma laser
- **Genomic findings**: VHL c.499C>T (p.Arg167Trp) exon 3 missense — VHL Type 1; ENG c.1088G>A (p.Arg363Gln) — HHT Type 1 (high PAVM prevalence)
- **Key labs**: Hgb 18.4, Hct 56%, EPO 42 (elevated), ferritin 18 (low despite erythrocytosis — HHT blood loss), PaO2 58, A-a gradient 48, plasma free metanephrines normal (pheo excluded), D-dimer 0.8
- **Imaging**: Brain MRI (4.2cm solid-cystic hemangioblastoma, triventricular hydrocephalus, prior surgical cavity), MRA brain (PICA/SCA feeders), CT chest HHT protocol (3 PAVMs: RLL 18mm, LLL 8mm, LUL 4mm feeding arteries), bubble echo (Grade 3 R-to-L shunt), pulmonary angiography, abdominal MRI VHL protocol (hepatic AVMs, no renal masses, no pheo, no pNET)
- **Medications**: Dexamethasone 4mg q6h (perioperative), levetiracetam 500mg BID, ferrous sulfate, TXA (planned), bevacizumab (held 6wk pre-op — anti-VEGF for HHT, but VHL tumors are VEGF-driven)
- **Anesthesia concerns**: PARADOXICAL AIR EMBOLISM (any IV air crosses PAVMs → arterial stroke); prone position mandatory (sitting contraindicated); nitrous oxide absolutely contraindicated; no nasal intubation (HHT mucosal telangiectasias); isovolumic phlebotomy to Hct <50%; realistic SpO2 target >85%; PEEP contraindicated (ICP)
- **Demo value**: Two independent genetic syndromes compounding surgical risk, every anesthesia decision is life-or-death, pharmacogenomic tension (bevacizumab hold), rarest and most intellectually challenging case

### Category C: Oncology (3 patients)

#### C1 — EGFR-Mutant Lung Adenocarcinoma
- **ICD-10**: C34.11
- **Demographics**: 62yo White Male, never-smoker
- **Temporal depth**: 5 years (2021-2026), 4 treatment lines
- **Stage**: cT2a N2 M1c (Stage IVB) — brain mets at presentation
- **Genomic profile**: EGFR L858R (exon 21, pathogenic), TP53 R248W, TMB 4.2 mut/Mb, MSS
- **Pathology**: CT-guided core biopsy RUL: adenocarcinoma, acinar, G2. TTF-1+, Napsin-A+, CK7+, p40-. PD-L1 TPS 15%, Ki-67 35%
- **Treatment lines**:
  1. Osimertinib 80mg daily (23mo, PR -69%, brain mets near-CR) → PD with CNS + systemic
  2. Amivantamab 1400mg IV Q2W + lazertinib 240mg daily (14mo) + SRS to temporal met → PD with liver met
  3. Carboplatin AUC5 + pemetrexed 500mg/m² Q21d x4 → pemetrexed maintenance (11mo, PR -42%) → slow PD
  4. Phase I/II Trop-2 ADC trial (ongoing)
- **Resistance profiling**: ctDNA at PD1: EGFR C797S cis + MET amp (CN 8). ctDNA at PD2: not repeated (clinical decision)
- **RECIST imaging**: 16 CT chest/abd timepoints + 4 brain MRI timepoints, with target lesion measurements showing response→stability→progression per line
- **Labs tracked**: CEA (18.4→5.2→12.7→22.3→8.1→19.6), CBC with differential (G3 neutropenia on chemo), LFTs, renal function
- **Complications**: DVT on pemetrexed (started apixaban), G2 infusion reaction (amivantamab C1), G2 paronychia, G3 neutropenia requiring pegfilgrastim
- **Demo value**: Full precision oncology arc, 4 treatment lines with resistance mechanisms, brain met response tracking, clinical trial matching, ctDNA longitudinal

#### C2 — BRAF V600E MSS Colorectal Cancer
- **ICD-10**: C18.2
- **Demographics**: 54yo Black Female
- **Temporal depth**: 4 years (2022-2026), adjuvant + 3 metastatic lines
- **Stage**: Initially pT3 N2a M0 (Stage IIIB) → resected → metastatic recurrence at 11mo
- **Genomic profile**: BRAF V600E, PIK3CA E545K, APC R1450*, TP53 R175H, KRAS/NRAS WT, HER2 0, TMB 6.8, MSS, CIMP-high
- **Pathology**: Right hemicolectomy: adenocarcinoma with mucinous features (40%), G2-G3, LVI+, PNI+, 4/22 LN+, pMMR/MSS. Liver biopsy: confirmed metastatic CRC (CK20+, CDX2+, CK7-)
- **Treatment lines**:
  - Adjuvant: CAPOX x6mo (G2 peripheral neuropathy persistent, G2 HFS) → recurrence 4mo post-completion
  1. FOLFIRI + bevacizumab (8mo, PR -38%) → febrile neutropenia episode (hospitalized) → PD
  2. Encorafenib 300mg daily + cetuximab (BEACON regimen, 11mo, PR -38%) → PD
  3. Phase I/II encorafenib + cetuximab + nivolumab (6mo SD) → immune thyroiditis → PD → BSC
- **Resistance profiling**: ctDNA at PD2: KRAS G12D acquired + MAP2K1 K57N (MEK1 bypass resistance)
- **RECIST imaging**: 11 CT timepoints + 2 PET/CT, tracking 3 liver target lesions + peritoneal disease
- **Labs tracked**: CEA (8.4→2.1→34.7→11.2→48.3→14.6→72.1→145.8), CBC, LFTs (AST/ALT/ALP/LDH trending with liver burden), albumin decline
- **Complications**: Febrile neutropenia (FOLFIRI), port-associated subclavian DVT, immune thyroiditis (nivolumab), malignant ascites requiring paracentesis, persistent oxaliplatin neuropathy
- **Demo value**: Worst molecular subgroup (BRAF+MSS), CEA-imaging correlation, resistance bypass mechanism, declining trajectory to BSC

#### C3 — BRCA1 Triple-Negative Breast Cancer
- **ICD-10**: C50.912
- **Demographics**: 41yo South Asian Female
- **Temporal depth**: 5 years (2021-2026), neoadjuvant + adjuvant + 2 metastatic lines
- **Stage**: Initially cT2 N1 M0 (Stage IIB) → non-pCR → metastatic recurrence at 15mo
- **Genomic profile**: Germline BRCA1 c.5266dupC (p.Gln1756Profs*74), pathogenic. Somatic: biallelic BRCA1 loss (germline + LOH), TP53 Y220C, MYC amplification, HRD score 62, TMB 8.4, MSS
- **Pathology**: Core biopsy: invasive NST, Nottingham G3 (score 9), ER-/PR-/HER2 IHC 0, PD-L1 CPS 18, Ki-67 78%. Surgical path: ypT1c N1a, RCB-II
- **Treatment lines**:
  - Neoadjuvant: KEYNOTE-522 (pembrolizumab + paclitaxel/carboplatin → pembrolizumab + AC) → non-pCR (RCB-II)
  - Surgery: Left MRM + ALND
  - Adjuvant: Pembrolizumab x9 cycles (completing 1yr) → G2 immune colitis (prednisone taper)
  1. Olaparib 300mg BID (17mo, PR -78% near-CR) → PD with adrenal met
  2. Sacituzumab govitecan 10mg/kg (ongoing, PR -35%, dose reduced after febrile neutropenia)
- **Resistance profiling**: ctDNA at olaparib PD: BRCA1 reversion mutation (c.5264_5266del restoring reading frame) — classic PARP inhibitor resistance
- **RECIST imaging**: 4 breast MRI timepoints (neoadjuvant response), 7 CT chest/abd timepoints (metastatic), 1 PET/CT, 1 brain MRI (negative)
- **Labs tracked**: CA 15-3 (24→88→22→67→38), CBC (G3 neutropenia on AC and SG), LFTs
- **Complications**: Immune hypothyroidism (pembro, lifelong levothyroxine), immune colitis (G2, steroid taper), febrile neutropenia x2 (AC and SG), lymphedema (post-ALND), UGT1A1 *1/*28 heterozygous (SG metabolism)
- **Demo value**: Germline-somatic interplay, neoadjuvant response assessment (RCB), PARP inhibitor deep response then reversion resistance, ADC therapy, hereditary cancer management

### Category D: Undiagnosed (3 patients)

#### D1 — Erdheim-Chester Disease
- **ICD-10**: C96.1
- **Demographics**: 54yo African American Male
- **Temporal depth**: 2.5 years diagnostic odyssey
- **Hidden diagnosis**: Erdheim-Chester Disease — BRAF V600E-driven non-Langerhans histiocytosis
- **Diagnostic odyssey**: PCP (bone pain, weight loss, ESR 68) → Orthopedics (symmetric femoral/tibial sclerosis, bone biopsy: "nonspecific foamy histiocytes" — CD68+/CD1a- not stained initially) → Infectious Disease (3mo antibiotics for "osteomyelitis," no improvement) → Rheumatology (periorbital xanthelasma, polyuria, ANA/ANCA negative, IgG4 normal) → Endocrinology (water deprivation test confirms central DI, thickened pituitary stalk, low testosterone) → Pulmonology (interstitial lung disease, periaortic soft tissue — not typical sarcoidosis) → Nephrology (Cr 1.8, "hairy kidney," "coated aorta," bilateral hydronephrosis → ureteral stents for RPF) → Cardiology (pericardial effusion 1.8cm, RA infiltration on cardiac MRI) → Hematology (re-stain bone biopsy: CD68+/CD163+/CD1a-/S100-, BRAF V600E on tissue + cfDNA VAF 2.8%) → **Diagnosis: ECD**
- **Treatment**: Vemurafenib 960mg BID
- **Genomic findings**: BRAF V600E (somatic), initially detected on liquid biopsy, confirmed on tissue
- **Key missed clue**: Bone biopsy foamy histiocytes were CD68+/CD1a- (pathognomonic for ECD) but immunostaining was not performed until month 22
- **Demo value**: 6 specialist records that individually suggest common diagnoses (osteomyelitis, RPF, sarcoidosis, constrictive pericarditis) but collectively point to one rare disease; cross-specialty signal aggregation

#### D2 — VEXAS Syndrome
- **ICD-10**: D89.89
- **Demographics**: 67yo White Male
- **Temporal depth**: 3 years diagnostic odyssey
- **Hidden diagnosis**: VEXAS Syndrome — somatic UBA1 mutation causing systemic autoinflammation + hematologic dysfunction
- **Diagnostic odyssey**: PCP (fever, skin nodules, ear swelling, pancytopenia, macrocytic anemia MCV 106) → Rheumatology Visit 1 (auricular chondritis + proximal girdle pain → "PMR with possible RP overlap," prednisone, ferritin 680, IL-6 42) → Dermatology (skin biopsy: neutrophilic dermatosis → "Sweet syndrome") → Hematology (bone marrow: hypercellular 70%, **prominent cytoplasmic vacuoles** in myeloid + erythroid precursors, mild dysplasia, blasts <3% → "MDS unclassifiable." Vacuoles documented but attributed to artifact) → Rheumatology Visit 2 (bilateral auricular + nasal chondritis, sensorineural hearing loss → "relapsing polychondritis," add methotrexate) → Vascular (unprovoked DVT, hypercoag panel negative) → Ophthalmology (bilateral episcleritis + uveitis) → Pulmonology (progressive GGOs, neutrophilic BAL, FVC 72%) → Academic hematology 2nd opinion (re-reviews marrow → vacuoles are diagnostic → **UBA1 p.Met41Thr (c.122T>C), VAF 62%** → **Diagnosis: VEXAS**)
- **Genomic findings**: UBA1 p.Met41Thr (somatic), VAF 62%. Normal karyotype, no myeloid panel mutations
- **Key missed clue**: Bone marrow cytoplasmic vacuoles in myeloid and erythroid precursors (present in >80% of VEXAS, documented at month 6 but dismissed)
- **Demo value**: "Too many diagnoses" pattern — 4 simultaneous diagnoses (PMR + Sweet + MDS + RP) are actually one disease; demonstrates AI multi-diagnosis pattern flagging; disease discovered in 2020 (NEJM, Beck et al.) — tests recognition of emerging diseases

#### D3 — Autoimmune Polyendocrine Syndrome Type 1 (APS-1/APECED) [PEDIATRIC]
- **ICD-10**: E31.0
- **Demographics**: 8-11yo Hispanic Female
- **Temporal depth**: 3 years diagnostic odyssey
- **Hidden diagnosis**: APS-1/APECED — biallelic AIRE mutations causing defective central immune tolerance
- **Diagnostic odyssey**: Pediatrics (recurrent oral candidiasis x4/yr, alopecia areata, nail dystrophy, **calcium 8.2 — flagged as "likely artifact"**) → Dermatology (scalp biopsy: alopecia areata confirmed) → Pediatric Immunology (standard immunodeficiency workup all normal — anti-IL-17/IL-22 antibodies not tested) → ED (hypocalcemic seizure, Ca 6.8, PTH 4, QTc 502ms → PICU) → Pediatric Endocrinology (parathyroid antibodies positive, DiGeorge FISH normal → "isolated autoimmune hypoparathyroidism") → Pediatric Dentistry (enamel hypoplasia — ectodermal feature of APECED, dismissed as developmental) → Pediatric Rheumatology (bilateral knee effusions → "oligoarticular JIA") → Pediatric GI (AST 142, ALT 198, ASMA positive, IgG 1850, liver biopsy: interface hepatitis → "autoimmune hepatitis type 1" — GI notes "consider polyglandular syndrome" but no AIRE testing ordered) → Ophthalmology (keratoconjunctivitis sicca, Schirmer 4mm) → Endocrinology follow-up (hyperpigmentation, salt craving → AM cortisol 3.2, ACTH 280, 21-hydroxylase Ab positive → **Addison disease, completing classic triad: CMC + hypoparathyroidism + Addison = APS-1** → AIRE sequencing → **compound het: c.769C>T (p.Arg257Ter) / c.967_979del13 (p.Leu323fsX372)**)
- **Genomic findings**: AIRE compound heterozygous (both pathogenic). Anti-IFN-omega Ab >300 U/mL, anti-IL-17F Ab positive, anti-IL-22 Ab positive
- **Key missed clues**: (1) Initial low calcium at month 0 dismissed as artifact, (2) dental enamel hypoplasia not communicated to endocrinology, (3) 2 of 3 classic triad present by month 12 but in different charts
- **Demo value**: Most emotionally compelling — child sees 7 subspecialists, accumulates 5 autoimmune diagnoses; fragmented pediatric records; APS-1 triad detection; ectodermal finding tracker value

## Data Generation Strategy

### Implementation: Hand-Crafted PHP Seeder (Approach A)

```
backend/database/seeders/
  ClinicalDemoSeeder.php              # Orchestrator
  DemoPatients/
    RareDiseasePatient1_hATTR.php
    RareDiseasePatient2_TSC.php
    RareDiseasePatient3_CAPS.php
    PreSurgicalPatient1_CABG.php
    PreSurgicalPatient2_HIPEC.php
    PreSurgicalPatient3_VHL_HHT.php
    OncologyPatient1_LungEGFR.php
    OncologyPatient2_CRC_BRAF.php
    OncologyPatient3_TNBC_BRCA1.php
    UndiagnosedPatient1_ECD.php
    UndiagnosedPatient2_VEXAS.php
    UndiagnosedPatient3_APS1.php
```

### MRN Scheme
- All demo patients use MRN prefix `DEMO-` followed by category code:
  - `DEMO-RD-001` through `DEMO-RD-003` (rare disease)
  - `DEMO-PS-001` through `DEMO-PS-003` (pre-surgical)
  - `DEMO-ON-001` through `DEMO-ON-003` (oncology)
  - `DEMO-UD-001` through `DEMO-UD-003` (undiagnosed)

### Idempotency
- `ClinicalDemoSeeder` deletes all `clinical.patients` with MRN starting with `DEMO-` before seeding
- Cascading deletes handle all related records (conditions, medications, etc.)
- Safe to run repeatedly: `php artisan db:seed --class=ClinicalDemoSeeder`

### Synthetic Name Generation
- All names are synthetic and cannot match real patients
- Diverse first/last names matching stated demographics
- No celebrity or public figure names

### Date Anchoring
- All dates are relative to a configurable anchor date (default: `2026-03-15`)
- Oncology cases: 2021-2026 timeline
- Rare disease cases: variable (8-14 year arcs ending near anchor)
- Pre-surgical cases: weeks-to-months before anchor
- Undiagnosed cases: 2.5-3 year arcs ending near anchor

### Data Volume Summary
- **~2,370 total clinical records** across 12 patients
- **~1,330 measurements** (labs — the densest data type)
- **~230 visits**, **~130 clinical notes**, **~110 imaging studies**
- **~110 conditions**, **~110 medications**, **~55 procedures**
- **~35 genomic variants**, **~50 condition eras**, **~40 drug eras**

### Table Coverage Decisions

**Populated tables** (all patients):
- `clinical.patients`, `clinical.conditions`, `clinical.medications`, `clinical.procedures`, `clinical.measurements`, `clinical.observations`, `clinical.visits`, `clinical.clinical_notes`, `clinical.imaging_studies`, `clinical.genomic_variants`, `clinical.condition_eras`, `clinical.drug_eras`

**Populated with minimal records**:
- `clinical.imaging_series` — 1-2 synthetic series per imaging study (series_uid generated, modality matching parent study). Needed to maintain schema integrity.
- `clinical.patient_identifiers` — 1-2 identifiers per patient (e.g., synthetic insurance ID, facility MRN). Exercises the table without complexity.

**Intentionally NOT seeded** (computed/derived data):
- `clinical.imaging_instances` — represents individual DICOM slices; no actual DICOM files exist for demo data
- `clinical.imaging_segmentations` — AI-generated artifacts, not source data
- `clinical.patient_embeddings` — computed by the AI service, not seeded

### RECIST Measurement Mapping

Oncology RECIST 1.1 target lesion measurements (patients C1, C2, C3) go into `clinical.imaging_measurements` linked to `imaging_study_id`, NOT into the general `clinical.measurements` table. The `measurement_type` column should be set to `'RECIST'` with `target_lesion = true` for target lesions.

General lab values (CBC, CMP, tumor markers, etc.) go into `clinical.measurements`.

### Observations vs. Measurements Mapping

- **`clinical.measurements`**: Quantitative lab values with numeric results, units, and reference ranges (CBC, CMP, tumor markers, coagulation, etc.)
- **`clinical.observations`**: Qualitative or categorical clinical findings — risk scores (STS, EuroSCORE, MELD, Lee RCRI, ASA class, KPS, mRS, PCI score), physical exam findings (shagreen patch, livedo reticularis, periorbital xanthelasma), functional assessments, and disease staging (TNM, NYHA class, ECOG PS)

### Source Provenance Columns

All demo records use:
- `source_type = 'synthetic'`
- `source_id = 'demo_seeder_v1'`

This allows easy identification and filtering of demo data in queries.

### Domain Mapping

The `conditions.domain` column maps to patient categories:
- Category A (Rare Disease): `domain = 'rare_disease'`
- Category B (Pre-Surgical): `domain = 'surgical'`
- Category C (Oncology): `domain = 'oncology'`
- Category D (Undiagnosed): `domain = 'complex_medical'`

Comorbid conditions may use a different domain than the primary (e.g., a rare disease patient's concurrent CKD would be `domain = 'complex_medical'`).

### Clinical Accuracy Standards
- All ICD-10 codes verified against CMS ICD-10-CM 2026
- All drug names are real (generic), doses match FDA-approved labeling or published off-label evidence
- All lab values use standard units with correct reference ranges for age/sex
- All genomic variants use HGVS nomenclature with real gene names, chromosomal locations, and ClinVar-consistent classifications
- RECIST 1.1 measurements follow published response criteria thresholds
- Treatment sequences follow NCCN guidelines or published trial data
- Imaging modalities and findings match standard clinical practice for each disease

### Source References
- NCCN Guidelines (NSCLC, CRC, Breast Cancer — 2025-2026 versions)
- Landmark trials: FLAURA, BEACON, KEYNOTE-522, OlympiAD, ASCENT, MARIPOSA-2
- NEJM: Beck et al. 2020 (VEXAS/UBA1), hATTR diagnosis guidelines
- TSC International Consensus Guidelines (2021 update)
- CAPS Registry (Asherson criteria)
- WHO 5th Edition Classification of Hematolymphoid Tumors (ECD)
- GeneReviews (TSC, VEXAS, APS-1/APECED)
