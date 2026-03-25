# Case–Patient Profile Integration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Embed the full patient profile (all 9 view modes) inside the case detail page's Overview tab so clinicians can review patient data without navigating away from the case.

**Architecture:** Compose existing patient profile components directly into CaseDetailPage. Case metadata (clinical question, summary, stats) moves into a collapsible header section. No new API endpoints, no component duplication, no changes to standalone profile page.

**Tech Stack:** React 19, TypeScript, TanStack Query, Tailwind CSS, Zustand (existing stack — no new dependencies)

**Spec:** `docs/superpowers/specs/2026-03-24-case-patient-profile-integration-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `frontend/src/features/patient-profile/utils/csvExport.ts` | Create | Extract `downloadEventsAsCsv` utility |
| `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx` | Modify (line 76-90) | Import `downloadEventsAsCsv` from new utils file |
| `frontend/src/features/cases/components/CaseForm.tsx` | Modify | Add `patient_id` number input field |
| `frontend/src/features/cases/pages/CaseDetailPage.tsx` | Major rewrite | Collapsible case context header + embedded patient profile in Overview tab |

---

## Task 1: Extract CSV Export Utility

**Files:**
- Create: `frontend/src/features/patient-profile/utils/csvExport.ts`
- Modify: `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx:76-90`

- [ ] **Step 1: Create the shared utility file**

Create `frontend/src/features/patient-profile/utils/csvExport.ts`:

```typescript
import type { ClinicalEvent } from "../types/profile";

export function downloadEventsAsCsv(events: ClinicalEvent[], filename: string) {
  if (events.length === 0) return;
  const headers = ["domain", "concept_code", "concept_name", "start_date", "end_date", "value", "unit"];
  const rows = events.map((e) =>
    [e.domain, e.concept_code ?? "", `"${(e.concept_name ?? "").replace(/"/g, '""')}"`, e.start_date, e.end_date ?? "", e.value_as_string ?? e.value_numeric ?? "", e.unit ?? ""].join(","),
  );
  const csv = [headers.join(","), ...rows].join("\n");
  const blob = new Blob([csv], { type: "text/csv" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}
```

- [ ] **Step 2: Update PatientProfilePage to import from utility**

In `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx`:

Replace lines 76-90 (the `downloadEventsAsCsv` function definition) with:

```typescript
import { downloadEventsAsCsv } from "../utils/csvExport";
```

Add this import near the top of the file, after the existing imports (around line 37).

Delete the inline `downloadEventsAsCsv` function (lines 76-90).

- [ ] **Step 3: Verify the standalone profile page still works**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/patient-profile/utils/csvExport.ts frontend/src/features/patient-profile/pages/PatientProfilePage.tsx
git commit -m "refactor: extract downloadEventsAsCsv to shared utility"
```

---

## Task 2: Add patient_id Field to CaseForm

**Files:**
- Modify: `frontend/src/features/cases/components/CaseForm.tsx`

The `CreateCaseData` type already has `patient_id?: number` (see `frontend/src/features/cases/types/case.ts:99`), and the API already sends it. Only the form UI field is missing.

- [ ] **Step 1: Add patient_id state**

In `CaseForm.tsx`, after line 60 (`const [summary, setSummary] = useState(...)`), add:

```typescript
const [patientId, setPatientId] = useState(
  clinicalCase?.patient_id?.toString() ?? "",
);
```

- [ ] **Step 2: Include patient_id in form submission**

In the `handleSubmit` function (line 62-73), add `patient_id` to the `data` object. Replace:

```typescript
const data: CreateCaseData = {
  title: title.trim(),
  specialty,
  case_type: caseType,
  urgency,
  clinical_question: clinicalQuestion.trim() || undefined,
  summary: summary.trim() || undefined,
};
```

With:

```typescript
const data: CreateCaseData = {
  title: title.trim(),
  specialty,
  case_type: caseType,
  urgency,
  clinical_question: clinicalQuestion.trim() || undefined,
  summary: summary.trim() || undefined,
  patient_id: patientId.trim() ? parseInt(patientId.trim(), 10) : undefined,
};
```

- [ ] **Step 3: Add the patient_id input field to the form UI**

After the Summary `</div>` (line 208) and before the Footer `<div>` (line 210), add:

```tsx
{/* Patient ID */}
<div className="form-group">
  <label htmlFor="case-patient-id" className="form-label">
    Patient ID (optional)
  </label>
  <input
    id="case-patient-id"
    type="number"
    value={patientId}
    onChange={(e) => setPatientId(e.target.value)}
    placeholder="e.g., 154"
    className="form-input"
    min={1}
  />
</div>
```

- [ ] **Step 4: Type check**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 5: Commit**

```bash
git add frontend/src/features/cases/components/CaseForm.tsx
git commit -m "feat: add patient_id field to CaseForm"
```

---

## Task 3: Rewrite CaseDetailPage Header with Collapsible Case Context

**Files:**
- Modify: `frontend/src/features/cases/pages/CaseDetailPage.tsx`

This task modifies only the header section — the tab content changes come in Task 4.

- [ ] **Step 1: Add ChevronDown/ChevronUp imports and contextCollapsed state**

At the top of `CaseDetailPage.tsx`, replace the lucide-react import (line 3-7) with:

```typescript
import {
  ArrowLeft, Pencil, Loader2, Clock,
  MessageSquare, Tag, FileText, Gavel, Users,
  Download, Trash2, Upload, ExternalLink,
  ChevronDown, ChevronUp,
  Activity, FlaskConical, Hospital, LayoutList, ScanLine, Dna, Brain, User,
} from "lucide-react";
```

This adds `ChevronDown, ChevronUp` (for collapsible header) and the view mode icons (`Activity`, `FlaskConical`, `Hospital`, `LayoutList`, `ScanLine`, `Dna`, `Brain`, `User`) that will be used in Task 4.

Inside the `CaseDetailPage` component, after line 365 (`const [showEditForm, setShowEditForm] = useState(false);`), add:

```typescript
const [contextCollapsed, setContextCollapsed] = useState(false);
```

- [ ] **Step 2: Add the collapsible Case Context section to the header**

After the closing `</div>` of the header's badges + edit button block (after line 458, before the tab bar), insert the collapsible case context section:

```tsx
{/* Collapsible case context */}
<div className="rounded-lg border border-[#1C1C48] bg-[#16163A]">
  <button
    type="button"
    onClick={() => setContextCollapsed((prev) => !prev)}
    className="flex w-full items-center justify-between px-4 py-3 text-left"
  >
    <span className="text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
      Case Context
    </span>
    {contextCollapsed ? (
      <ChevronDown size={14} className="text-[#4A5068]" />
    ) : (
      <ChevronUp size={14} className="text-[#4A5068]" />
    )}
  </button>

  {!contextCollapsed && (
    <div className="space-y-4 border-t border-[#1C1C48] px-4 pb-4 pt-3">
      {/* Clinical question */}
      {clinicalCase.clinical_question && (
        <div>
          <h4 className="mb-1 text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
            Clinical Question
          </h4>
          <p className="text-sm text-[#B4BAC8] whitespace-pre-wrap">
            {clinicalCase.clinical_question}
          </p>
        </div>
      )}

      {/* Summary */}
      {clinicalCase.summary && (
        <div>
          <h4 className="mb-1 text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
            Summary
          </h4>
          <p className="text-sm text-[#B4BAC8] whitespace-pre-wrap">
            {clinicalCase.summary}
          </p>
        </div>
      )}

      {/* Details row */}
      <div className="flex flex-wrap items-center gap-4 text-xs text-[#7A8298]">
        <span>
          <span className="text-[#4A5068]">Type:</span>{" "}
          {clinicalCase.case_type.replace(/_/g, " ")}
        </span>
        <span>
          <span className="text-[#4A5068]">Created:</span>{" "}
          {new Date(clinicalCase.created_at).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
          })}
        </span>
        {clinicalCase.scheduled_at && (
          <span>
            <span className="text-[#4A5068]">Scheduled:</span>{" "}
            {new Date(clinicalCase.scheduled_at).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
            })}
          </span>
        )}
        <span>
          <span className="text-[#4A5068]">By:</span>{" "}
          {clinicalCase.creator?.name ?? `User #${clinicalCase.created_by}`}
        </span>
      </div>

      {/* Activity stats row */}
      <div className="flex flex-wrap items-center gap-4">
        <div className="flex items-center gap-1.5 text-xs text-[#7A8298]">
          <MessageSquare size={12} className="text-[#4A5068]" />
          <span>{clinicalCase.discussions_count ?? 0} discussions</span>
        </div>
        <div className="flex items-center gap-1.5 text-xs text-[#7A8298]">
          <Tag size={12} className="text-[#4A5068]" />
          <span>{clinicalCase.annotations_count ?? 0} annotations</span>
        </div>
        <div className="flex items-center gap-1.5 text-xs text-[#7A8298]">
          <FileText size={12} className="text-[#4A5068]" />
          <span>{clinicalCase.documents_count ?? 0} documents</span>
        </div>
        <div className="flex items-center gap-1.5 text-xs text-[#7A8298]">
          <Gavel size={12} className="text-[#4A5068]" />
          <span>{clinicalCase.decisions_count ?? 0} decisions</span>
        </div>
      </div>
    </div>
  )}
</div>
```

- [ ] **Step 3: Type check**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/cases/pages/CaseDetailPage.tsx
git commit -m "feat: add collapsible case context section to case header"
```

---

## Task 4: Replace Overview Tab with Embedded Patient Profile

**Files:**
- Modify: `frontend/src/features/cases/pages/CaseDetailPage.tsx`

This is the main integration task. Delete the old `OverviewTab` component, add profile imports and state, and render the embedded profile in the Overview tab.

- [ ] **Step 1: Add patient profile imports**

At the top of `CaseDetailPage.tsx`, add these imports after the existing imports:

```typescript
import { usePatientProfile, usePatientStats } from "@/features/patient-profile/hooks/useProfiles";
import { PatientDemographicsCard } from "@/features/patient-profile/components/PatientDemographicsCard";
import { PatientBriefing } from "@/features/patient-profile/components/PatientBriefing";
import { PatientTimeline } from "@/features/patient-profile/components/PatientTimeline";
import { PatientLabPanel } from "@/features/patient-profile/components/PatientLabPanel";
import { PatientVisitView } from "@/features/patient-profile/components/PatientVisitView";
import { PatientNotesTab } from "@/features/patient-profile/components/PatientNotesTab";
import PatientImagingTab from "@/features/patient-profile/components/PatientImagingTab";
import PatientGenomicsTab from "@/features/patient-profile/components/PatientGenomicsTab";
import { PatientsLikeThis } from "@/features/patient-profile/components/PatientsLikeThis";
import { ClinicalEventCard } from "@/features/patient-profile/components/ClinicalEventCard";
import { CollaborationPanel } from "@/features/patient-profile/components/CollaborationPanel";
import { VIEW_TAB_TO_DOMAIN } from "@/features/patient-profile/types/collaboration";
import { downloadEventsAsCsv } from "@/features/patient-profile/utils/csvExport";
import type { ClinicalEvent } from "@/features/patient-profile/types/profile";
```

Also add `useMemo, useEffect` to the React import (line 1):

```typescript
import { useState, useMemo, useEffect } from "react";
```

And add `Link` to the react-router-dom import (line 2) if not already present.

- [ ] **Step 2: Delete the old OverviewTab component**

Delete the entire `OverviewTab` function component (lines 229-352 in the current file — the section labeled `// ── Overview tab content ──`).

- [ ] **Step 3: Add view mode constants**

After the TABS array (around line 49), add:

```typescript
type ViewMode = "briefing" | "timeline" | "list" | "labs" | "visits" | "notes" | "imaging" | "genomics" | "similar";

type DomainTab = "all" | "condition" | "medication" | "procedure" | "measurement" | "observation" | "visit";

const VIEW_BUTTONS: { mode: ViewMode; icon: React.ReactNode; label: string }[] = [
  { mode: "briefing", icon: <User size={12} />, label: "Briefing" },
  { mode: "timeline", icon: <Activity size={12} />, label: "Timeline" },
  { mode: "list", icon: <LayoutList size={12} />, label: "List" },
  { mode: "labs", icon: <FlaskConical size={12} />, label: "Labs" },
  { mode: "visits", icon: <Hospital size={12} />, label: "Visits" },
  { mode: "notes", icon: <FileText size={12} />, label: "Notes" },
  { mode: "imaging", icon: <ScanLine size={12} />, label: "Imaging" },
  { mode: "genomics", icon: <Dna size={12} />, label: "Genomics" },
  { mode: "similar", icon: <Brain size={12} />, label: "Similar Patients" },
];

const DOMAIN_TABS: { key: DomainTab; label: string }[] = [
  { key: "all", label: "All" },
  { key: "condition", label: "Conditions" },
  { key: "medication", label: "Medications" },
  { key: "procedure", label: "Procedures" },
  { key: "measurement", label: "Measurements" },
  { key: "observation", label: "Observations" },
  { key: "visit", label: "Visits" },
];
```

Icons match `PatientProfilePage.tsx` exactly. All icons were imported in Task 3 Step 1.

- [ ] **Step 4: Add patient profile state and data hooks inside CaseDetailPage**

Inside the `CaseDetailPage` component, place ALL of the following code **immediately after the existing state declarations** (`activeTab`, `showEditForm`, `contextCollapsed`) and **before the early returns** (the `if (isLoading)` and `if (!clinicalCase)` blocks). React hooks cannot be called conditionally, but these hooks use internal `enabled` guards so they safely no-op when `patient_id` is null.

```typescript
// Patient profile state (for Overview tab)
const [viewMode, setViewMode] = useState<ViewMode>("briefing");
const [domainTab, setDomainTab] = useState<DomainTab>("all");
const [panelOpen, setPanelOpen] = useState(false);
const [panelTab, setPanelTab] = useState<"discuss" | "tasks" | "flags" | "decisions">("discuss");
const [panelRecordRef, _setPanelRecordRef] = useState<string | undefined>();

// Fetch patient profile when case has a patient_id
// Hooks have `enabled` guards — safe to call before clinicalCase is checked
const patientId = clinicalCase?.patient_id ?? null;
const {
  data: profile,
  isLoading: loadingProfile,
  error: profileError,
} = usePatientProfile(patientId);
const { data: profileStats } = usePatientStats(patientId);

// Derived events (same logic as PatientProfilePage)
const allEvents = useMemo(() => {
  if (!profile) return [];
  return [
    ...(profile.conditions ?? []),
    ...(profile.medications ?? []),
    ...(profile.procedures ?? []),
    ...(profile.measurements ?? []),
    ...(profile.observations ?? []),
    ...(profile.visits ?? []),
  ].sort(
    (a, b) =>
      new Date(b.start_date).getTime() - new Date(a.start_date).getTime(),
  );
}, [profile]);

const filteredEvents = useMemo(() => {
  if (domainTab === "all") return allEvents;
  return allEvents.filter((e) => e.domain === domainTab);
}, [allEvents, domainTab]);

const handleExportCsv = () => {
  if (!profile || !patientId) return;
  downloadEventsAsCsv(filteredEvents, `patient-${patientId}-${domainTab}.csv`);
};

// Keyboard shortcut: Cmd/Ctrl+Shift+C toggles collaboration panel (Overview tab only)
useEffect(() => {
  const handler = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === "c") {
      if (activeTab !== "overview" || !patientId) return;
      e.preventDefault();
      setPanelOpen((prev) => !prev);
    }
  };
  window.addEventListener("keydown", handler);
  return () => window.removeEventListener("keydown", handler);
}, [activeTab, patientId]);
```

- [ ] **Step 5: Replace the Overview tab content rendering**

Replace `{activeTab === "overview" && <OverviewTab clinicalCase={clinicalCase} />}` (line 477) with the embedded patient profile:

```tsx
{activeTab === "overview" && (
  <div className={`transition-all duration-300 ${panelOpen ? "mr-80" : ""}`}>
    {/* No patient linked */}
    {!clinicalCase.patient_id && (
      <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-16">
        <Users size={32} className="mb-3 text-[#4A5068]" />
        <h3 className="text-base font-semibold text-[#E8ECF4]">No Patient Linked</h3>
        <p className="mt-1 text-sm text-[#7A8298]">
          Link a patient to this case to view their full clinical profile here.
        </p>
        <button
          type="button"
          onClick={() => setShowEditForm(true)}
          className="mt-4 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5]"
        >
          Link Patient
        </button>
      </div>
    )}

    {/* Loading profile */}
    {clinicalCase.patient_id && loadingProfile && (
      <div className="flex items-center justify-center py-16">
        <Loader2 size={24} className="animate-spin text-[#4A5068]" />
      </div>
    )}

    {/* Profile error */}
    {clinicalCase.patient_id && profileError && (
      <div className="flex flex-col items-center justify-center py-16">
        <p className="text-sm text-[#F0607A]">Failed to load patient profile</p>
        <p className="mt-1 text-xs text-[#7A8298]">
          Patient #{clinicalCase.patient_id} may not exist.
        </p>
        <button
          type="button"
          onClick={() => window.location.reload()}
          className="mt-4 rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8]"
        >
          Retry
        </button>
      </div>
    )}

    {/* Patient profile loaded */}
    {clinicalCase.patient_id && profile && (
      <div className="space-y-5">
        {/* Demographics + open full profile link */}
        <div className="relative">
          <PatientDemographicsCard
            patient={profile.patient}
            profile={profile}
            stats={profileStats}
            onDrillDown={(view, domain) => {
              setViewMode(view as ViewMode);
              if (domain) setDomainTab(domain as DomainTab);
            }}
          />
          <Link
            to={`/profiles/${clinicalCase.patient_id}`}
            className="absolute right-3 top-3 flex items-center gap-1 text-xs text-[#7A8298] hover:text-[#2DD4BF] transition-colors"
            title="Open full profile"
          >
            <ExternalLink size={12} />
            Full profile
          </Link>
        </div>

        {/* View controls */}
        <div className="flex items-center justify-between gap-3 flex-wrap">
          <span className="text-sm font-semibold text-[#E8ECF4]">
            Clinical Events ({allEvents.length})
          </span>
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-1 rounded-lg border border-[#1C1C48] bg-[#0A0A18] p-0.5">
              {VIEW_BUTTONS.filter((b) => {
                if (b.mode === "imaging" && (profile.imaging ?? []).length === 0) return false;
                if (b.mode === "genomics" && (profile.genomics ?? []).length === 0) return false;
                return true;
              }).map(({ mode, icon, label }) => (
                <button
                  key={mode}
                  type="button"
                  onClick={() => setViewMode(mode)}
                  className={cn(
                    "inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors",
                    viewMode === mode
                      ? "bg-[#2DD4BF]/10 text-[#2DD4BF]"
                      : "text-[#7A8298] hover:text-[#B4BAC8]",
                  )}
                >
                  {icon}
                  {label}
                </button>
              ))}
            </div>
            {viewMode === "list" && (
              <button
                type="button"
                onClick={handleExportCsv}
                className="inline-flex items-center gap-1.5 rounded-lg border border-[#2A2A60] px-3 py-1.5 text-xs text-[#7A8298] hover:text-[#E8ECF4] hover:border-[#4A5068] transition-colors"
              >
                <Download size={12} />
                Export CSV
              </button>
            )}
            <button
              onClick={() => setPanelOpen((prev) => !prev)}
              className="ml-auto px-3 py-1.5 rounded text-xs font-semibold"
              style={{ background: "rgba(167,139,250,0.15)", color: "#a78bfa" }}
            >
              {panelOpen ? "Close Panel" : "Collaborate \u00BB"}
            </button>
          </div>
        </div>

        {/* Active view */}
        {viewMode === "briefing" && (
          <PatientBriefing
            patientId={clinicalCase.patient_id}
            profile={profile}
            onNavigate={(tab) => setViewMode(tab as ViewMode)}
          />
        )}

        {viewMode === "timeline" && (
          <PatientTimeline
            events={allEvents}
            observationPeriods={profile.observation_periods}
          />
        )}

        {viewMode === "labs" && (
          <PatientLabPanel events={allEvents} patientId={clinicalCase.patient_id} />
        )}

        {viewMode === "visits" && (
          <PatientVisitView events={allEvents} patientId={clinicalCase.patient_id} />
        )}

        {viewMode === "notes" && (
          <PatientNotesTab patientId={clinicalCase.patient_id} />
        )}

        {viewMode === "imaging" && (
          <PatientImagingTab studies={profile.imaging ?? []} patientId={clinicalCase.patient_id} />
        )}

        {viewMode === "genomics" && (
          <PatientGenomicsTab patientId={clinicalCase.patient_id} />
        )}

        {viewMode === "similar" && (
          <PatientsLikeThis patientId={clinicalCase.patient_id} />
        )}

        {viewMode === "list" && (
          <div className="space-y-4">
            <div className="flex items-center gap-1 border-b border-[#1C1C48] overflow-x-auto">
              {DOMAIN_TABS.map((tab) => {
                const count =
                  tab.key === "all"
                    ? allEvents.length
                    : allEvents.filter((e) => e.domain === tab.key).length;
                if (tab.key !== "all" && count === 0) return null;
                return (
                  <button
                    key={tab.key}
                    type="button"
                    onClick={() => setDomainTab(tab.key)}
                    className={cn(
                      "relative px-3 py-2 text-xs font-medium transition-colors whitespace-nowrap",
                      domainTab === tab.key
                        ? "text-[#2DD4BF]"
                        : "text-[#7A8298] hover:text-[#B4BAC8]",
                    )}
                  >
                    {tab.label} <span className="text-[10px] opacity-60">({count})</span>
                    {domainTab === tab.key && (
                      <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#2DD4BF]" />
                    )}
                  </button>
                );
              })}
            </div>

            {filteredEvents.length === 0 ? (
              <div className="flex items-center justify-center h-32 rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A]">
                <p className="text-sm text-[#7A8298]">No events in this category</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                {filteredEvents.map((event, i) => (
                  <ClinicalEventCard key={i} event={event} />
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    )}

    {/* Collaboration panel */}
    {clinicalCase.patient_id && (
      <CollaborationPanel
        patientId={clinicalCase.patient_id}
        domain={VIEW_TAB_TO_DOMAIN[viewMode]}
        isOpen={panelOpen}
        onClose={() => setPanelOpen(false)}
        initialTab={panelTab}
        initialRecordRef={panelRecordRef}
      />
    )}
  </div>
)}
```

- [ ] **Step 6: Type check**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 7: Commit**

```bash
git add frontend/src/features/cases/pages/CaseDetailPage.tsx
git commit -m "feat: embed patient profile in case detail Overview tab"
```

---

## Task 5: Smoke Test & Visual Verification

- [ ] **Step 1: Start the dev server if not running**

Run: `cd /home/smudoshi/Github/Aurora && docker compose up -d`

Check: `curl -s http://localhost:5177 | head -5` (Vite dev server responds)

- [ ] **Step 2: Verify case detail page with linked patient**

Navigate to `http://localhost:8085/cases/15` (or any case with a `patient_id`).

Verify:
- Case header shows title, badges, collapsible case context
- Case context section shows clinical question, summary, details, activity stats
- Clicking the chevron collapses/expands the case context
- Overview tab shows patient demographics card with "Full profile" link
- All 9 view mode buttons appear (except imaging/genomics if no data)
- Clicking each view mode renders the correct component
- Collaborate button opens the right panel
- Cmd/Ctrl+Shift+C toggles the panel
- Documents and Team tabs still work normally

- [ ] **Step 3: Verify case detail page without linked patient**

Navigate to any case where `patient_id` is null.

Verify:
- Overview tab shows "No Patient Linked" prompt
- "Link Patient" button opens the CaseForm modal
- CaseForm now has a "Patient ID" field
- Entering a patient_id and saving links the patient
- After save, the Overview tab loads the patient profile

- [ ] **Step 4: Verify standalone profile page still works**

Navigate to `http://localhost:8085/profiles/154`.

Verify:
- All 9 view modes work
- Export CSV works
- No regressions

- [ ] **Step 5: Verify the "Full profile" link**

On a case detail page with a linked patient, click the "Full profile" link in the demographics card.

Verify: Navigates to `/profiles/{patient_id}` standalone page.

- [ ] **Step 6: Final commit if any fixes were needed**

```bash
git add -u
git commit -m "fix: address visual/functional issues from smoke testing"
```

---

## Task 6: Deploy

- [ ] **Step 1: Build frontend**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npm run build`
Expected: Build succeeds with no errors

- [ ] **Step 2: Deploy to aurora.acumenus.net**

Run: `cd /home/smudoshi/Github/Aurora && bash deploy.sh`

- [ ] **Step 3: Verify production**

Navigate to `https://aurora.acumenus.net/cases/15` and verify the integrated case+profile experience works.

- [ ] **Step 4: Push to remote**

```bash
git push origin v2/phase-0-scaffold
```
