# Case–Patient Profile Integration Design

**Date:** 2026-03-24
**Status:** Approved
**Goal:** Eliminate the navigation step between case review and patient clinical data by embedding the full patient profile inside the case detail page.

## Problem

A clinician reviewing a case at `/cases/:id` sees case metadata (title, clinical question, summary, team, documents) but must click "Open Patient" to navigate away to `/profiles/:personId` to view the patient's clinical data — labs, imaging, genomics, timeline, etc. This context switch breaks the reviewer's flow during case review.

## Solution

Replace the CaseDetailPage's Overview tab with an embedded patient profile that reuses all existing profile components. Promote case metadata (clinical question, summary, stats) into a collapsible section in the case header.

## Approach

**Compose Existing Components** — Import the existing patient profile components directly into CaseDetailPage. No new wrapper components, no component duplication, no changes to the standalone profile page.

### Why This Approach

- Single source of truth: profile components stay in `features/patient-profile/`, no duplication
- Improvements to profile components automatically appear in the case view
- Minimal new code — mostly layout and composition changes
- Existing `usePatientProfile(patientId)` hook works as-is
- If a shared `PatientProfileShell` abstraction is needed later, the composition pattern makes that refactor straightforward

## Design

### 1. Case Header Expansion

The case header currently shows: title + status/specialty/urgency badges + Edit button.

**Additions:**
- **Collapsible "Case Context" section** below the title badges, containing:
  - Clinical question (full text)
  - Summary (full text)
  - Details row: case type, created date, scheduled date (if set), creator name
  - Activity stats row: discussions count, annotations count, documents count, decisions count
- Starts **expanded** by default; collapses to a single line with a toggle chevron
- Reviewers who already know the case context can minimize it to maximize patient data viewport

**Removals from current Overview tab (moved to header):**
- Clinical question block
- Summary block
- Details grid (case type, created, scheduled, creator)
- Activity stats grid (discussions, annotations, documents, decisions)

### 2. Overview Tab → Embedded Patient Profile

When the Overview tab is active and the case has a `patient_id`, the tab renders the full patient profile experience.

**Content (top to bottom):**
1. **PatientDemographicsCard** — avatar, name, MRN, age, sex, race, deceased status
2. **View mode toggle bar** — 9 buttons: Briefing, Timeline, List, Labs, Visits, Notes, Imaging, Genomics, Similar Patients. Plus Export CSV (list view only) and Collaborate button.
3. **Active view content** — the selected view mode component:
   - Briefing → `PatientBriefing`
   - Timeline → `PatientTimeline`
   - List → domain tabs + `ClinicalEventCard` grid
   - Labs → `PatientLabPanel`
   - Visits → `PatientVisitView`
   - Notes → `PatientNotesTab`
   - Imaging → `PatientImagingTab`
   - Genomics → `PatientGenomicsTab`
   - Similar → `PatientsLikeThis`
4. **CollaborationPanel** — right sidebar, toggled via Collaborate button or Cmd/Ctrl+Shift+C

**State added to CaseDetailPage:**
- `viewMode`: "briefing" | "timeline" | "list" | "labs" | "visits" | "notes" | "imaging" | "genomics" | "similar" (default: "briefing")
- `domainTab`: "all" | "condition" | "medication" | "procedure" | "measurement" | "observation" | "visit" (for list view)
- `panelOpen`: boolean (collaboration panel visibility)
- `panelTab`: "discuss" | "tasks" | "flags" | "decisions"
- `panelRecordRef`: string | undefined

**Data fetching:**
- `usePatientProfile(clinicalCase.patient_id)` — full patient clinical data
- `usePatientStats(clinicalCase.patient_id)` — domain counts
- No new API endpoints required

### 3. No Patient Fallback

When `patient_id` is null, the Overview tab shows a centered prompt:
- Icon + "No Patient Linked" heading
- Subtext: "Link a patient to this case to view their full clinical profile here."
- "Link Patient" button opens the CaseForm edit modal

**Note:** The current `CaseForm` does not have a `patient_id` field. A `patient_id` number input must be added to `CaseForm` so that users can link a patient when editing a case. This is a small addition to the existing form — one new field below the summary textarea.

### 4. Component Import Strategy

**Imports added to CaseDetailPage:**
- `usePatientProfile`, `usePatientStats` from `features/patient-profile/hooks/useProfiles`
- `PatientDemographicsCard`, `PatientBriefing`, `PatientTimeline`, `PatientLabPanel`, `PatientVisitView`, `PatientNotesTab`, `PatientImagingTab`, `PatientGenomicsTab`, `PatientsLikeThis`, `ClinicalEventCard`, `CollaborationPanel` from `features/patient-profile/components/`
- Type imports: `ClinicalEvent` from `features/patient-profile/types/profile`
- `VIEW_TAB_TO_DOMAIN` from `features/patient-profile/types/collaboration`

**Deleted:**
- The `OverviewTab` function component (replaced by inline embedded profile rendering in the Overview tab's JSX)
- The "Open Patient" `<Link>` (no longer needed)

**Unchanged:**
- `DocumentsTab` — no modifications
- `CaseTeamPanel` / Team tab — no modifications
- `PatientProfilePage` — continues to work independently at `/profiles/:personId`
- All imported profile components — zero modifications, consumed as-is

**Minor modifications:**
- `CaseForm` — add a `patient_id` number input field so users can link a patient to a case
- `PatientDemographicsCard` — currently destructures only `{ patient }` despite its interface accepting `profile`, `stats`, `onDrillDown`. No changes needed for initial integration (drilldown from demographics card is not required — the view mode toggle bar serves this purpose). The unused props can be wired up in a future enhancement.

**Derived data:**
- `allEvents` and `filteredEvents` useMemo logic (currently in PatientProfilePage) is replicated in the Overview tab rendering (~10 lines). Pure derivation from profile data; extracting a shared hook would be premature.

**Utilities:**
- `downloadEventsAsCsv` (currently a standalone function in PatientProfilePage, lines 76-90) must be imported or replicated for the Export CSV button in list view. Preferred: extract to a shared utility file `features/patient-profile/utils/csvExport.ts` and import from both pages.

**Recently viewed tracking:**
- Viewing a patient through the case page should NOT update `useProfileStore`'s recently viewed list. The recently viewed feature is for the standalone profile browser, not case-embedded viewing. No `useProfileStore` import needed.

**Collaboration panel when no patient:**
- The Collaborate button and Cmd/Ctrl+Shift+C shortcut are hidden/disabled when `patient_id` is null (collaboration is patient-scoped).

### 5. Edge Cases & Behavior

**Navigation:**
- PatientDemographicsCard inside a case does NOT show "Back to Patient Profiles"
- A small "Open full profile" link (with external link icon) appears next to the patient name, linking to `/profiles/{patient_id}`

**Tab persistence:**
- Switching between case tabs (Overview/Documents/Team) preserves the patient view mode since `viewMode` state lives on CaseDetailPage

**Loading states:**
- Case data and patient profile load independently (separate TanStack Query hooks)
- Case header renders immediately; Overview tab shows spinner while profile loads

**Error state:**
- If patient profile fails to load (deleted patient, network error), the Overview tab shows an error message: "Failed to load patient profile. Patient #{id} may not exist." with a "Retry" button. Same pattern as the standalone profile page error state.

**Keyboard shortcut:**
- Cmd/Ctrl+Shift+C toggles collaboration panel (same as standalone profile)
- The `useEffect` handler checks `activeTab === "overview"` before toggling — shortcut is inert on Documents/Team tabs

**URL structure:**
- No URL changes. `/cases/15` renders everything. View mode is not reflected in URL.

**Responsive:**
- Collaboration panel pushes content left via `mr-80` (same as standalone profile)
- View mode toggle bar wraps on narrow screens (existing flex-wrap)

## Files Modified

| File | Change |
|------|--------|
| `frontend/src/features/cases/pages/CaseDetailPage.tsx` | Major rewrite: expanded header with collapsible case context, Overview tab replaced with embedded profile |
| `frontend/src/features/cases/components/CaseForm.tsx` | Add `patient_id` number input field |
| `frontend/src/features/patient-profile/utils/csvExport.ts` | New file: extract `downloadEventsAsCsv` from PatientProfilePage |
| `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx` | Import `downloadEventsAsCsv` from new utils file (remove inline function) |
| `frontend/src/features/patient-profile/components/PatientDemographicsCard.tsx` | None — consumed as-is |
| Backend | None — no new API endpoints |

## Not In Scope

- Changing the standalone patient profile page behavior (`/profiles/:personId`)
- Patient search/autocomplete in CaseForm (simple numeric `patient_id` input is sufficient for now)
- URL-based view mode state (e.g., `/cases/15?view=labs`)
- Wiring up `PatientDemographicsCard`'s unused `onDrillDown` prop (future enhancement)
- Modifying any backend endpoints or models
