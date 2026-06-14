# Aurora — Strategy for Complex-Care MDT Leadership

**Date:** 2026-06-14
**Author:** Claude Code / Dr. Sanjay Udoshi
**Status:** Strategy approved in principle; lead initiative ready for planning
**Scope:** How to make Aurora the most advanced, useful platform for complex care coordination and decision-making by care teams for four populations — cancer, advanced/complex surgical, advanced/complex medical (multimorbidity), and rare/undiagnosed disease.

> **Steering decisions (2026-06-14):**
> 1. **Lead non-oncology vertical:** Rare / undiagnosed disease (diagnostic odyssey).
> 2. **6-month emphasis:** AI + evidence differentiation.
> 3. **Posture:** Standards + RWE moat from the start.
>
> These three choices are mutually reinforcing: rare disease *is* the federation story (Matchmaker Exchange), the agentic-AI story (automated reanalysis loop), and the standards story (Phenopackets v2, VRS, Beacon, FHIR Genomics) — all built on assets Aurora already has.

---

## 1. Executive thesis

Aurora is already a genuinely *multimodal* MDT platform — structured decision capture with voting/dissent/concordance, a live session engine with view-sync, DICOM volumetrics, genomics (ClinVar/OncoKB), a molecular-genomic-volumetric **fingerprint + "Patients Like This"** engine, an agentic AI (Abby) with PHI sanitization and tool/DAG execution, scaffolded federation, and a clean OMOP/FHIR adapter layer. The v2 design already *names* all four populations.

But **depth is ~90% oncology**. The strategic opportunity, confirmed by deep market research, is that **the non-oncology MDT space is almost entirely uncontested, and there is no maintained open-source MDT collaboration platform of any kind.** Every mature competitor (Roche navify, OncoLens, Caris, Tempus, Epic Beacon) is oncology-locked. Cardiac Heart Team/TAVR (CMS-mandated), ILD boards (ATS/ERS-mandated), transplant selection committees, rare-disease germline boards, and complex-medical huddles still run on **email, Word, and PowerPoint**. The GitHub topic `molecular-tumor-board` has *zero* repos. Microsoft's multi-agent "tumor board" (Healthcare Agent Orchestrator) is an MIT *sample*, not a product.

The thesis is therefore **not** "add three verticals." It is:

> **Build a horizontal "MDT operating system" — a longitudinal patient track plus a configurable board-template engine — and ship population packs on top of it.** One codebase becomes best-in-class for all four populations instead of four mediocre verticals.

---

## 2. The unifying abstraction

The four populations look different but share one spine; what differs is the *time model* of the decision:

| Population | Time model | What Aurora's current "case" lacks |
|---|---|---|
| **Cancer** | Episodic (diagnosis → staged plan) | (current model — adequate) |
| **Complex surgical** | Episode-of-care (decide → optimize → operate → recover) | candidacy rubric + multi-clearance gating + episode timeline |
| **Complex medical** | Longitudinal, recurring | persistent problem list, recurring review cycles, goals-of-care axis |
| **Rare / undiagnosed** | Diagnostic-odyssey state machine **with a reanalysis loop** | explicit case state machine + asynchronous reanalysis |

The keystone work is to **generalize `ClinicalCase` into (a) a longitudinal *patient track* and (b) a *board-template system*** — each board type carrying its own structured data schema, candidacy rubric, decision schema, and agenda. Everything else (decisions, risk, imaging, genomics, AI, interop) becomes a shared horizontal service each pack specializes.

---

## 3. The seven cross-cutting platform moves (horizontal core)

### A. Longitudinal track + configurable board engine *(keystone)*
Generalize `ClinicalCase` → *patient track* (persistent problem list, longitudinal timeline, recurring review cycles) + *board-template system* (per-board data model + candidacy rubric + decision schema). Add explicit state machines: surgical episode-of-care, and the rare-disease diagnostic odyssey (referral → deep phenotyping → testing → MDT → matchmaking → diagnosis → **reanalysis**). Unblocks all three new packs.

### B. Decision intelligence layer *(Aurora's signature)*
Every incumbent documents decisions *narratively*; none expose a queryable, guideline-linked decision dataset. Make Aurora's structured decision record + **closed-loop task engine** (FHIR `Task`, owner, due-date, explicit "close-the-loop" states for referrals/pending results) the defining artifact. It doubles as the RWE asset.

### C. OMOP-native risk & cohort engine
Auto-compute validated, population-specific scores at case creation with zero manual entry, versioned as `measurement` rows so trends are visible:
- *Surgical:* RCRI, mFI-5, ARISCAT, Hospital Frailty Risk Score (all FHIR/OMOP-computable) + CT-derived sarcopenia/body composition and future-liver-remnant from existing segmentation.
- *Medical:* Charlson/Elixhauser, electronic Frailty Index, LACE/HOSPITAL, MELD-Na/KFRE/MAGGIC; **care-gap engine** as CQL/FHIR `Measure`; rising-risk panels for high-utilizer huddles.
- *Rare:* diagnostic-yield tracking (benchmark: UDN ~35% solved; +5–15% from systematic reanalysis).

### D. Imaging → decision
Extend Cornerstone3D from tumor volumetrics to **surgical planning** (vessel-contact/resectability arcs, FLR, implant/annulus sizing) and persist AI results as standards objects (**DICOM SR TID 1500 / SEG / RTSTRUCT**, FHIR `ImagingStudy`) over **DICOMweb** (QIDO/WADO/STOW-RS).

### E. Genomics → decision (the reanalysis loop)
Add **GA4GH VRS 2.0** variant canonicalization + ClinGen Allele Registry **CAID** as variant primary key; an **ACMG/AMP Tavtigian points engine** with ClinGen gene-specific (CSpec) criteria; emit **FHIR Genomics Reporting IG (STU3)**. Build the **automated periodic reanalysis pipeline with knowledge-change alerting** — when a gene-disease assertion or ClinVar classification changes, diff against the patient's last classification and auto-raise an MDT review task. Highest-ROI, lowest-competition feature found; valuable for rare disease *and* re-flagging oncology variants.

### F. Abby as a productized agentic MDT
The AI frontier nobody has shipped commercially (see §7).

### G. Interoperability spine
SMART on FHIR app launch + **CDS Hooks 2.0** (surface Aurora's trial matches, guideline concordance, risk scores, deprescribing alerts inside Epic/Cerner); **mCODE STU4** (oncology), **Phenopackets v2** (rare), **FHIR CarePlan/Goal/CareTeam + Gravity SDOH IG** (medical); **Bulk Data $export**, US Core, terminology services, TEFCA participation via a connectivity QHIN (see §8).

---

## 4. The four population packs

| Pack | Signature additions beyond the core | Beachhead board |
|---|---|---|
| **Oncology** *(mature)* | mCODE mapping, reanalysis loop, agentic board, ambient decision capture | already live |
| **Rare disease** *(LEAD)* | Diagnostic-odyssey state machine; HPO deep phenotyping + Phenopackets v2; ACMG points engine + **reanalysis loop**; **Matchmaker Exchange node** (reuses federation/similarity!); Exomiser/Phen2Gene prioritization; Beacon v2 endpoint; ERN/CPMS-style virtual MDT | undiagnosed-disease or unified germline+somatic board |
| **Complex surgical** | Heart Team-style candidacy boards + vote; risk engine (RCRI/mFI-5/HFRS/sarcopenia/FLR); prehab/ERAS pathway (EIAS-aligned); **Best Case/Worst Case** SDM; episode-of-care timeline; Clavien-Dindo + Comprehensive Complication Index | **Cardiac Heart Team / TAVR** (CMS-mandated, zero incumbent) |
| **Complex medical** | Longitudinal tracks; **deprescribing engine** (STOPP/START v3, Beers 2023, anticholinergic burden) extending the DDI checker; **goals-of-care / Serious Illness Conversation** + POLST/PACIO ADI; care-gap + rising-risk panels; SDOH closed-loop referrals; transitions-of-care (I-PASS) | high-utilizer huddle / transplant selection committee |

**Why rare disease leads:** the European Reference Network reference tool (CPMS) has *no* imaging and *no* genomics integration — exactly Aurora's strengths — and Aurora's federation/similarity engine *is* conceptually a Matchmaker Exchange node. UDN/GREGoR/Genomics England/Solve-RD have converged on a standard workflow Aurora can model directly.

---

## 5. Lead initiative — Rare-Disease Diagnostic Odyssey + Agentic Reanalysis + Standards/Federation

This is the first sub-project to plan and build. It exercises the keystone core (state machine, decision/task engine), the AI emphasis (agentic reanalysis), and the standards/RWE posture (Phenopackets/VRS/Beacon/MME) simultaneously.

### 5.1 Diagnostic-odyssey case state machine
Model the case as a first-class state machine mirroring UDN/GREGoR/Genomics England/Solve-RD:
`referral & eligibility → deep phenotyping → multi-omic testing → bioinformatic prioritization → MDT case review → functional validation & matchmaking → diagnosis/report/return → periodic reanalysis (loop)`.
Adopt a GREGoR-style data model (participant/family/analyte/experiment/aligned/called-variant) so cross-site reanalysis and yield-tracking are tractable. Track `progressStatus` (IN_PROGRESS / SOLVED / UNSOLVED) per Phenopackets.

### 5.2 Deep phenotyping (HPO + Phenopackets v2)
HPO capture with onset/severity/frequency modifiers and **explicit excluded phenotypes** (silence ≠ absence); autocomplete via `ontology.jax.org/api/`. Adopt **GA4GH Phenopackets v2 (ISO 4454:2022)** as the canonical case-interchange format (import/export, validate with phenopacket-tools) for instant interoperability with UDN/GREGoR/Solve-RD/seqr.

### 5.3 Variant interpretation + the reanalysis loop *(the differentiator)*
- **VRS 2.0** canonicalization in the Python AI layer + ClinGen Allele Registry CAID as the internal variant primary key (dedupes variants across VCF/HGVS/Beacon; prerequisite for reanalysis and ClinVar round-tripping).
- **ACMG/AMP Tavtigian points engine** with ClinGen CSpec gene-specific criteria, SpliceAI/CNV support, AutoPVS1.
- **Automated periodic reanalysis** with diff-against-last-classification and auto-generated MDT review tasks on tier change. Triggers: new gene-disease validity assertion, ClinVar/CAR reclassification, updated HPO, new segregation, 12–18-month cadence.
- **Knowledge-base change alerting:** subscribe each unsolved patient to ClinGen validity changes and ClinVar/CAR reclassifications.

### 5.4 Matchmaking & federation tie-in
Implement the GA4GH **Matchmaker Exchange** `/match` contract natively in Laravel (or deploy a `matchbox`/PatientMatcher sidecar) so Aurora is both an MME **client** (query GeneMatcher, PhenomeCentral, DECIPHER, MyGene2) and an answering **node** — directly reusing the existing federation/similarity engine. Patient-similarity on `semsimian`/`hpo3` (Resnik MICA, symmetric best-match-average / simGIC), the same model behind PhenomeCentral and MME.

### 5.5 Standards exposure
**Beacon v2.2** endpoint over Aurora cohorts (tiered boolean→count→record) for privacy-preserving discovery; **FHIR Genomics Reporting** emit/ingest; **Phenopackets v2** export. Ontologies: HPO + ORDO/ORPHA + MONDO + OMIM throughout.

### 5.6 Phenotype-driven prioritization
Wrap **Phen2Gene** (MIT, HPO-only — easy phenotype-first widget) and process-isolate **Exomiser** v15 (VCF + HPO; gold-standard hiPHIVE) to surface candidate genes pre-MDT.

---

## 6. AI architecture (Abby) — agentic MDT

### 6.1 Multi-agent board
Role agents (radiology, pathology, staging/candidacy, guidelines, trials, history; for rare disease: phenotype, variant-curation, matchmaking, reanalysis) auto-assemble the case packet and flag missing data. Architectures are validated — Microsoft HAO (Stanford/JHU/MGB/Providence/UW, ~10× prep reduction), MDAgents (adaptive routing), MAI-DxO (~80% on NEJM CPCs, ~4× generalist physicians) — but only Microsoft has a public *sample*. Add a **dissent agent** (cf. "Catfish Agent") to counter agreement bias.

### 6.2 Adopt BioMCP as Abby's biomedical tool layer
**BioMCP** (MIT, GenomOncology, actively maintained) already unifies ClinicalTrials.gov API v2 + NCI CTS, PubMed/PubTator3, MyVariant/ClinVar/gnomAD/CIViC/OncoKB, and cBioPortal. Reuse rather than rebuild; signals standards-citizenship while Aurora owns the decision/collaboration layer.

### 6.3 Ambient capture → structured decision
Self-hosted **Whisper large-v3 / NVIDIA Parakeet** + **pyannote** diarization (audio stays in-enterprise) turns the live discussion into a structured, guideline-linked decision record, session note, referral letter, and draft orders. Design for **structured decision capture** (the differentiator) — the JAMA 2026 multi-site study shows ambient scribes give only modest time savings on notes alone. Prompt-directed ambient capture markedly improves MDT decision-record quality.

### 6.4 Model strategy (open-first; cloud for hardest reasoning)
| Layer | Open backbone (local-first) | Cloud (reserve for hardest) |
|---|---|---|
| Clinical text | MedGemma 27B / MedGemma 1.5; OpenBioLLM-70B; Meditron (Apache-2.0) | GPT-5, Claude Opus 4.x, Gemini 2.5/3 |
| Pathology | Prov-GigaPath (open), UNI2-h, H-optimus, Virchow2 (NC) | Paige Alba |
| Radiology | Merlin (MIT, 3D CT), CT-FM (MIT) | Azure CT Foundation |
| Genomics | Evo 2 (Apache-2.0, 1M-bp ctx), AlphaMissense | — |
| Multimodal onco | MUSK, THREADS (histology+genomics, treatment-response) | — |

Watch licenses (several pathology/radiology FMs are research/non-commercial). Benchmark on **HealthBench / MedHELM**, not saturated MedQA.

### 6.5 Trustworthy AI (mandatory, see also §10)
Citation-grounded answering with linked evidence (SourceCheckup found 50–90% of LLM answers aren't fully supported by their own citations; guideline-grounded RAG reaches ~99.5% faithfulness); conformal-prediction abstention; existing `can_use_tool` human-approval gating; read-only-by-default tools; equity auditing (EquityMedQA, demographic subgroup performance). Note the NEJM AI 2026 RCT: AI-literacy training did *not* prevent automation bias — so design the UI to force review of the *basis*, not just the recommendation.

---

## 7. Standards & interoperability spine (posture: standards-first)

- **FHIR R4 baseline** (US regulatory standard; US Core skips R5, going R4→R6) with a thin version-router. Conform adapters to US Core profiles.
- **SMART App Launch 2.2** (EHR + standalone), scopes v2, **SMART Backend Services** for unattended pulls; **SMART Health Links** for patient-mediated case packets.
- **CDS Hooks 2.0** service (`patient-view`, `order-select`, `order-sign`) returning cards (trial match, guideline concordance, risk, deprescribing) + `type:"smart"` app-link to deep-launch Aurora. Lead with Epic (Cerner CDS Hooks not yet GA).
- **Bulk Data $export** client for cohort/population ingestion into OMOP + pgvector.
- **mCODE STU4** for oncology cases (unlocks CodeX trial matching, NAACCR/SEER registry export, ICAREdata RWE). For rare disease, anchor on Phenopackets v2 + IPS; for medical, FHIR CarePlan/Goal/CareTeam + **Gravity SDOH IG** (PRAPARE/AHC screens, Z-codes, closed-loop referrals).
- **Genomics:** VRS 2.0 + VA-Spec/Cat-VRS, ClinGen Allele Registry, FHIR Genomics Reporting, Beacon v2.2.
- **Imaging:** DICOMweb; AI results as DICOM SR TID 1500 / SEG / RTSTRUCT; FHIR `ImagingStudy`; IHE IID for EHR→viewer launch.
- **Terminology:** SNOMED CT, LOINC 2.80, RxNorm, ICD-10-CM 2026 / ICD-O-3.2, HPO/ORDO/MONDO/NCIt; FHIR terminology ops; VSAC; map all to OMOP standard concepts via Athena. Stay on **OMOP CDM v5.4**.
- **Networks:** join **TEFCA** as a Participant under a connectivity QHIN (Health Gorilla / CommonWell); XCPD/XCA now, QHIN-FHIR later.

---

## 8. Federation & RWE model (posture: RWE moat now)

The MDT is the highest-value RWE capture funnel (OncoLens ORN, Tempus Lens, Flatiron). Aurora's opt-in, OMOP-native, PHI-never-leaves federation is a governance-friendly version:
- Federated **"Patients Like This"** (embedding broadcast, mTLS, aggregate-only return) — already designed.
- **Matchmaker Exchange** node for rare disease (de-identified HPO + genomic features).
- **Beacon v2** for variant discovery.
- An OMOP RWE network behind opt-in consent as a sustainability/revenue line.
- GTM: explicitly target health systems orphaned by **Syapse's Dec-2024 collapse**.

---

## 9. Evidence & trust program (emphasis: evidence; posture: trust)

1. **Publish a peer-reviewed prep-time/cost + decision-quality study within ~12 months.** navify's 2022 Ellis Fischel cost study is the buying bar and is stale; fresh, *open* evidence flips the script. Report per **TRIPOD+AI / TRIPOD-LLM / DECIDE-AI / FUTURE-AI**.
2. **Pursue a 510(k)-cleared derivative of the OHIF/Cornerstone3D viewer path** (precedent: OHIF → Radical Imaging FlexView, K233226). Keep the OSS core a non-device CDS; clear the derivative.
3. **Stay inside FDA non-device CDS criteria** (the four §520(o)(1)(E) tests): don't auto-analyze images/signals to drive the decision, surface the *basis/evidence per recommendation*, output recommendations not directives, and let the clinician independently review. Plan a **PCCP** for any shipped models; plan for **EU AI Act** high-risk obligations (medical-device high-risk applies 2 Aug 2027).
4. **Model governance:** Model Cards / Sendak "Model Facts" labels, CHAI Assurance; mandatory **calibration + drift monitoring** (not just AUROC) with external/temporal validation.
5. **Neutral OSS governance** (Apache 2.0 + steering committee) — KLAS/Gartner won't list community projects; win on license + governance + validation.

---

## 10. Phased roadmap (reflecting the three steering decisions)

- **Phase A — Generalize the core:** longitudinal patient track + board-template engine + decision/task engine + risk engine + interop spine foundations (FHIR/US Core, SMART, CDS Hooks scaffold). *Everything depends on this.*
- **Phase B — Rare-disease lead initiative (§5):** diagnostic-odyssey state machine, Phenopackets v2, VRS/CAID + ACMG engine, **reanalysis loop + KB alerting**, Matchmaker Exchange node, Beacon endpoint. *Standards-first by construction.*
- **Phase C — AI productization (§6):** agentic board (rare-disease + oncology role agents), BioMCP integration, ambient → structured decision capture, trustworthy-AI guardrails.
- **Phase D — Evidence & RWE (§8–9):** federated PLT/MME, OMOP RWE network, the peer-reviewed study, 510(k) derivative path, OSS governance.
- **Later packs:** complex surgical (Heart Team/TAVR beachhead), complex medical (multimorbidity), each reusing the Phase-A core.

---

## 11. Risks & open questions

- **Scope/velocity:** the horizontal core is large; risk of slow time-to-demo. Mitigation: Phase B delivers a clinically credible rare-disease slice on the core.
- **License hygiene:** several pathology/radiology FMs are research/non-commercial; clinical use requires local validation and license review.
- **Regulatory line:** as soon as AI *drives* (not supports) decisions, device status attaches — design to stay non-device CDS unless a cleared derivative is intended.
- **Data access:** MME/Beacon participation and the OMOP RWE network require consent/IRB and institutional federation agreements.
- **Open:** which rare-disease design partner / network (UDN-affiliated, ERN, NHS GMS, or institutional) anchors the validation study?

---

## 12. Key research sources (by domain)

- **Competitive:** Roche navify Clinical Hub + Foundation Medicine integration (May 2026), OncoLens (Series B Oct 2024, >225 centers), Caris (IPO Jun 2025), Tempus (FY25 $1.27B), Microsoft Healthcare Agent Orchestrator (arXiv:2509.06602), empty `molecular-tumor-board` GitHub topic, Syapse collapse (Dec 2024).
- **Rare disease:** NIH UDN (PMID 30304647), GREGoR data model, Genomics England 100KGP (PMID 34758253), Solve-RD; Phenopackets v2 (PMID 35705716, ISO 4454:2022); HPO (PMID 37953324); ACMG/AMP (PMID 25741868) + Tavtigian points (humu.24088); Matchmaker Exchange (PMID 26295439, 26255989); matchbox (PMID 30240502); reanalysis yield (npj Genom Med 2020/2024).
- **Surgical:** ACS NSQIP, RCRI, mFI-5, Hospital Frailty Risk Score (PMID 27885969); Heart Team/TAVR (PMID 34156404); ERAS Society/EIAS; Best Case/Worst Case (PMID 28062349); Clavien-Dindo/CCI (PMID 23728278).
- **Medical:** NICE NG56 multimorbidity; STOPP/START v3 (PMID 37256475); Beers 2023; anticholinergic burden (PMID 35994403); Serious Illness Conversation (PMID 35802350); Patient Priorities Care (PMID 30357955); Gravity SDOH IG; PACIO ADI.
- **Standards:** FHIR R4/US Core, SMART App Launch 2.2, Bulk Data v2, CDS Hooks 2.0, mCODE STU4, GA4GH VRS 2.0 / Phenopackets v2 / Beacon v2.2, DICOMweb, TEFCA.
- **AI/models:** MedGemma 1.5 (arXiv:2507.05201), MAIRA-2/Rad-DINO, Prov-GigaPath (Nature 2024), UNI2/CONCH/TITAN, Merlin/CT-FM, Evo 2 / AlphaMissense, MUSK (Nature 2025) / THREADS; BioMCP; Dragon Copilot / Abridge; Whisper / Parakeet / pyannote.
- **Trust/regulatory:** FDA CDS final guidance (2022, §520(o)(1)(E)); FDA AI lifecycle draft (Jan 2025); FDA PCCP final (Dec 2024); EU AI Act 2024/1689; SourceCheckup (Nat Commun 2025); TRIPOD+AI (BMJ 2024) / TRIPOD-LLM / FUTURE-AI; EquityMedQA (Nat Med 2024); CHAI; calibration (BMC Med 2019); drift (NEJM 2021).
