# Action-Oriented Patient Experience Redesign

**Date:** 2026-03-22
**Status:** Draft
**Scope:** Patient Profile page, Session/Case pages, new collaboration primitives

## Problem

Aurora's patient views were ported from Parthenon, a population health and outcomes research platform. They are retrospective data browsers — timelines, era clustering, cohort comparison — designed for analysts looking backward at what happened. Aurora is a clinical collaboration platform where multidisciplinary teams coordinate care. Clinicians need to look forward: what's going on, what needs attention, what should we do next.

The current architecture separates patient data (Profile page) from collaboration (Case Detail page). Clinicians context-switch between the two, losing the connection between the data that informs a decision and the discussion that produces it.

## Existing Systems This Design Builds On

Before defining the new design, these are the existing Aurora models that this spec extends rather than replaces:

| System | Tables | Purpose | What Changes |
|--------|--------|---------|--------------|
| **Sessions** | `app.clinical_sessions`, `app.session_cases`, `app.session_participants` | Multi-case meeting scheduling with ordered agenda, time allotments, participant tracking | Becomes the primary multi-patient meeting surface (replaces Case as the "tumor board" page) |
| **Decisions** | `app.decisions`, `app.decision_votes`, `app.follow_ups` | Structured decision capture with voting (agree/disagree/abstain), status workflow (proposed→approved), and follow-up task tracking | Extended with `patient_id` and `record_refs` to surface in the patient Briefing |
| **Cases** | `app.cases`, `app.case_discussions`, `app.case_annotations` | Per-patient clinical cases with threaded discussions and annotations | Cases remain per-patient; discussions/annotations gain anchoring fields for the collaboration panel |

## Design Principles

1. **Patient is the primary surface.** Clinicians think "my patient," not "my case." All clinical work — data review, discussion, task assignment, decision capture — happens on the patient page.
2. **Action over observation.** Every data view supports inline actions (flag, discuss, task, annotate). Data is never read-only.
3. **Context sensitivity.** The collaboration panel adapts to what the clinician is viewing. Genomics tab shows genomics discussions; labs tab shows lab-related flags.
4. **Sessions are coordination wrappers.** A Session organizes a multi-patient review (tumor board, surgical conference). It holds the agenda, team roster, and decisions log. The clinical work happens on each patient's page.

## Architecture Overview

### Patient Page (Redesigned)

The patient page becomes a workflow surface with three layers:

```
+------------------------------------------------------------------+
|  Demographics Bar (compact: name, MRN, age, primary dx, tags)    |
+------------------------------------------------------------------+
|                                                    |              |
|  Main Content Area                                 | Collaboration|
|  ┌──────────────────────────────────────────┐     | Panel        |
|  │ [Briefing] Timeline Labs Imaging Genomics│     | (slide-out)  |
|  │  Notes  Visits  Similar                  │     |              |
|  │                                          │     | Context-     |
|  │  Active view content                     │     | sensitive    |
|  │  with inline actions                     │     | to current   |
|  │                                          │     | view tab     |
|  └──────────────────────────────────────────┘     |              |
+------------------------------------------------------------------+
```

### View Tabs

| Tab | Purpose | Changes from Current |
|-----|---------|---------------------|
| **Briefing** (NEW, default) | 30-second patient situation awareness | Replaces Timeline as default landing |
| Timeline | Chronological event visualization | Unchanged, demoted from default |
| Labs | Lab measurements with sparklines | Gains inline actions + select-and-act |
| Imaging | DICOM viewer | Gains inline actions |
| Genomics | Variant table with ClinVar | Gains inline actions + select-and-act |
| Notes | Clinical notes | Gains inline actions |
| Visits | Visit-grouped events | Gains inline actions |
| Similar | AI patient similarity | Unchanged |

**Retired:** Eras tab (era data folded into Briefing active problems and Timeline).

### 1. Clinical Briefing (New Default View)

The Briefing is a four-quadrant dashboard answering "what's happening with this patient right now?"

#### Active Problems (top-left)
- Curated list of current, active conditions and treatments
- Source: conditions with no end_date + active medications
- Shows: problem name, onset/start date
- New findings highlighted (e.g., "new" badge for conditions added in last 14 days)
- Clickable — navigates to relevant data view with filter applied

#### Flagged Findings (top-right)
- Items explicitly flagged by team members or auto-flagged by rules
- Severity indicators: red (critical), amber (attention), blue (informational)
- Source: new entity — `PatientFlag` (see Data Model section)
- Examples: abnormal lab trends, actionable variants, new imaging findings
- Each flag links to the source data point

#### Pending Actions (bottom-left)
- Two sources combined into one view:
  - **Follow-ups** from decisions (existing `app.follow_ups` where the decision's case belongs to this patient)
  - **Standalone tasks** not tied to decisions (new `PatientTask` entity)
- Shows: task/follow-up description, assignee, due date, overdue status
- Checkbox interaction to mark complete inline

#### Recent Decisions (bottom-right)
- Decisions from cases involving this patient (existing `app.decisions` via case → patient relationship)
- Shows: recommendation text, decision_type, status badge, source case/session, date, proposer
- Vote summary (3 agree, 1 abstain)
- Links back to the case and session for full context

#### Empty States
When a quadrant has no data (e.g., new patient with no flags):
- Active Problems: "No active conditions recorded." with link to Timeline
- Flagged Findings: "No flags raised. Flag a finding from any data view to see it here."
- Pending Actions: "No pending tasks or follow-ups."
- Recent Decisions: "No case decisions yet. Create a case to start collaborating."

### 2. Inline Action System

Every data point across all views gains two interaction layers:

#### Quick Path: Context Menu
- Primary trigger: three-dot action button on any data row (always visible on hover)
- Secondary trigger: right-click on data row (progressive enhancement; calls `preventDefault()` only on recognized data rows, falls through to browser default otherwise)
- Actions: Flag for review, Add to discussion, Create task, Annotate
- Each action opens a minimal inline form (no modal, no page navigation)
- The action is anchored to the specific data point (variant, lab value, imaging study)

#### Power Path: Select-and-Act Toolbar
- Checkbox selection on data rows (available in Labs, Genomics, Visits, Notes)
- Floating toolbar appears when 1+ items selected
- Batch actions: Discuss selected, Flag selected, Export selected
- "Add to Presentation" deferred to future Presentation Builder feature (not in initial release)

### 3. Context-Sensitive Collaboration Panel

A right-side slide-out panel (approximately 320px wide) triggered by:
- Clicking "Collaborate" button in tab bar
- Any inline action that requires team interaction (discuss, assign task)
- Keyboard shortcut (Cmd/Ctrl + Shift + C)

#### Panel Behavior
- **Adapts to current view tab**: On Genomics tab, panel filters to genomic-related threads. On Labs, shows measurement-related flags and discussions. On Briefing, shows all.
- **Persistent within session**: Opening the panel on one tab keeps it open when switching tabs (content re-filters).
- **Does not replace Session/Case pages**: The panel shows collaboration *for this patient*. Session-level coordination (multi-case agenda, participant roster) stays on the Session page.

#### Panel Tabs
| Tab | Content |
|-----|---------|
| Discuss | Threaded discussions filtered by current data domain (from `case_discussions` where `patient_id` matches). Quick-compose at bottom. |
| Tasks | Pending follow-ups (from `app.follow_ups` via decisions) and standalone tasks (`patient_tasks`), filtered by domain. Create task inline. |
| Flags | Active flags with severity, source data link, and resolve action. |
| Decisions | Decisions from `app.decisions` involving this patient, chronological. Shows status, vote summary, linked follow-ups. |

#### Anchoring

Discussions, flags, and tasks are anchored to specific data points via a `record_ref` field. The format is `{domain}:{primary_key}`, where domain matches the standardized vocabulary:

| Domain | Prefix | Example |
|--------|--------|---------|
| condition | `condition:` | `condition:42` |
| medication | `medication:` | `medication:108` |
| procedure | `procedure:` | `procedure:55` |
| measurement | `measurement:` | `measurement:231` |
| observation | `observation:` | `observation:17` |
| genomic | `genomic:` | `genomic:89` |
| imaging | `imaging:` | `imaging:12` |
| general | `general:` | `general:0` (not anchored to a specific record) |

This enables:
- Filtering panel content by current view
- Showing annotation indicators on data rows ("2 threads" badge on a variant)
- Navigating from a discussion to the exact data point it references

Backend validation: a `RecordRefValidator` helper rejects malformed record_refs at the controller level.

### 4. Session Page (Redesigned)

Sessions (`app.clinical_sessions`) become the primary multi-patient meeting surface. They already have the right structure: ordered cases, time allotments, participant roles, and status tracking.

#### Session Header (Existing, Enhanced)
- Title, session_type, status (existing)
- scheduled_at, duration_minutes (existing)
- Participant roster with roles: moderator, presenter, reviewer, observer (existing `app.session_participants`)

#### Case Agenda (Existing `app.session_cases`, Enhanced)
- Ordered list of cases to be reviewed in this session (existing `order` field)
- Per case: patient name + MRN (via `case.patient`), one-line summary, flag count for that patient, presenter, time allotment
- "Open Patient" link navigates to patient page
- Status tracking per case: pending → presenting → discussed → skipped (existing)
- Drag-to-reorder for agenda sequencing (new UI)
- "Add Case" to include new cases in the review (new UI)

#### Decisions Log (Enhanced)
- Decisions captured during/after the session (existing `app.decisions` with `session_id`)
- Each decision linked to a specific case (and thus patient)
- Full voting workflow: propose → vote (agree/disagree/abstain) → finalize (existing)
- Follow-ups assigned from decisions propagate to the linked patient's Briefing (existing data, new UI surface)

#### Case Detail Page (Simplified)
The Case Detail page remains but is simplified:
- **Kept**: Case metadata (title, specialty, urgency, clinical_question, summary), team members, documents
- **Enhanced**: Discussions and annotations gain `domain` and `record_ref` fields for anchoring
- **New**: Discussions and annotations with `record_ref` are surfaced in the patient's collaboration panel
- **Unchanged**: Cases remain single-patient (`patient_id` on `app.cases` is retained)

### Relationship Clarification: Session → Case → Patient

```
Session (tumor board meeting)
  └── SessionCase (ordered agenda item)
        └── Case (clinical case, single patient)
              ├── patient_id → ClinicalPatient
              ├── Discussions (with domain + record_ref anchoring)
              ├── Annotations (with domain + record_ref anchoring)
              └── Decisions (with patient-level surfacing)
                    ├── DecisionVotes
                    └── FollowUps (surfaced as tasks in patient Briefing)
```

Cases remain 1:1 with patients. Sessions are the multi-patient container. This preserves the existing data model while enabling the multi-patient agenda view.

## Data Model Changes

### New Table: `app.patient_flags`

```sql
CREATE TABLE app.patient_flags (
  id              bigserial PRIMARY KEY,
  patient_id      bigint NOT NULL REFERENCES clinical.patients(id),
  flagged_by      bigint NOT NULL REFERENCES app.users(id),
  domain          varchar NOT NULL, -- condition, medication, procedure, measurement, observation, genomic, imaging, general
  record_ref      varchar NOT NULL, -- e.g., "genomic:42"
  severity        varchar NOT NULL DEFAULT 'attention', -- critical, attention, informational
  title           varchar NOT NULL,
  description     text,
  resolved_at     timestamp,
  resolved_by     bigint REFERENCES app.users(id),
  created_at      timestamp NOT NULL DEFAULT now(),
  updated_at      timestamp NOT NULL DEFAULT now()
);

CREATE INDEX idx_patient_flags_patient ON app.patient_flags(patient_id);
CREATE INDEX idx_patient_flags_domain ON app.patient_flags(patient_id, domain);
CREATE INDEX idx_patient_flags_unresolved ON app.patient_flags(patient_id) WHERE resolved_at IS NULL;
```

### New Table: `app.patient_tasks`

Standalone tasks not tied to a decision. Decision-linked tasks remain as `app.follow_ups`.

```sql
CREATE TABLE app.patient_tasks (
  id              bigserial PRIMARY KEY,
  patient_id      bigint NOT NULL REFERENCES clinical.patients(id),
  created_by      bigint NOT NULL REFERENCES app.users(id),
  assigned_to     bigint REFERENCES app.users(id),
  domain          varchar, -- nullable; same vocabulary as flags
  record_ref      varchar, -- nullable; anchors to specific data point
  title           varchar NOT NULL,
  description     text,
  due_date        date,
  priority        varchar NOT NULL DEFAULT 'normal', -- low, normal, high, urgent
  status          varchar NOT NULL DEFAULT 'pending', -- pending, in_progress, completed, cancelled
  completed_at    timestamp,
  completed_by    bigint REFERENCES app.users(id),
  created_at      timestamp NOT NULL DEFAULT now(),
  updated_at      timestamp NOT NULL DEFAULT now()
);

CREATE INDEX idx_patient_tasks_patient ON app.patient_tasks(patient_id);
CREATE INDEX idx_patient_tasks_assigned ON app.patient_tasks(assigned_to) WHERE status IN ('pending', 'in_progress');
CREATE INDEX idx_patient_tasks_domain ON app.patient_tasks(patient_id, domain);
```

### Modifications to Existing Tables

**`app.decisions`** — Add columns for patient-level surfacing:
```sql
ALTER TABLE app.decisions ADD COLUMN patient_id bigint REFERENCES clinical.patients(id);
ALTER TABLE app.decisions ADD COLUMN record_refs jsonb; -- array of anchored data points, e.g., ["genomic:42", "measurement:108"]
CREATE INDEX idx_decisions_patient ON app.decisions(patient_id);
```
Migration: backfill `patient_id` from `decisions.case_id → cases.patient_id` for all existing rows.

**`app.case_discussions`** — Add anchoring fields:
```sql
ALTER TABLE app.case_discussions ADD COLUMN domain varchar;
ALTER TABLE app.case_discussions ADD COLUMN record_ref varchar;
ALTER TABLE app.case_discussions ADD COLUMN patient_id bigint REFERENCES clinical.patients(id);
CREATE INDEX idx_case_discussions_patient_domain ON app.case_discussions(patient_id, domain);
```
Migration: backfill `patient_id` from `case_discussions.case_id → cases.patient_id` for existing rows.

**`app.case_annotations`** — Add patient reference:
```sql
ALTER TABLE app.case_annotations ADD COLUMN patient_id bigint REFERENCES clinical.patients(id);
-- domain and record_ref already exist on case_annotations
-- anchored_to (jsonb) also exists but is deprecated in favor of record_ref for consistency
-- across flags, tasks, and discussions. Existing anchored_to data is preserved but not used by new UI.
CREATE INDEX idx_case_annotations_patient ON app.case_annotations(patient_id);
```
Migration: backfill `patient_id` from `case_annotations.case_id → cases.patient_id`.

**`app.follow_ups`** — Add patient-level context:
```sql
ALTER TABLE app.follow_ups ADD COLUMN patient_id bigint REFERENCES clinical.patients(id);
CREATE INDEX idx_follow_ups_patient ON app.follow_ups(patient_id) WHERE status IN ('pending', 'in_progress');
```
Migration: backfill `patient_id` from `follow_ups.decision_id → decisions.case_id → cases.patient_id`.

### Tables NOT Changed

- `app.cases` — `patient_id` column retained. Cases remain single-patient.
- `app.clinical_sessions` — No schema changes; UI redesign only.
- `app.session_cases` — No schema changes; UI redesign only.
- `app.session_participants` — No schema changes.
- `app.decision_votes` — No schema changes.

## Authorization Model

All new entities use the existing Spatie RBAC system. Permissions are scoped by team membership (users who are on the patient's case team or the session's participant list).

| Entity | Create | Read | Update/Resolve | Delete |
|--------|--------|------|----------------|--------|
| PatientFlag | Any authenticated user with access to the patient | Any authenticated user with access to the patient | Creator, or any team member on a case for this patient | Creator only, or admin |
| PatientTask | Any authenticated user with access to the patient | Any authenticated user with access to the patient | Assignee (status changes), Creator (reassign, edit) | Creator only, or admin |
| Decisions | Existing permissions (case team members) | Any authenticated user with access to the patient | Existing permissions | Existing permissions |
| Follow-ups | Existing permissions (via decision) | Any user with access to the patient (for Briefing) | Assignee (status), decision proposer (edit) | Existing permissions |

"Access to the patient" means: the user is a member of at least one case team for this patient, OR has a role with global patient access (admin, coordinator).

## Frontend Component Architecture

### New Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `PatientBriefing` | patient-profile/components/ | Four-quadrant briefing dashboard |
| `ActiveProblemsList` | patient-profile/components/ | Active conditions + treatments |
| `FlaggedFindings` | patient-profile/components/ | Flagged items with severity |
| `PendingActions` | patient-profile/components/ | Combined follow-ups + standalone tasks |
| `RecentDecisions` | patient-profile/components/ | Decisions with vote summary and status |
| `CollaborationPanel` | patient-profile/components/ | Slide-out right panel |
| `PanelDiscussionTab` | patient-profile/components/ | Filtered discussion threads |
| `PanelTasksTab` | patient-profile/components/ | Filtered follow-ups + tasks |
| `PanelFlagsTab` | patient-profile/components/ | Filtered flags |
| `PanelDecisionsTab` | patient-profile/components/ | Filtered decisions with voting |
| `InlineActionMenu` | patient-profile/components/ | Three-dot menu + right-click context menu |
| `SelectActToolbar` | patient-profile/components/ | Floating toolbar for batch actions |
| `SessionAgenda` | sessions/components/ | Multi-case ordered agenda |
| `SessionDecisionLog` | sessions/components/ | Per-case decision capture with voting |

### Modified Components

| Component | Changes |
|-----------|---------|
| `PatientProfilePage` | Add Briefing as default tab, integrate CollaborationPanel, remove Eras tab |
| `PatientDemographicsCard` | Compact to single bar (remove mini-stats, they move to Briefing) |
| `PatientGenomicsTab` | Add checkbox selection, inline action triggers, annotation indicators |
| `PatientLabPanel` | Add checkbox selection, inline action triggers |
| `PatientNotesTab` | Add inline action triggers |
| `PatientVisitView` | Add inline action triggers |
| `PatientImagingTab` | Add inline action triggers |
| `CaseDetailPage` | Simplify: keep metadata + team + documents, enhance discussions/annotations with anchoring |

### New Hooks

| Hook | Purpose |
|------|---------|
| `usePatientFlags(patientId, domain?)` | Fetch/create/resolve flags |
| `usePatientTasks(patientId, domain?)` | Fetch/create/update standalone tasks |
| `usePatientFollowUps(patientId, domain?)` | Fetch follow-ups for this patient across all decisions |
| `usePatientDecisions(patientId)` | Fetch decisions involving this patient |
| `usePatientCollaboration(patientId, domain?)` | Aggregate: discussions + tasks + follow-ups + flags + decisions (limited to 10 most recent per type) |
| `useSessionAgenda(sessionId)` | Fetch/reorder session cases |

### API Endpoints

All patient endpoints use the existing route namespace (`/api/patients`).

```
# Patient Flags
GET    /api/patients/{id}/flags?domain={domain}&resolved={bool}
POST   /api/patients/{id}/flags
PATCH  /api/flags/{id}                    (resolve, update)
DELETE /api/flags/{id}

# Patient Tasks (standalone, not decision follow-ups)
GET    /api/patients/{id}/tasks?domain={domain}&status={status}
POST   /api/patients/{id}/tasks
PATCH  /api/tasks/{id}                    (status update, reassign)
DELETE /api/tasks/{id}

# Patient Collaboration (panel aggregate)
GET    /api/patients/{id}/collaboration?domain={domain}
       Returns: {
         discussions: CaseDiscussion[] (max 10, most recent, filtered by patient + domain),
         tasks: PatientTask[] (max 10, pending/in_progress),
         follow_ups: FollowUp[] (max 10, pending/in_progress, for this patient),
         flags: PatientFlag[] (max 10, unresolved),
         decisions: Decision[] (max 10, most recent)
       }

# Patient Decisions (read-only convenience, actual CRUD through /api/cases/{id}/decisions)
GET    /api/patients/{id}/decisions

# Session Agenda (uses existing session routes, enhanced)
GET    /api/sessions/{id}/cases           (existing, returns ordered session_cases with patient data)
POST   /api/sessions/{id}/cases           (add case to agenda)
PATCH  /api/sessions/{id}/cases/{caseId}  (reorder, update time allotment)
DELETE /api/sessions/{id}/cases/{caseId}  (remove from agenda)

# Existing endpoints unchanged
GET    /api/cases/{id}/decisions          (existing)
POST   /api/cases/{id}/decisions          (existing)
GET    /api/cases/{id}/discussions        (existing, gains domain/record_ref filter params)
POST   /api/cases/{id}/discussions        (existing, gains domain/record_ref fields)
```

## Migration Strategy

This redesign is additive — no existing tables are dropped, no columns removed.

### Phase 1: Schema Extensions + Briefing
- Run migrations: add `patient_id` and `record_refs` to `decisions`, `case_discussions`, `case_annotations`, `follow_ups`
- Run backfill: populate `patient_id` on existing rows via case → patient relationship
- Create `app.patient_flags` and `app.patient_tasks` tables
- Build PatientBriefing component and make it the default tab
- Build API endpoints for flags and tasks
- Build patient collaboration aggregate endpoint

### Phase 2: Inline Actions
- Build InlineActionMenu component (three-dot primary, right-click secondary)
- Build SelectActToolbar for batch operations
- Add to all data view components (Genomics, Labs, Notes, Visits, Imaging)
- Wire actions to create flags/tasks/discussions with record_ref anchoring

### Phase 3: Collaboration Panel
- Build CollaborationPanel with four tabs
- Implement context-sensitive filtering by domain and record_ref
- Add annotation indicators to data rows (badge showing thread/flag count)
- Wire panel to open from inline actions

### Phase 4: Session Agenda Enhancement
- Build SessionAgenda component with drag-to-reorder and patient flag counts
- Build SessionDecisionLog with per-case decision capture and voting
- Simplify CaseDetailPage (keep metadata + team + documents, enhance anchoring on discussions)
- Update CaseListPage to show case context within sessions

## Success Criteria

1. A clinician landing on a patient page can assess the situation in under 30 seconds without clicking any tabs.
2. A clinician can flag a concerning lab value, create a task, or start a discussion without leaving the data view they're on.
3. The collaboration panel shows only relevant context for the current view — no noise from unrelated domains.
4. A tumor board coordinator can build a multi-case session agenda and capture per-case decisions from one session page.
5. All existing patient data views (Timeline, Labs, Genomics, Imaging, Notes, Visits, Similar) remain fully functional with the new action layers added on top.
6. Existing decisions, follow-ups, discussions, and annotations are preserved and surfaced in the new UI without data loss.

## Out of Scope

- Real-time collaboration (WebSocket presence, live cursors) — future enhancement
- AI-powered auto-flagging (Abby suggesting flags based on lab trends) — future enhancement
- Presentation builder (full slide deck from selected data points) — future enhancement
- Mobile-responsive collaboration panel — desktop-first for now
- FHIR/HL7 integration for tasks/orders — Aurora tasks are internal coordination, not EMR orders
