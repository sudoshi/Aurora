import { useState, useMemo } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  Loader2,
  LayoutList,
  Activity,
  FlaskConical,
  Hospital,
  Download,
  FileText,
  Clock,
  User,
  X,
  Brain,
  Search,
  ChevronLeft,
  ChevronRight,
  ScanLine,
  Dna,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { usePatients, usePatientProfile, usePatientStats, usePatientSearch } from "../hooks/useProfiles";
import { PatientDemographicsCard } from "../components/PatientDemographicsCard";
import { PatientTimeline } from "../components/PatientTimeline";
import { ClinicalEventCard } from "../components/ClinicalEventCard";
import { PatientBriefing } from "../components/PatientBriefing";
import { PatientLabPanel } from "../components/PatientLabPanel";
import { PatientVisitView } from "../components/PatientVisitView";
import { PatientNotesTab } from "../components/PatientNotesTab";
import { PatientsLikeThis } from "../components/PatientsLikeThis";
import PatientImagingTab from "../components/PatientImagingTab";
import PatientGenomicsTab from "../components/PatientGenomicsTab";
import { useProfileStore } from "@/stores/profileStore";
import type { ClinicalEvent } from "../types/profile";

type ViewMode = "briefing" | "timeline" | "list" | "labs" | "visits" | "notes" | "imaging" | "genomics" | "similar";

type DomainTab =
  | "all"
  | "condition"
  | "medication"
  | "procedure"
  | "measurement"
  | "observation"
  | "visit";

const DOMAIN_TABS: { key: DomainTab; label: string }[] = [
  { key: "all", label: "All" },
  { key: "condition", label: "Conditions" },
  { key: "medication", label: "Medications" },
  { key: "procedure", label: "Procedures" },
  { key: "measurement", label: "Measurements" },
  { key: "observation", label: "Observations" },
  { key: "visit", label: "Visits" },
];

const VIEW_BUTTONS: {
  mode: ViewMode;
  icon: React.ReactNode;
  label: string;
}[] = [
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

function downloadEventsAsCsv(events: ClinicalEvent[], filename: string) {
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

export default function PatientProfilePage() {
  const { personId } = useParams<{ personId: string }>();
  const navigate = useNavigate();

  const parsedPersonId = personId ? Number(personId) : null;
  const [viewMode, setViewMode] = useState<ViewMode>("briefing");
  const [domainTab, setDomainTab] = useState<DomainTab>("all");

  const { recentProfiles, clearRecentProfiles } = useProfileStore();

  const {
    data: profile,
    isLoading: loadingProfile,
    error: profileError,
  } = usePatientProfile(parsedPersonId);

  const { data: profileStats } = usePatientStats(parsedPersonId);

  // All events combined + sorted
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
    if (!profile || !parsedPersonId) return;
    downloadEventsAsCsv(filteredEvents, `patient-${parsedPersonId}-${domainTab}.csv`);
  };

  // No personId: show patient list landing
  if (!parsedPersonId) {
    return <PatientListLanding navigate={navigate} recentProfiles={recentProfiles} clearRecentProfiles={clearRecentProfiles} />;
  }

  // Profile view
  return (
    <div className="space-y-5">
      {/* Header */}
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 min-w-0">
          <button
            type="button"
            onClick={() => navigate("/profiles")}
            className="inline-flex items-center gap-1 text-sm text-[#7A8298] hover:text-[#E8ECF4] transition-colors mb-3"
          >
            <ArrowLeft size={14} />
            Patient Profiles
          </button>
          <h1 className="text-2xl font-bold text-[#E8ECF4]">Patient Profile</h1>
          <p className="mt-1 text-sm text-[#7A8298]">Patient #{parsedPersonId}</p>
        </div>
      </div>

      {/* Loading */}
      {loadingProfile && (
        <div className="flex items-center justify-center h-64">
          <Loader2 size={24} className="animate-spin text-[#7A8298]" />
        </div>
      )}

      {/* Error */}
      {profileError && (
        <div className="flex items-center justify-center h-48">
          <div className="text-center">
            <p className="text-[#F0607A] text-sm">Failed to load patient profile</p>
            <p className="mt-1 text-xs text-[#7A8298]">
              Patient #{parsedPersonId} may not exist.
            </p>
          </div>
        </div>
      )}

      {/* Profile data */}
      {profile && (
        <>
          <PatientDemographicsCard
            patient={profile.patient}
            profile={profile}
            stats={profileStats}
            onDrillDown={(view, domain) => {
              setViewMode(view as ViewMode);
              if (domain) setDomainTab(domain as DomainTab);
            }}
          />

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
            </div>
          </div>

          {viewMode === "briefing" && profile && (
            <PatientBriefing
              patientId={Number(personId)}
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

          {viewMode === "labs" && parsedPersonId && <PatientLabPanel events={allEvents} patientId={parsedPersonId} />}
          {viewMode === "visits" && parsedPersonId && <PatientVisitView events={allEvents} patientId={parsedPersonId} />}
          {viewMode === "notes" && parsedPersonId && (
            <PatientNotesTab patientId={parsedPersonId} />
          )}

          {viewMode === "imaging" && parsedPersonId && (
            <PatientImagingTab studies={profile.imaging ?? []} patientId={parsedPersonId} />
          )}

          {viewMode === "genomics" && parsedPersonId && (
            <PatientGenomicsTab patientId={parsedPersonId} />
          )}

          {viewMode === "similar" && parsedPersonId && (
            <PatientsLikeThis patientId={parsedPersonId} />
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
        </>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Category Pill
// ---------------------------------------------------------------------------

const CATEGORY_STYLES: Record<string, { label: string; bg: string; text: string; border: string }> = {
  oncology:        { label: "Oncology",     bg: "rgba(240, 96, 122, 0.12)", text: "#F0607A", border: "rgba(240, 96, 122, 0.25)" },
  surgical:        { label: "Surgical",     bg: "rgba(34, 211, 238, 0.12)", text: "#22D3EE", border: "rgba(34, 211, 238, 0.25)" },
  rare_disease:    { label: "Rare Disease", bg: "rgba(157, 117, 248, 0.12)", text: "#A78BFA", border: "rgba(157, 117, 248, 0.25)" },
  complex_medical: { label: "Medical",      bg: "rgba(0, 214, 143, 0.12)",  text: "#00D68F", border: "rgba(0, 214, 143, 0.25)" },
};

function CategoryPill({ category }: { category: string | null }) {
  const style = category ? CATEGORY_STYLES[category] : null;
  if (!style) return <span className="text-xs text-[var(--text-ghost)]">—</span>;
  return (
    <span
      className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap"
      style={{ backgroundColor: style.bg, color: style.text, border: `1px solid ${style.border}` }}
    >
      {style.label}
    </span>
  );
}

// ---------------------------------------------------------------------------
// Patient List Landing
// ---------------------------------------------------------------------------

function PatientListLanding({
  navigate,
  recentProfiles,
  clearRecentProfiles,
}: {
  navigate: ReturnType<typeof useNavigate>;
  recentProfiles: { patientId: number; name: string; mrn: string; viewedAt: number }[];
  clearRecentProfiles: () => void;
}) {
  const [page, setPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");
  const { data: patientList, isLoading } = usePatients(page, 25);
  const { data: searchResults, isLoading: searchLoading } = usePatientSearch(searchQuery);

  const isSearching = searchQuery.trim().length >= 1;
  const displayPatients = isSearching ? searchResults : patientList?.data;
  const loading = isSearching ? searchLoading : isLoading;

  function formatAge(dob: string | null): string {
    if (!dob) return "";
    const birth = new Date(dob);
    const diff = Date.now() - birth.getTime();
    return `${Math.floor(diff / 31557600000)}y`;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4">
        <div>
          <h1 className="page-title">Patient Profiles</h1>
          <p className="page-subtitle">
            {patientList?.total ?? "..."} patients in registry
          </p>
        </div>
      </div>

      {/* Search bar */}
      <div className="relative max-w-md">
        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-ghost)]" />
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => { setSearchQuery(e.target.value); setPage(1); }}
          placeholder="Search by name, MRN, or condition..."
          className="w-full rounded-lg border border-[var(--border-default)] bg-[var(--surface-overlay)] pl-10 pr-4 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:border-[var(--border-focus)] focus:outline-none focus:ring-1 focus:ring-[var(--primary)]/30 transition-colors"
        />
        {searchQuery && (
          <button
            type="button"
            onClick={() => setSearchQuery("")}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-ghost)] hover:text-[var(--text-secondary)]"
          >
            <X size={14} />
          </button>
        )}
      </div>

      {/* Recent Profiles */}
      {!isSearching && recentProfiles.length > 0 && (
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Clock size={14} className="text-[var(--text-muted)]" />
              <h2 className="text-sm font-semibold text-[var(--text-secondary)]">Recently Viewed</h2>
            </div>
            <button
              type="button"
              onClick={clearRecentProfiles}
              className="inline-flex items-center gap-1 text-xs text-[var(--text-ghost)] hover:text-[var(--text-muted)] transition-colors"
            >
              <X size={10} />
              Clear
            </button>
          </div>
          <div className="flex gap-2 overflow-x-auto pb-1">
            {recentProfiles.map((rp) => (
              <button
                key={rp.patientId}
                type="button"
                onClick={() => navigate(`/profiles/${rp.patientId}`)}
                className="flex items-center gap-2 shrink-0 rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] px-3 py-2 text-left hover:border-[var(--primary-border)] hover:bg-[var(--primary-bg)] transition-colors"
              >
                <User size={14} className="text-[var(--primary)]" />
                <span className="text-sm font-medium text-[var(--text-primary)]">{rp.name}</span>
                <span className="text-xs text-[var(--text-muted)]">#{rp.patientId}</span>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Patient table */}
      {loading ? (
        <div className="flex items-center justify-center h-48">
          <Loader2 size={24} className="animate-spin text-[var(--text-muted)]" />
        </div>
      ) : (
        <div className="rounded-lg border border-[var(--border-default)] overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[var(--border-default)] bg-[var(--surface-raised)]">
                <th className="text-left px-4 py-3 font-semibold text-[var(--text-secondary)]">Patient</th>
                <th className="text-left px-4 py-3 font-semibold text-[var(--text-secondary)]">MRN</th>
                <th className="text-left px-4 py-3 font-semibold text-[var(--text-secondary)]">Category</th>
                <th className="text-left px-4 py-3 font-semibold text-[var(--text-secondary)]">Age</th>
                <th className="text-left px-4 py-3 font-semibold text-[var(--text-secondary)]">Sex</th>
                <th className="text-left px-4 py-3 font-semibold text-[var(--text-secondary)]">Race / Ethnicity</th>
              </tr>
            </thead>
            <tbody>
              {(displayPatients ?? []).length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-12 text-center text-[var(--text-muted)]">
                    {isSearching ? "No patients match your search" : "No patients found"}
                  </td>
                </tr>
              ) : (
                (displayPatients ?? []).map((p) => (
                  <tr
                    key={p.id}
                    onClick={() => navigate(`/profiles/${p.id}`)}
                    className="border-b border-[var(--border-default)] hover:bg-[var(--primary-bg)] cursor-pointer transition-colors"
                  >
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-[var(--primary-bg)] shrink-0">
                          <User size={14} className="text-[var(--primary)]" />
                        </div>
                        <span className="font-medium text-[var(--text-primary)]">
                          {p.last_name}, {p.first_name}
                        </span>
                      </div>
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-[var(--text-muted)]">{p.mrn}</td>
                    <td className="px-4 py-3"><CategoryPill category={p.category ?? null} /></td>
                    <td className="px-4 py-3 text-[var(--text-secondary)]">{formatAge(p.date_of_birth)}</td>
                    <td className="px-4 py-3 text-[var(--text-secondary)]">{p.sex ?? "—"}</td>
                    <td className="px-4 py-3 text-[var(--text-muted)]">
                      {[p.race, p.ethnicity].filter(Boolean).join(" / ") || "—"}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      {!isSearching && patientList && patientList.last_page > 1 && (
        <div className="flex items-center justify-between">
          <span className="text-xs text-[var(--text-muted)]">
            Page {patientList.current_page} of {patientList.last_page}
          </span>
          <div className="flex gap-1">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
              className="inline-flex items-center gap-1 rounded-md border border-[var(--border-default)] px-3 py-1.5 text-xs text-[var(--text-secondary)] hover:bg-[var(--surface-raised)] disabled:opacity-30 disabled:pointer-events-none transition-colors"
            >
              <ChevronLeft size={14} /> Prev
            </button>
            <button
              type="button"
              disabled={page >= patientList.last_page}
              onClick={() => setPage((p) => p + 1)}
              className="inline-flex items-center gap-1 rounded-md border border-[var(--border-default)] px-3 py-1.5 text-xs text-[var(--text-secondary)] hover:bg-[var(--surface-raised)] disabled:opacity-30 disabled:pointer-events-none transition-colors"
            >
              Next <ChevronRight size={14} />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
