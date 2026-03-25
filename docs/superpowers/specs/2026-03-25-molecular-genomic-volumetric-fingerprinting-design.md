# Molecular-Genomic-Volumetric Fingerprinting

**Date:** 2026-03-25
**Status:** Approved
**Author:** Claude + Human

## Vision

A system that enables clinicians to find patients similar to the one they are currently treating — across molecular, genomic, and volumetric dimensions — see which similar patients had the best outcomes and why, and use that intelligence to bend their patient's trajectory toward the best possible result.

No single software system enables this today. Aurora will be the first.

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Architecture | Dimensional Fingerprint (Approach B) | Transparent per-dimension similarity; clinician weight overrides are query-time, no re-embedding; missing data is first-class |
| Similarity weights | Learned defaults + clinician overrides | ML learns what predicts good outcomes (D); clinicians override based on context (C) |
| Outcome definition | Composite trajectory + clinician assessment | Computed sub-scores from existing data + expert judgment overlay |
| V1 surface | Patient profile tab only | Build engine right, prove UX in one place; tumor board and research views come later |
| Data strategy | 20 golden cohort patients + targeted public enrichment | Quality over quantity; full multi-modal density over sparse thousands |

## 1. Core Engine Architecture

### Dimensional Fingerprint

Each patient gets a fingerprint composed of three independent embedding vectors, each produced by a specialized encoder:

**Genomic Encoder → 256-dim vector**
- Captures the molecular identity of the patient's disease
- Data sources:
  - `clinical.genomic_variants`: `gene`, `variant`, `variant_type`, `allele_frequency`, `clinical_significance`, `zygosity`, `actionability`
  - `clinical.gene_drug_interactions`: `gene`, `variant_pattern`, `drug`, `relationship`, `evidence_level`
  - `clinical.observations` WHERE `category` = 'genomic': TMB score and MSI status (stored as observation values)
  - `clinical.clinvar_variants`: `clinical_significance`, `is_pathogenic` (joined via gene_symbol + position)

**Volumetric Encoder → 256-dim vector**
- Captures disease burden and spatial characteristics
- Data sources:
  - `clinical.imaging_studies`: `modality`, `body_part`, `study_date`
  - `clinical.imaging_measurements`: `measurement_type`, `value_numeric`, `unit`, `target_lesion`, `measured_at`
  - `clinical.imaging_segmentations`: `volume_mm3`, `label`, `algorithm`
  - Derived: volume change rate across timepoints, lesion count per study, total tumor burden

**Clinical Encoder → 256-dim vector**
- Captures treatment history and clinical trajectory
- Data sources:
  - `clinical.conditions`: `concept_name`, `concept_code`, `domain`, `status`, `severity`
  - `clinical.medications`: `drug_name`, `dose_value`, `dose_unit`, `frequency`, `status`, `start_date`, `end_date`
  - `clinical.drug_eras`: `drug_name`, `era_start`, `era_end`, `gap_days`
  - `clinical.measurements`: `measurement_name`, `value_numeric`, `unit`, `measured_at`, `reference_range_low`, `reference_range_high`
  - `clinical.procedures`: `procedure_name`, `performed_date`, `body_site`
  - `clinical.visits`: `visit_type`, `admission_date`, `discharge_date`
  - `clinical.condition_eras`: `condition_name`, `era_start`, `era_end`, `occurrence_count`

### Fingerprint Storage

Each patient's fingerprint is stored as:
- Three nullable pgvector(256) columns — a new `patient_fingerprints` table separate from the existing `patient_embeddings` table
- A `dimension_mask` boolean array indicating which dimensions have data
- Per-dimension `confidence` scores (0.0-1.0) reflecting data quality/completeness
- Per-dimension encoding timestamps for staleness tracking

### Migration & Coexistence

The existing `clinical.patient_embeddings` table (single 768-dim vector from SapBERT/Ollama text embedding) and the existing `/similarity/search` endpoint in the AI service are **deprecated** by this feature. They will be kept in the codebase during V1 development but not exposed in the UI. Once the fingerprint system is validated, the old table and endpoint will be removed. The new `patient_fingerprints` table is a fundamentally different representation (3 specialized 256-dim vectors vs 1 general-purpose 768-dim vector) and is not a migration of the old data.

### Similarity Fusion Layer

At query time:
1. Compute cosine similarity per dimension between query patient and each candidate
2. Apply weights — either learned defaults or clinician overrides
3. Mask missing dimensions and renormalize remaining weights (e.g., if volumetric missing for either patient, redistribute its weight proportionally)
4. Output: composite score + per-dimension breakdown + confidence level

Key property: **weights are applied at search time, not encoding time.** Clinician overrides are instant — no re-computation needed.

### Missing Data Strategy

- Missing dimension = excluded from score, not zero-filled (prevents false matches)
- Confidence is adjusted downward when fewer dimensions participate
- A patient with only genomic data still gets matched on genomics; the UI clearly shows "matched on 1/3 dimensions"
- Three tiers: Full Fingerprint (3/3), Partial (1-2/3), Minimal (1/3)

## 2. Outcome Scoring & Trajectory Comparison

### Computed Trajectory Score

Automatically derived from existing clinical data. Five sub-scores, each 0.0-1.0:

| Sub-Score | Weight | Data Sources | Formula |
|-----------|--------|-------------|---------|
| Tumor Response | 0.30 | `imaging_measurements` (RECIST type), `imaging_segmentations` (volume_mm3) | Best RECIST response: CR=1.0, PR=0.75, SD=0.5, PD=0.0. If volumetric data available, adjust by volume change %: >30% reduction adds +0.1, >20% growth subtracts -0.1. Clamp to [0, 1]. |
| Treatment Tolerance | 0.20 | `drug_eras` (era_start, era_end), `medications` (status) | `actual_era_days / median_era_days_for_drug`. Median era length derived from the cohort's own data for each drug. Status=completed → 1.0, status=discontinued → min(ratio, 0.5). Clamp to [0, 1]. |
| Lab Trajectory | 0.20 | `measurements` (value_numeric, measured_at, reference_range_low/high) | For key tumor markers (PSA, CEA, CA-125, AFP, LDH per cancer type): compute linear regression slope over sequential values. Score = 1.0 if trending into normal range, 0.5 if stable, 0.0 if trending away. Average across available markers. |
| Disease Stability | 0.15 | `condition_eras` (era_start, era_end, occurrence_count), `conditions` (status) | `days_since_last_new_condition / total_observation_days`. No new conditions in observation window = 1.0. Each new condition or status change to "active" reduces score proportionally. |
| Care Intensity | 0.15 | `visits` (visit_type, admission_date, discharge_date) | Score = 1.0 - normalized_intensity. Intensity = (emergency_visits × 3 + inpatient_days × 2 + outpatient_visits × 0.5) / observation_months. Normalize against cohort median. Clamp to [0, 1]. |

Composite = weighted sum of available sub-scores. If a sub-score cannot be computed (missing data), exclude it and renormalize remaining weights proportionally.

### Clinician Assessment

Expert judgment overlay with structured fields:
- **Overall Rating**: enum (excellent | good | mixed | poor | failure)
- **Key Factors**: free-text narrative explaining the outcome
- **Decision Point Tags**: structured tags from a curated set (drug-switch, dose-reduction, surgical-candidate, immunotherapy-ae, palliative-transition, complete-response, etc.) plus custom tags
- **Hindsight Note**: optional retrospective insight ("in retrospect, we should have...")

### Trajectory Profile

The combined output for each patient:
- `computed_score` (0.0-1.0) with sub-score breakdown
- `clinician_rating` (enum) with factors, tags, and hindsight
- `agreement` indicator (computed vs clinician alignment)

### Feedback Loop

As clinician annotations accumulate:
1. Compare computed scores against clinician ratings
2. Adjust outcome sub-score weights to minimize divergence
3. Adjust similarity fusion weights based on which dimensions best predict clinician-validated good outcomes
4. Minimum threshold: 50+ annotated patients before weight learning activates
5. Until then, use hand-tuned domain-expert defaults

## 3. Data Model

All tables in the `clinical` schema.

### clinical.patient_fingerprints

```
id                      bigint PK
patient_id              bigint FK → clinical.patients (UNIQUE)
genomic_vector          vector(256) NULLABLE
volumetric_vector       vector(256) NULLABLE
clinical_vector         vector(256) NULLABLE
dimension_mask          boolean[3]    -- [genomic, volumetric, clinical]
genomic_confidence      decimal(5,4) NULLABLE
volumetric_confidence   decimal(5,4) NULLABLE
clinical_confidence     decimal(5,4) NULLABLE
encoder_version         varchar(32)   -- e.g. "v1.0"
genomic_encoded_at      timestamp NULLABLE
volumetric_encoded_at   timestamp NULLABLE
clinical_encoded_at     timestamp NULLABLE
created_at              timestamp
updated_at              timestamp
```

### clinical.outcome_trajectories

```
id                          bigint PK
patient_id                  bigint FK → clinical.patients (UNIQUE)
tumor_response_score        decimal(5,4) NULLABLE
treatment_tolerance_score   decimal(5,4) NULLABLE
lab_trajectory_score        decimal(5,4) NULLABLE
disease_stability_score     decimal(5,4) NULLABLE
care_intensity_score        decimal(5,4) NULLABLE
composite_score             decimal(5,4) NULLABLE
clinician_rating            enum (excellent|good|mixed|poor|failure) NULLABLE
clinician_factors           text NULLABLE
decision_tags               jsonb NULLABLE   -- ["drug-switch", "immunotherapy-ae"]
hindsight_note              text NULLABLE
assessed_by                 bigint FK → app.users NULLABLE
assessed_at                 timestamp NULLABLE
computed_at                 timestamp
created_at                  timestamp
updated_at                  timestamp
```

### clinical.similarity_searches

```
id                  bigint PK
query_patient_id    bigint FK → clinical.patients
searched_by         bigint FK → app.users
weights_used        jsonb       -- {genomic: 0.4, volumetric: 0.3, clinical: 0.3}
weights_customized  boolean     -- clinician override vs default
context             enum (point_of_care|tumor_board|research)
result_patient_ids  jsonb       -- ordered array of matched patient IDs
result_scores       jsonb       -- [{composite, genomic, volumetric, clinical}]
result_count        integer
created_at          timestamp
```

### clinical.fusion_weight_configs

```
id                  bigint PK
name                varchar         -- "default", "genomics-heavy", "learned-v1"
config_type         enum (preset|learned|custom)
genomic_weight      decimal(5,4)
volumetric_weight   decimal(5,4)
clinical_weight     decimal(5,4)
outcome_weights     jsonb           -- {tumor_response: 0.3, tolerance: 0.2, ...}
is_active           boolean         -- only one active "default" at a time
trained_on_count    integer NULLABLE
created_at          timestamp
updated_at          timestamp
```

## 4. API Design

### Laravel Backend (PHP)

**Similarity Search:**
- `POST /api/fingerprint/search` — find similar patients (query patient ID + optional weight overrides)

**Fingerprint Management:**
- `GET /api/fingerprint/patients/{id}` — get patient fingerprint + metadata
- `POST /api/fingerprint/patients/{id}/encode` — trigger (re-)encoding for a patient
- `POST /api/fingerprint/encode-batch` — batch encode multiple patients

**Outcome Trajectories:**
- `GET /api/fingerprint/patients/{id}/outcome` — get computed + clinician outcome
- `PUT /api/fingerprint/patients/{id}/outcome/assess` — submit clinician assessment

**Weight Configuration:**
- `GET /api/fingerprint/weights` — list weight presets
- `GET /api/fingerprint/weights/active` — get current active default weights

**Stats:**
- `GET /api/fingerprint/stats` — fingerprinted count, coverage by dimension, outcomes annotated

### Python AI Service (FastAPI)

Routes registered under the `/api/ai/fingerprint` prefix in FastAPI (following existing router pattern). Laravel calls these internally; they are not exposed directly to the frontend.

**Encoding:**
- `POST /api/ai/fingerprint/encode/genomic` — encode variant profile → 256-dim vector
- `POST /api/ai/fingerprint/encode/volumetric` — encode imaging data → 256-dim vector
- `POST /api/ai/fingerprint/encode/clinical` — encode clinical trajectory → 256-dim vector

**Outcome Computation:**
- `POST /api/ai/fingerprint/outcome/compute` — compute trajectory sub-scores from raw data

**Weight Learning:**
- `POST /api/ai/fingerprint/weights/learn` — train fusion weights from annotated outcomes

**Explanation:**
- `POST /api/ai/fingerprint/explain` — generate natural language similarity explanation

**Golden Cohort:**
- `POST /api/ai/fingerprint/synthetic/generate` — generate synthetic patient with full multi-modal data
- `GET /api/ai/fingerprint/synthetic/templates` — list available synthetic patient archetypes

### Service Responsibility Split

**Laravel owns:** data storage/retrieval, pgvector similarity queries, clinician assessment CRUD, weight preset management, search audit logging, auth/RBAC, API response formatting.

**Python owns:** vector encoding (all 3 dimensions), outcome score computation, weight learning (ML), natural language explanations, synthetic patient generation, embedding model management.

Laravel calls Python AI service internally (same pattern as existing decision-support endpoints). Frontend only talks to Laravel.

### Permissions (RBAC)

All fingerprint endpoints require authentication via Sanctum. Role-based access:

| Endpoint | Required Permission | Notes |
|----------|-------------------|-------|
| `POST /api/fingerprint/search` | `fingerprint.search` | Any clinician with access to the query patient |
| `GET /api/fingerprint/patients/{id}` | `fingerprint.view` | Must have patient access |
| `POST /api/fingerprint/patients/{id}/encode` | `fingerprint.encode` | Attending physician or admin |
| `POST /api/fingerprint/encode-batch` | `fingerprint.admin` | Admin only |
| `GET /api/fingerprint/patients/{id}/outcome` | `fingerprint.view` | Must have patient access |
| `PUT /api/fingerprint/patients/{id}/outcome/assess` | `fingerprint.assess` | Attending physician, specialist, or admin |
| `GET /api/fingerprint/weights` | `fingerprint.view` | Any authenticated user |
| `GET /api/fingerprint/weights/active` | `fingerprint.view` | Any authenticated user |
| `GET /api/fingerprint/stats` | `fingerprint.view` | Any authenticated user |

### Error Handling

**Encoding failures:**
- If the Python AI service is unavailable, Laravel returns a 503 with a user-friendly message. The fingerprint is left in its previous state (or null if first encoding).
- If encoding fails for a specific dimension (e.g., insufficient genomic data), that dimension's vector remains null, the dimension_mask is updated, and the other dimensions proceed normally. The UI shows "Genomic encoding failed: insufficient variant data."
- No automatic retry queue in V1. Encoding is user-triggered or batch-triggered. A failed encoding can be retried by calling the encode endpoint again.

**Partial encoding:**
- If genomic encoding succeeds but volumetric fails, the fingerprint is updated with the genomic vector and the volumetric vector remains null. The patient is still searchable on the genomic dimension.

**Search with no results:**
- If no similar patients are found (e.g., unique fingerprint, very small cohort), the UI shows an empty state: "No similar patients found. This may improve as more patients are fingerprinted."

## 5. UI Design

### Similar Patients Tab (Patient Profile)

New tab alongside existing Overview, Genomics, Imaging, Timeline, Tumor Board tabs.

**Layout:** Two-column — results list (left, ~70%) + aggregated intelligence sidebar (right, ~30%).

**Top elements:**
- Fingerprint status banner — shows which dimensions have data + per-dimension confidence + encoding freshness
- Weight controls — preset buttons (Balanced, Genomics-First, Volumetric, Custom) + three sliders for manual weight adjustment

**Result cards** (left column), each showing:
- Patient identifier, demographics, diagnosis, key mutation, treatment summary, best response
- Composite similarity score (prominent) + per-dimension breakdown bars (genomic, volumetric, clinical)
- AI-generated natural language explanation of why they're similar
- Color-coded outcome badge (Excellent → Failure)
- Cautionary flag on poor outcomes with explanation of what went wrong

**Sidebar** (right column):
- Outcome distribution stacked bar across all similar patients
- **Abby's Insight** — AI-synthesized narrative pattern ("patients who received targeted therapy did better than chemo alone")
- Treatment response rates ("what worked" with per-drug success ratios)
- Aggregated hindsight notes from clinicians who treated similar patients

### Outcome Assessment Modal

Accessible from any patient profile. Fields:
- Overall outcome rating (5-point enum, button selector)
- Decision point tags (toggleable chips from curated set + custom tags)
- Key factors (free-text)
- Hindsight note (optional free-text)
- Save assessment → updates outcome_trajectories table

## 6. Golden Cohort — 20 Synthetic Patients

### Composition

4 cancer types × 5 patients each:

**NSCLC (5):** BRAF V600E cluster (3 patients — pembrolizumab CR, dabrafenib+trametinib PR, carboplatin PD), EGFR L858R (osimertinib PR), KRAS G12C (sotorasib mixed)

**RCC (5):** VHL-mutant cluster with varying co-mutations (PBRM1, BAP1, SETD2), MET amplification outlier. Treatments: nivolumab+cabozantinib, pembrolizumab+axitinib, sunitinib, everolimus, cabozantinib mono

**Breast Cancer (5):** HER2+ with PIK3CA (T-DXd CR), BRCA1 germline (olaparib PR), ER+/HER2- with ESR1 (fulvestrant+CDK4/6 SD), TNBC (pembrolizumab+chemo PR), HER2+ without actionable mutations (trastuzumab PD)

**Pancreatic (5):** KRAS G12D + BRCA2 (FOLFIRINOX+olaparib PR), KRAS G12D + CDKN2A (FOLFIRINOX SD), KRAS G12V + TP53 (gem+nabPaclitaxel SD), MSI-H/MLH1 loss rare case (pembrolizumab PR), KRAS G12D + SMAD4 (gemcitabine PD)

### Data Density (Every Patient)

**Genomic:** 8-15 variants, 1-3 actionable mutations, allele frequencies, clinical significance, gene-drug interactions, ClinVar annotations, TMB score

**Volumetric:** 2-4 imaging studies (baseline + follow-up), segmentations with volume_mm³, RECIST measurements per timepoint, target + non-target lesions, volume change trajectory, response assessment

**Clinical:** Primary + secondary conditions, 2-4 medication eras with dates, 6-10 lab measurements over time, 3-5 visits, 1-2 procedures, clinical notes, condition eras

### Outcome Distribution

4 Excellent · 7 Good · 4 Mixed · 5 Poor — intentionally balanced, not skewed positive.

### Design Principles

- **Deliberate similarity clusters** within each cancer type: 2-3 patients share key mutations but received different treatments with different outcomes → demonstrates "what worked"
- **Cross-type bridges**: some patients share mutations across cancer types (e.g., MSI-H in both PDAC and RCC) → demonstrates cross-indication similarity discovery
- **Pre-seeded clinician assessments** with deliberate computed/clinician score disagreements → exercises the learning loop
- **Idempotent seeder script** tagged with `source_type: "golden_cohort"` for clean management

### Generation Strategy

1. LLM-assisted generation (Ollama/Abby) for clinically plausible narratives
2. Human review for medical accuracy
3. JSON templates ensuring consistent data density
4. Python seeder script populating all clinical tables

## 7. Future Surfaces (Post-V1)

Not in scope for V1, but the engine supports:
- **Tumor Board view** — pull up similar patients as a group discussion tool during multidisciplinary review
- **Research Explorer** — cohort-level similarity analysis, not tied to a single active patient
- **Abby integration** — "Find me patients like this one" via natural language in the Abby chat interface

## 8. Technical Constraints

- **Disk space conscious** — golden cohort over massive public datasets
- **pgvector already available** — existing PatientEmbedding table proves infrastructure works
- **Ollama for LLM tasks** — local inference, no external API dependency for encoding/explanation
- **Sparse data is the norm** — every design decision accounts for partial fingerprints
- **Encoder versioning** — fingerprints track which encoder version produced them for future re-encoding
- **pgvector indexing** — at 20 patients, brute-force scan is fine. At 200+, add HNSW indexes on each vector column (`CREATE INDEX ... USING hnsw (genomic_vector vector_cosine_ops)`). HNSW chosen over IVFFlat for better recall at small-to-medium scale.
- **similarity_searches is write-only audit** — not queried by the feedback loop. Weight learning uses `outcome_trajectories` + `patient_fingerprints` directly. Audit data retained for 12 months, then archived.
