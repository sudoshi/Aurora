# Aurora V2 — Complete Overhaul Design Document

**Date**: 2026-03-09
**Status**: Approved
**Approach**: Clean Room with Parthenon DNA (Approach 2)

---

## Table of Contents

1. [Product Vision & Identity](#1-product-vision--identity)
2. [System Architecture](#2-system-architecture)
3. [Data Architecture — Clinical Adapter Layer](#3-data-architecture--clinical-adapter-layer)
4. [Imaging Architecture](#4-imaging-architecture)
5. [Frontend Architecture & Design System](#5-frontend-architecture--design-system)
6. [Collaboration Engine & Live Sessions](#6-collaboration-engine--live-sessions)
7. [AI Architecture (Abby)](#7-ai-architecture-abby)
8. [Authentication & SSO Bridge](#8-authentication--sso-bridge)
9. [Federation Architecture](#9-federation-architecture)
10. [Migration Strategy](#10-migration-strategy)
11. [Testing & Quality Strategy](#11-testing--quality-strategy)

---

## 1. Product Vision & Identity

**Aurora** — The Advanced Clinical Case Intelligence Platform

A platform where multidisciplinary teams collaborate on complex medical cases — oncology, rare diseases, complex surgical cases — with deep patient data exploration, AI-assisted decision support, and cross-institutional federation.

**Tagline**: *"Every complex case deserves collective intelligence."*

### Three Modes of Use

1. **Solo Exploration** — A clinician prepares for a case conference by reviewing a patient's full clinical timeline, labs, imaging, genomics, and running "Patients Like This" to see outcomes of similar cases
2. **Async Collaboration** — Team members annotate findings, attach evidence, comment on cases, and prepare agendas — all before the meeting
3. **Live Session** — The team enters a structured live session: shared view, real-time presence, case presentation, discussion, decision capture, auto-generated clinical notes

### Specialty-Agnostic by Design

- **Oncology**: Tumor boards, molecular tumor boards, genomic variant review
- **Rare Diseases**: Diagnostic odyssey tracking, phenotype matching, gene panels
- **Complex Surgical**: Pre-operative planning, multidisciplinary surgical review
- **Complex Medical**: Multi-comorbidity management, treatment optimization

### Key Differentiators

- **Patients Like This**: Genomics-weighted similarity engine with federated cross-institutional queries
- **Structured Decision Capture**: Not just discussion — tracked recommendations, votes, dissent, guideline concordance, and outcome tracking
- **Abby AI**: Conversational copilot + structured decision support (trial matching, variant interpretation, guideline checks)
- **Federation**: Cross-institutional intelligence sharing without PHI crossing boundaries

---

## 2. System Architecture

### Monorepo Structure (Parthenon Pattern)

```
aurora/
├── backend/                  # Laravel 11 API (PHP 8.4)
├── frontend/                 # React 19 SPA (TypeScript strict)
├── ai/                       # Python FastAPI (Abby — similarity, copilot, decision support)
├── federation/               # Lightweight federation relay service
├── e2e/                      # Playwright E2E tests
├── docker/                   # Container definitions
├── docs/                     # User manual, API docs, plans
├── docker-compose.yml        # 10-service stack
├── deploy.sh                 # One-command deployment
└── Makefile                  # Dev shortcuts
```

### Docker Compose Services

| Service | Tech | Purpose |
|---------|------|---------|
| **api** | PHP 8.4 / Laravel 11 | Core API — cases, collaboration, users, events |
| **web** | Nginx | Reverse proxy, static assets, SPA serving |
| **db** | PostgreSQL 16 + pgvector | Multi-schema database |
| **redis** | Redis 7 | Cache, sessions, queue broker, real-time pub/sub |
| **ai** | Python FastAPI | Abby — copilot, similarity engine, decision support |
| **worker** | PHP / Horizon | Background jobs (note generation, similarity indexing) |
| **ws** | Laravel Reverb (or Soketi) | WebSocket server for live sessions |
| **search** | Meilisearch (or Solr) | Full-text clinical search |
| **federation** | Python or Go | Optional cross-instance relay |
| **node** | Node.js | Vite dev server (dev only) |

### Key Architectural Decisions

1. **Laravel Reverb over Pusher** — Self-hosted WebSockets. No external dependency. Critical for HIPAA/on-prem deployments. Drop-in replacement for Pusher protocol.
2. **Meilisearch over Solr** — Lighter, faster to deploy, excellent typo tolerance for clinical terms. Optional — system works without it (falls back to PostgreSQL full-text).
3. **pgvector for embeddings** — Powers "Patients Like This" similarity queries, semantic search, and AI features. Already in the Parthenon stack.

### Multi-Schema PostgreSQL

| Schema | Purpose |
|--------|---------|
| `app` | Aurora application data (cases, sessions, teams, decisions) |
| `clinical` | Normalized clinical data (from any adapter) |
| `vocab` | OMOP vocabulary (when connected to OMOP source) |
| `federation` | Cross-instance shared state, de-identified profiles |

---

## 3. Data Architecture — Clinical Adapter Layer

The heart of Aurora's flexibility. A normalized internal clinical model that any data source maps into.

### Internal Clinical Model (stored in `clinical` schema)

```
ClinicalPatient
├── demographics (name, DOB, gender, race, ethnicity, location)
├── identifiers[] (MRN, person_id, FHIR id — source-tagged)
├── observation_periods[] (start, end — when we have data)
│
├── conditions[] (diagnosis, onset, resolution, status, severity)
├── medications[] (drug, start, end, dose, route, frequency)
├── procedures[] (procedure, date, modifier, provider)
├── measurements[] (lab/vital, value, unit, range_low, range_high, date)
├── observations[] (clinical finding, value, date)
├── visits[] (type, start, end, provider, facility)
├── notes[] (type, title, text, date, author)
├── imaging[] (modality, body_site, date, series[], instances[])
├── genomics[] (gene, variant, significance, zygosity, source)
│
├── eras[] (condition_era, drug_era — computed aggregates)
└── embeddings[] (pgvector — for similarity queries)
```

### Three Adapter Implementations

| Adapter | Source | How It Works |
|---------|--------|--------------|
| **OMOP** | Parthenon / any OMOP CDM | Reads directly from CDM schema. Maps `condition_occurrence` → `conditions[]`, `drug_exposure` → `medications[]`, etc. Read-only against the CDM — zero ETL needed. |
| **FHIR** | EHR via SMART on FHIR | Pulls FHIR R4 resources (Patient, Condition, MedicationRequest, Observation, DiagnosticReport, ImagingStudy). Normalizes into internal model. Can be real-time or batch-synced. |
| **Manual** | Case conference entry | Team members enter patient data directly through Aurora's UI. Rich forms for each clinical domain. Supports file uploads (PDFs, DICOM, genomic reports). |

### Design Principles

1. **Read-through, not copy** — The OMOP adapter queries the CDM live (with caching). No data duplication. The FHIR adapter can cache or query live depending on configuration.
2. **Source tagging** — Every clinical record carries a `source_id` and `source_type` (omop, fhir, manual). The UI shows provenance. Multiple sources per patient supported.
3. **Embeddings computed on ingest** — When a patient is loaded (from any adapter), the AI service computes a clinical embedding vector. Stored in `clinical.patient_embeddings`. Powers "Patients Like This."

### Patients Like This Engine

- **Input**: Current patient's embedding vector + optional filters (age range, specific conditions, genomic variants)
- **Query**: pgvector cosine similarity against all indexed patients
- **Output**: Ranked list of similar patients with trajectory summaries (treatment paths, outcomes, survival)
- **Federated mode**: Query is broadcast to connected Aurora instances (de-identified), results merged and ranked
- **Genomics-weighted**: When genomic data is present, variant overlap is heavily weighted in similarity scoring

---

## 4. Imaging Architecture

DICOM is a first-class clinical domain across all four specialties.

### DICOM Across All Domains

| Domain | DICOM Use | Examples |
|--------|-----------|---------|
| **Oncology** | Volumetric tumor analysis, RECIST measurements, treatment response tracking | CT/MRI tumor volumes over time, PET SUV values, response assessment (CR/PR/SD/PD) |
| **Surgical** | Pre-operative 3D planning, anatomical measurements, post-op comparison | Organ volumes, vessel mapping, implant sizing, surgical approach planning |
| **Rare Disease** | Phenotypic imaging markers, longitudinal morphometric tracking | Brain MRI volumetrics, skeletal surveys, organ size progression |
| **Complex Medical** | Functional imaging, disease burden quantification | Cardiac MRI ejection fraction, liver volumetry in cirrhosis, lung fibrosis scoring |

### Imaging Data Model

```
imaging[]
├── study (modality, body_site, date, description, referring_provider)
├── series[] (series_uid, modality, description, instance_count)
├── instances[] (sop_uid, instance_number, slice_location)
├── measurements[] (type, value, unit, date, annotator)
│   ├── linear (RECIST longest diameter, short axis)
│   ├── volumetric (3D segmentation volume in cm3)
│   ├── functional (SUV max/mean, ADC, ejection fraction)
│   └── derived (volume change %, doubling time, response category)
├── segmentations[] (label, volume, algorithm, confidence)
├── response_assessments[] (criteria, category, prior_study_ref, date)
└── viewer_state (window/level, annotations, bookmarked slices)
```

### Viewer Integration

- **Cornerstone3D** (same as Parthenon) for 2D/3D rendering, MPR, MIP
- **Volume rendering** for 3D tumor visualization and surgical planning
- **Segmentation overlay** — AI-generated or manual tumor/organ contours displayed on the image
- **Longitudinal comparison** — Side-by-side or overlay of same anatomy across time points, with volume/measurement trend charts
- **DICOM SR support** — Structured reports ingested as measurements, not just images

### Volumetric Analysis Pipeline (in `ai/` service)

1. DICOM series uploaded or pulled from PACS
2. AI service runs segmentation model (organ/tumor-specific)
3. Computes volume, surface area, longest diameter
4. Stores as `measurements[]` + `segmentations[]`
5. On subsequent studies: auto-matches prior, computes delta, assigns response category
6. Results displayed in Patient Profile imaging tab with trend charts

Imaging measurements feed directly into the "Patients Like This" embedding — a patient's tumor volume trajectory and response pattern become part of their similarity profile.

---

## 5. Frontend Architecture & Design System

Port Parthenon's design system wholesale, then extend it for collaboration.

### Design Tokens (from Parthenon's `tokens-dark.css`)

| Token | Value | Usage |
|-------|-------|-------|
| Surface Base | `#0E0E11` | Main background |
| Surface Raised | `#151518` | Cards, panels |
| Surface Elevated | `#232328` | Borders, modals |
| Primary | `#9B1B30` | Key actions, conditions domain |
| Accent | `#C9A227` | Focus states, highlights |
| Success/Teal | `#2DD4BF` | Active states, drugs domain |
| Text Primary | `#F0EDE8` | Main text |
| Text Secondary | `#C5C0B8` | Labels |
| Text Muted | `#8A857D` | Hints |

Same dark, professional aesthetic optimized for long clinical sessions. Same typography (IBM Plex Mono for IDs, sans-serif for body).

### Frontend Structure (Feature-Based)

```
frontend/src/
├── features/
│   ├── auth/                    # Login, register, password change
│   ├── dashboard/               # Home — upcoming sessions, recent cases, activity
│   ├── cases/                   # Case management — create, browse, assign
│   │   ├── pages/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── api/
│   │   └── types/
│   ├── patient-profile/         # Ported from Parthenon — timeline, labs, imaging, genomics
│   │   ├── components/
│   │   │   ├── PatientDemographicsCard.tsx
│   │   │   ├── PatientTimeline.tsx          # Interval-packed, zoom, domain colors
│   │   │   ├── PatientLabPanel.tsx          # Values + reference ranges
│   │   │   ├── PatientNotesTab.tsx          # Paginated clinical notes
│   │   │   ├── PatientImagingTab.tsx        # Cornerstone3D + volumetrics
│   │   │   ├── PatientGenomicsTab.tsx       # Variants, ClinVar, actionable genes
│   │   │   ├── PatientVisitView.tsx         # Visit-grouped events
│   │   │   ├── EraTimeline.tsx              # Condition/drug eras
│   │   │   ├── PatientsLikeThis.tsx         # Similarity results + trajectories
│   │   │   └── ConceptDetailDrawer.tsx
│   │   └── hooks/useProfiles.ts
│   ├── collaboration/           # Core Aurora differentiator
│   │   ├── pages/
│   │   │   ├── SessionLobbyPage.tsx         # Pre-session: agenda, case list, team
│   │   │   └── LiveSessionPage.tsx          # In-session: shared view, presence
│   │   ├── components/
│   │   │   ├── CasePresenter.tsx            # Current case being discussed
│   │   │   ├── ParticipantBar.tsx           # Who's here, roles, speaking indicator
│   │   │   ├── SharedAnnotations.tsx        # Real-time annotations on patient data
│   │   │   ├── DecisionCapture.tsx          # Structured recommendation entry
│   │   │   ├── SessionTimer.tsx             # Per-case and overall timers
│   │   │   ├── AgendaPanel.tsx              # Case queue, reorder, skip
│   │   │   └── SessionNoteGenerator.tsx     # AI-generated summary at session end
│   │   └── hooks/
│   │       ├── useWebSocket.ts              # Reverb connection
│   │       ├── usePresence.ts               # Who's viewing what
│   │       └── useSessionState.ts           # Shared session state
│   ├── copilot/                 # Abby AI assistant
│   │   ├── components/
│   │   │   ├── CopilotPanel.tsx             # Slide-over chat panel
│   │   │   ├── CopilotSuggestion.tsx        # Inline suggestions in patient view
│   │   │   └── TrialMatchResults.tsx        # Clinical trial matching display
│   │   └── hooks/useCopilot.ts
│   ├── decisions/               # Decision tracking & audit trail
│   │   ├── components/
│   │   │   ├── DecisionTimeline.tsx          # History of all decisions for a case
│   │   │   ├── GuidelineConcordance.tsx      # How decision aligns with guidelines
│   │   │   └── OutcomeTracker.tsx            # Track outcomes of past decisions
│   │   └── types/
│   └── settings/                # User preferences, team management, data sources
│
├── components/                  # Shared UI components
│   ├── ui/                      # Buttons, inputs, cards, modals, drawers
│   ├── navigation/              # TopNav, sidebar, breadcrumbs
│   ├── CommandPalette.tsx       # Cmd+K global search
│   └── Toast.tsx                # Notifications
│
├── stores/                      # Zustand
│   ├── authStore.ts
│   ├── uiStore.ts
│   ├── sessionStore.ts          # Live session state
│   └── profileStore.ts          # Recent profiles (ported from Parthenon)
│
├── hooks/                       # Shared hooks
├── lib/                         # API client, query client, utilities
├── types/                       # Global TypeScript types
└── styles/
    ├── tokens-dark.css          # Ported from Parthenon
    ├── tokens-base.css          # Spacing, typography, radii
    └── app.css                  # Tailwind 4 entry
```

### State Management (Parthenon Pattern)

- **Zustand** for client state (auth, UI, session, recent profiles) with localStorage persistence
- **TanStack Query** for all server state (patients, cases, discussions, search)
- **No Context API** except where React requires it

### Key UX Flows

1. **Dashboard** — See upcoming sessions, recent cases, activity feed, quick patient search
2. **Case Detail** — Patient profile (all 8+ view modes) + case annotations, team, decision history, "Patients Like This"
3. **Session Lobby** — Review agenda, assign presenters, prep cases before going live
4. **Live Session** — Shared patient view, presenter controls, real-time annotations, decision capture, Abby sidebar
5. **Post-Session** — AI-generated session notes, decisions logged, follow-up tasks assigned

---

## 6. Collaboration Engine & Live Sessions

Aurora's core differentiator — what no other clinical platform does well.

### Data Model (in `app` schema)

```sql
-- Cases
cases
├── id, title, specialty, urgency, status (draft/active/closed/archived)
├── patient_id (FK → clinical.patients)
├── created_by, institution_id
├── case_type (tumor_board, surgical_review, rare_disease, medical_complex)
├── clinical_question (text — "What is the best treatment approach for...")
│
├── case_team_members[] (user_id, role: presenter/reviewer/observer, invited_at)
├── case_annotations[] (user_id, domain, record_ref, content, anchored_to)
│   └── anchored_to: specific lab value, imaging measurement, genomic variant, timeline point
├── case_documents[] (file, type: pathology_report/radiology/genomic/external)
├── case_discussions[] (threaded, with @mentions, reactions, file attachments)
└── case_decisions[] (see below)

-- Sessions
sessions
├── id, title, scheduled_at, duration_minutes, status (scheduled/live/completed)
├── session_type (tumor_board, mdc, surgical_planning, grand_rounds, ad_hoc)
├── created_by, institution_id
│
├── session_cases[] (case_id, order, presenter_id, time_allotted_minutes, status)
├── session_participants[] (user_id, role, joined_at, left_at)
└── session_recording (optional — audio/transcript reference)

-- Decisions
decisions
├── id, case_id, session_id (nullable — can be async)
├── decision_type (treatment_plan, diagnostic_workup, referral, watchful_waiting, clinical_trial)
├── recommendation (text)
├── rationale (text)
├── guideline_refs[] (NCCN, ASCO, ESMO, disease-specific)
├── dissenting_opinions[] (user_id, opinion)
├── confidence_level (consensus/majority/split)
├── decided_by[] (user_ids who voted)
├── decided_at
│
├── follow_ups[] (task, assigned_to, due_date, status)
└── outcome (recorded later — what actually happened, patient response)
```

### Live Session WebSocket Protocol (via Laravel Reverb)

| Event | Direction | Payload |
|-------|-----------|---------|
| `session.joined` | server → all | user, role, avatar |
| `session.left` | server → all | user |
| `case.presenting` | presenter → all | case_id, view_mode, scroll_position |
| `case.view_sync` | presenter → all | tab, filters, selected_record — followers see same view |
| `annotation.added` | user → all | annotation on specific clinical record |
| `cursor.moved` | user → all | x, y position on shared view (throttled) |
| `decision.proposed` | user → all | draft recommendation |
| `decision.voted` | user → server | agree/disagree/abstain + optional comment |
| `decision.finalized` | server → all | final recommendation + vote tally |
| `timer.tick` | server → all | remaining time for current case |
| `copilot.suggestion` | server → requester | AI insight (only shown to requester unless shared) |

### How a Live Session Works

1. **Presenter advances to a case** → all participants see that patient's profile
2. **View sync is opt-in** — participants can "follow presenter" (default) or browse independently
3. **Annotations are real-time** — presenter highlights a lab value, everyone sees the highlight
4. **Discussion happens alongside** — threaded chat in sidebar, or voice (future: integrated audio)
5. **Decision capture is structured** — when ready, presenter proposes a recommendation. Participants vote (agree/disagree/abstain with comment). System records the decision with rationale and dissent.
6. **Timer keeps things moving** — configurable per-case time, gentle warning at 80%, hard stop optional
7. **Abby available** — any participant can ask Abby privately; can share Abby responses with the group
8. **Session ends** → Abby generates a structured session note per case (presenting problem, key findings discussed, decision, rationale, follow-ups, dissenting views). Auto-saved to the case record.

---

## 7. AI Architecture (Abby)

Abby is the default AI assistant for all Acumenus products. In Aurora, Abby operates across three layers.

### Layer 1: Clinical Copilot (Conversational)

| Capability | How It Works |
|------------|-------------|
| **Patient summary** | Given a patient's clinical data, generates a concise narrative |
| **Case prep** | Before a session, auto-generates a structured brief: history, key findings, open questions, relevant literature |
| **Question answering** | Answers grounded in the patient's actual data + knowledge base |
| **Discussion summarizer** | Summarizes async case discussion threads into key points and unresolved questions |
| **Session note generator** | Post-session, generates structured clinical notes from decision capture data + discussion |

**Model strategy** (same as Parthenon's Abby pattern — configurable via admin/ai-providers):
- Default: Local model via Ollama (data never leaves the institution)
- Optional: OpenAI, Anthropic, Azure OpenAI, Google, AWS Bedrock
- Medical-tuned models preferred (MedGemma, Med-PaLM, BioMistral) but any capable LLM works

### Layer 2: Similarity Engine ("Patients Like This")

```
Input:  Patient clinical profile
  ↓
Step 1: Compute clinical embedding (demographics + dx + meds + genomics + imaging)
  ↓
Step 2: pgvector ANN search (cosine similarity, top 50 candidates)
  ↓
Step 3: Re-rank with domain-specific weighting:
         - Genomic variant overlap     (weight: 0.30 when genomics present)
         - Primary diagnosis match     (weight: 0.25)
         - Treatment history overlap   (weight: 0.20)
         - Demographics similarity     (weight: 0.10)
         - Imaging characteristics     (weight: 0.10)
         - Comorbidity overlap         (weight: 0.05)
  ↓
Step 4: Filter by user constraints (age range, specific conditions, etc.)
  ↓
Step 5: For top N matches, compute trajectory summaries:
         - Treatment paths taken
         - Response rates per treatment
         - Median time-to-progression
         - Overall survival curves
         - Adverse events
  ↓
Output: Ranked similar patients + aggregate trajectory visualization
```

**Federation mode**: Step 2 broadcasts the embedding vector (no patient data) to connected Aurora instances. Each instance runs local ANN search and returns de-identified summary statistics only. No PHI crosses institutional boundaries.

### Layer 3: Decision Support Modules (Structured)

| Module | Input | Output |
|--------|-------|--------|
| **Clinical trial matching** | Patient dx + genomics + demographics | Ranked eligible trials from ClinicalTrials.gov (auto-refreshed) |
| **Guideline concordance** | Proposed treatment decision | How it aligns with NCCN, ASCO, ESMO, disease-specific guidelines. Flags deviations. |
| **Genomic variant interpretation** | Variant list | ClinVar significance, OncoKB actionability, PharmGKB drug interactions, AMP/ASCO/CAP tier classification |
| **Drug interaction checker** | Current + proposed medications | Flags contraindications, dose adjustments, overlapping toxicities |
| **Prognostic modeling** | Patient features | Risk scores using validated models (Charlson, ECOG-derived, disease-specific staging nomograms) |
| **Rare disease phenotype matcher** | HPO terms + genomics | Matches against OMIM, Orphanet, Monarch Initiative. Suggests candidate diagnoses for undiagnosed patients. |

### Abby API Endpoints (all under `ai/` service)

```
POST /api/ai/copilot/chat              # Conversational copilot
POST /api/ai/copilot/summarize         # Patient/discussion summary
POST /api/ai/copilot/session-note      # Generate session note

POST /api/ai/similarity/search         # Patients Like This
POST /api/ai/similarity/embed          # Compute embedding for a patient
POST /api/ai/similarity/federated      # Federated search across instances

GET  /api/ai/trials/match/{patientId}  # Clinical trial matching
POST /api/ai/genomics/interpret        # Variant interpretation
POST /api/ai/guidelines/check          # Guideline concordance
POST /api/ai/drugs/interactions        # Drug interaction check
POST /api/ai/prognosis/score           # Prognostic scoring
POST /api/ai/rare-disease/match        # Phenotype matching
```

---

## 8. Authentication & SSO Bridge

### Mode 1: Standalone Aurora (Institution Without Parthenon)

Identical to Parthenon's auth flow (ported directly):

1. Register: name, email, phone — no password field
2. 12-char temp password generated, emailed via Resend (from: `Aurora <noreply@acumenus.net>`)
3. Login → `must_change_password` enforced via non-dismissable modal
4. Sanctum token issued, RBAC via Spatie

Same `AuthController`, `AuthService` code — literally ported from Parthenon.

### Mode 2: Parthenon SSO (Institution With Both Products)

```
Parthenon                          Aurora
---------                          -----
User clicks "Tumor Board"    →
button in Precision Medicine

Parthenon generates a
signed JWT (short-lived, 60s):
  {
    sub: user_id,
    email: "dr.chen@hospital.org",
    name: "Dr. Sarah Chen",
    roles: ["oncologist"],
    source_id: 3,
    person_id: 48291,              ← patient context passed through
    iat: now,
    exp: now + 60s
  }
  signed with AURORA_SSO_SECRET

Redirects to:
aurora.hospital.org/sso?token=eyJ.. →  Aurora receives JWT

                                   Validates signature + expiry
                                   Finds or creates local user
                                   Issues Aurora Sanctum token
                                   Redirects to case/patient view
                                   with patient context pre-loaded

                                   User is IN — zero re-login
```

**SSO Endpoint**: `POST /api/auth/sso/parthenon`

**Aurora Configuration** (`.env`):

```
AURORA_SSO_ENABLED=true
AURORA_SSO_PARTHENON_SECRET=shared-secret-here
AURORA_SSO_PARTHENON_URL=https://parthenon.hospital.org
```

**Parthenon-Side Changes** (~20 lines of code):
- Add `AURORA_SSO_SECRET` and `AURORA_URL` to `.env`
- Update Tumor Board button to generate JWT and redirect to Aurora

### Development Superuser

- **Email**: `admin@acumenus.net`
- **Password**: `superuser` (bcrypt hashed, `must_change_password: false` — exempt from forced change)
- **Roles**: All roles assigned (admin + every other role)
- **Permissions**: `*` wildcard — bypasses all permission checks
- **is_active**: Always `true`
- **Seeded on every migration** — if accidentally deleted, `php artisan db:seed` restores it
- **Cannot be deleted or deactivated** via the admin UI — protected in `UserService`
- **Excluded from federation** — never appears in de-identified datasets
- **Used only by developers** — not for production clinical use

### RBAC Roles

| Role | Capabilities |
|------|-------------|
| **admin** | Full system administration, user management, data source config |
| **department_head** | Create sessions, manage team, view all cases in department |
| **attending** | Present cases, make decisions, full patient profile access |
| **fellow** | Present cases, participate in decisions, full profile access |
| **resident** | Observe sessions, annotate, limited decision participation |
| **nurse_coordinator** | Manage schedules, case logistics, upload documents |
| **data_analyst** | Run "Patients Like This" queries, export data, no clinical decisions |
| **observer** | View-only access to sessions and cases (auditors, students) |

---

## 9. Federation Architecture

Cross-institutional intelligence sharing. Strictly opt-in at every level.

### What Federates (De-Identified Only)

| Data | What Crosses the Wire | What Never Leaves |
|------|----------------------|-------------------|
| **Patients Like This** | Embedding vector (no PHI) + aggregate stats returned | Patient names, MRNs, identifiers, raw clinical data |
| **Shared case conferences** | Invited participants join via SSO, see only the presenting institution's shared view | Other institution's local patient data |
| **Aggregate outcomes** | "N=47 similar patients: 68% responded to pembrolizumab, median PFS 11.2mo" | Individual patient records behind the aggregate |
| **Rare disease matching** | HPO phenotype codes + genomic variants (de-identified) | Patient identity, demographics beyond age range |

### Federation Protocol

```
Institution A (Aurora)             Federation Relay             Institution B (Aurora)
----------------------             ----------------             ----------------------

Clinician runs
"Patients Like This"
with federation enabled
        │
        ▼
Computes embedding locally
Strips all PHI
Signs request with institution cert
        │
        ├──── mTLS ──────────►    Validates cert
                                   Routes to registered peers
                                         │
                                         ├──── mTLS ──────────►  Validates cert
                                                                  Runs local ANN search
                                                                  Computes aggregate stats
                                                                  Signs response
                                         ◄──── mTLS ────────────┤
                                   Merges responses
        ◄──── mTLS ──────────┤    Returns to requester
        │
        ▼
Merges local + federated results
Displays: "Based on 312 similar
patients across 4 institutions..."
```

### Security Model

1. **mTLS everywhere** — Mutual TLS between all instances. No anonymous queries.
2. **Institution registry** — Admin explicitly approves which institutions to federate with. Not open discovery.
3. **Query audit log** — Every federated query logged with: who, when, what type, which peers responded. Zero PHI in the log.
4. **Rate limiting** — Max queries per hour per institution, configurable.
5. **Patient opt-out** — If an institution's IRB requires it, specific patients can be excluded from federation index.
6. **No raw data relay** — The federation service never stores or caches clinical data. It's a message router only.

### Cross-Institutional Case Conferences

- Institution A creates a session and invites an external specialist from Institution B
- Institution B's specialist receives an email/notification with a session link
- They authenticate via their own Aurora instance (SSO between federated peers)
- They see only the cases Institution A explicitly shares in that session
- Their annotations and recommendations are captured in Institution A's decision record
- No data from Institution B's patients is exposed

### Configuration

```env
AURORA_FEDERATION_ENABLED=false          # Off by default
AURORA_FEDERATION_RELAY_URL=             # Central relay or direct peer-to-peer
AURORA_FEDERATION_CERT_PATH=             # mTLS certificate
AURORA_FEDERATION_INSTITUTION_ID=        # Unique institution identifier
AURORA_FEDERATION_APPROVED_PEERS=        # Comma-separated institution IDs
```

---

## 10. Migration Strategy

Archive current Aurora, build new Aurora on Parthenon's foundation.

### What We Keep from Current Aurora

| Asset | How We Use It |
|-------|--------------|
| Auth system rules (`.claude/rules/auth-system.md`) | Ported to new repo, same constraints |
| `SecurityHeaders` middleware | Ported directly |
| Domain knowledge (event model, case discussions) | Informs the new `sessions` and `cases` schema |
| CI pipeline (`.github/workflows/ci.yml`) | Template for new, expanded CI |
| Docker config | Starting point, expanded to 10 services |
| Resend email integration | Ported to new `AuthService` |

### What We Don't Carry Forward

- 20+ placeholder routes and `UnderDevelopment` components — replaced by real implementations
- Simple `patients`/`cases` schema — replaced by the clinical adapter layer
- `Collaboration.jsx` with hardcoded tabs — replaced by ported Patient Profile + collaboration engine
- Zustand/Context dual-layer auth — simplified to Zustand only (Parthenon pattern)
- RippleUI dependency — removed, using Parthenon's token-based components
- Pusher.js — replaced by Laravel Reverb (self-hosted WebSockets)

### Phased Implementation

**Phase 0: Archive & Scaffold**
- Archive current Aurora repo (tag `v1-archive`, branch `archive/legacy`)
- Initialize new monorepo with Parthenon's structure
- Set up Docker Compose (10 services)
- Seed superuser (`admin@acumenus.net` / `superuser`)

**Phase 1: Foundation**
- Port auth system (standalone + Parthenon SSO)
- Port design system (tokens, shared components)
- Build clinical adapter layer (manual first, then OMOP, then FHIR)
- Port Patient Profile from Parthenon (all 8+ view modes)

**Phase 2: Collaboration Core**
- Build case management (create, assign, annotate, discuss)
- Build session engine (lobby, live session, WebSocket protocol)
- Build decision capture and tracking
- Integrate patient profile into case/session views

**Phase 3: AI & Intelligence**
- Port Abby from Parthenon (copilot, chat, summarization)
- Build similarity engine ("Patients Like This")
- Build decision support modules (trials, guidelines, genomics)
- Integrate Abby into session flow

**Phase 4: Imaging & Specialty**
- Integrate Cornerstone3D (port from Parthenon)
- Build volumetric analysis pipeline
- Build specialty-specific workflows (oncology, rare disease, surgical, complex medical)

**Phase 5: Federation & Scale**
- Build federation relay service
- Implement federated "Patients Like This"
- Implement cross-institutional case conferences
- Cloud deployment option

**Phase 6: Polish & Harden**
- E2E test suite (Playwright)
- Performance optimization
- Security audit
- Documentation (user manual, API docs)

---

## 11. Testing & Quality Strategy

Porting Parthenon's quality bar.

### Testing Pyramid

| Layer | Tool | Target | Coverage |
|-------|------|--------|----------|
| **PHP Unit/Feature** | Pest 3 | Services, controllers, adapters, auth flows | 80%+ |
| **PHP Static Analysis** | PHPStan Level 8 | Type safety, null checks, dead code | Zero errors |
| **PHP Style** | Pint (PSR-12) | Consistent code style | Zero violations |
| **TypeScript** | `tsc --strict` | No `any`, strict null checks | Zero errors |
| **JS Unit** | Vitest | Stores, hooks, utilities, component logic | 80%+ |
| **JS Lint** | ESLint + Prettier | Code quality, formatting | Zero violations |
| **Python Unit** | pytest | AI service, similarity engine, adapters | 80%+ |
| **Python Types** | mypy | Type safety for AI service | Zero errors |
| **E2E** | Playwright | Critical user flows | All pass |
| **API Docs** | Scramble | Auto-generated OpenAPI spec from code | Always current |

### Critical E2E Flows (Playwright)

1. **Auth flow** — Register → receive temp password → login → forced change → dashboard
2. **Parthenon SSO** — Simulate JWT → land in Aurora authenticated with patient context
3. **Patient profile** — Search patient → timeline renders → switch view modes → labs display values
4. **Case lifecycle** — Create case → assign team → add annotations → start discussion
5. **Live session** — Create session → add cases → go live → present case → capture decision → generate note
6. **Patients Like This** — Load patient → run similarity → results render with trajectories
7. **Abby copilot** — Open Abby → ask question → receive grounded response
8. **Imaging** — Load DICOM study → Cornerstone3D renders → measurements display

### CI Pipeline (GitHub Actions)

```yaml
jobs:
  backend:
    - pint (style check)
    - phpstan level 8
    - pest (unit + feature tests with coverage)

  frontend:
    - tsc --strict (type check)
    - eslint
    - vitest (unit tests with coverage)

  ai:
    - mypy
    - pytest (with coverage)

  e2e:
    - playwright (against Docker Compose stack)

  security:
    - composer audit (PHP dependency vulnerabilities)
    - npm audit (JS dependency vulnerabilities)
    - pip audit (Python dependency vulnerabilities)
```

---

## Appendix A: Technology Stack Summary

### Backend
| Tech | Version | Purpose |
|------|---------|---------|
| PHP | 8.4 | Language |
| Laravel | 11 | Framework |
| Sanctum | 4.x | Token-based API auth |
| Spatie Permission | 6.x | RBAC |
| Horizon | 5.x | Queue management |
| Reverb | 1.x | WebSocket server |
| Pest | 3.x | Testing |
| PHPStan | 3.x | Static analysis |
| Pint | 1.x | Code style |
| Scramble | 0.x | API docs |

### Frontend
| Tech | Version | Purpose |
|------|---------|---------|
| React | 19 | UI framework |
| TypeScript | 5.9+ | Type safety |
| Vite | 7 | Build tool |
| Tailwind | 4 | CSS framework |
| Zustand | 5 | Client state |
| TanStack Query | 5 | Server state |
| TanStack Table | 8 | Table logic |
| TanStack Virtual | 3 | List virtualization |
| React Hook Form | 7 | Form state |
| Zod | 4 | Schema validation |
| React Router | 6 | SPA routing |
| Cornerstone3D | latest | DICOM viewer |
| Recharts | 3 | Charts |
| Framer Motion | 12 | Animations |
| Lucide React | latest | Icons |

### AI Service
| Tech | Version | Purpose |
|------|---------|---------|
| Python | 3.12 | Language |
| FastAPI | latest | API framework |
| Ollama | latest | Default LLM runtime |
| pgvector | latest | Embedding similarity |
| Pydantic | v2 | Data validation |

### Infrastructure
| Tech | Purpose |
|------|---------|
| Docker Compose | Orchestration |
| PostgreSQL 16 + pgvector | Database |
| Redis 7 | Cache, queue, pub/sub |
| Nginx | Reverse proxy |
| Meilisearch | Full-text search |
| GitHub Actions | CI/CD |

## Appendix B: Hard Constraints

1. Auth system rules from `.claude/rules/auth-system.md` apply to new Aurora
2. Development superuser `admin@acumenus.net` / `superuser` — always exists, all privileges, password never changes, `must_change_password: false`
3. Abby is the AI brand for all Acumenus products
4. Email sender: `Aurora <noreply@acumenus.net>`
5. Resend API for email delivery (RESEND_API_KEY env var)
6. No hardcoded secrets in source code
7. Federation is off by default, opt-in at every level
8. PHI never crosses institutional boundaries in federation
