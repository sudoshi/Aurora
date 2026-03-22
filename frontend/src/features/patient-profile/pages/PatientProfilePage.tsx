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
  GitBranch,
  FileText,
  Clock,
  User,
  X,
  Brain,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { usePatientProfile, usePatientStats } from "../hooks/useProfiles";
import { PatientDemographicsCard } from "../components/PatientDemographicsCard";
import { PatientTimeline } from "../components/PatientTimeline";
import { ClinicalEventCard } from "../components/ClinicalEventCard";
import { EraTimeline } from "../components/EraTimeline";
import { PatientSummaryStats } from "../components/PatientSummaryStats";
import { PatientLabPanel } from "../components/PatientLabPanel";
import { PatientVisitView } from "../components/PatientVisitView";
import { PatientNotesTab } from "../components/PatientNotesTab";
import { PatientsLikeThis } from "../components/PatientsLikeThis";
import { useProfileStore } from "@/stores/profileStore";
import type { ClinicalEvent } from "../types/profile";

type ViewMode = "timeline" | "list" | "labs" | "visits" | "notes" | "eras" | "similar";

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
  { mode: "timeline", icon: <Activity size={12} />, label: "Timeline" },
  { mode: "list", icon: <LayoutList size={12} />, label: "List" },
  { mode: "labs", icon: <FlaskConical size={12} />, label: "Labs" },
  { mode: "visits", icon: <Hospital size={12} />, label: "Visits" },
  { mode: "notes", icon: <FileText size={12} />, label: "Notes" },
  { mode: "eras", icon: <GitBranch size={12} />, label: "Eras" },
  { mode: "similar", icon: <Brain size={12} />, label: "Similar Patients" },
];

function formatTimeAgo(epochMs: number): string {
  const diff = Date.now() - epochMs;
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return "just now";
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

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
  const [viewMode, setViewMode] = useState<ViewMode>("timeline");
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

  const hasEras =
    (profile?.condition_eras?.length ?? 0) > 0 ||
    (profile?.drug_eras?.length ?? 0) > 0;

  const handleExportCsv = () => {
    if (!profile || !parsedPersonId) return;
    downloadEventsAsCsv(filteredEvents, `patient-${parsedPersonId}-${domainTab}.csv`);
  };

  // No personId: show search landing
  if (!parsedPersonId) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-[#F0EDE8]">Patient Profiles</h1>
          <p className="mt-1 text-sm text-[#8A857D]">
            Search by patient ID to view clinical timelines, labs, and notes
          </p>
        </div>

        {/* Recent Profiles */}
        {recentProfiles.length > 0 && (
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Clock size={14} className="text-[#8A857D]" />
                <h2 className="text-sm font-semibold text-[#C5C0B8]">Recent Profiles</h2>
                <span className="text-xs text-[#5A5650]">({recentProfiles.length})</span>
              </div>
              <button
                type="button"
                onClick={clearRecentProfiles}
                className="inline-flex items-center gap-1 text-[10px] text-[#5A5650] hover:text-[#8A857D] transition-colors"
              >
                <X size={10} />
                Clear
              </button>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
              {recentProfiles.map((rp) => (
                <button
                  key={`${rp.patientId}`}
                  type="button"
                  onClick={() => navigate(`/profiles/${rp.patientId}`)}
                  className="flex items-center gap-3 rounded-lg border border-[#232328] bg-[#151518] px-3 py-2.5 text-left hover:border-[#2DD4BF]/30 hover:bg-[#1A1A1E] transition-colors"
                >
                  <div className="flex items-center justify-center w-8 h-8 rounded-full bg-[#2DD4BF]/10 shrink-0">
                    <User size={14} className="text-[#2DD4BF]" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <span className="text-sm font-semibold text-[#2DD4BF] font-['IBM_Plex_Mono',monospace]">
                      #{rp.patientId}
                    </span>
                    <span className="text-xs text-[#8A857D] ml-2">
                      {rp.name} · MRN: {rp.mrn}
                    </span>
                    <div className="text-[10px] text-[#5A5650] mt-0.5">
                      {formatTimeAgo(rp.viewedAt)}
                    </div>
                  </div>
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    );
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
            className="inline-flex items-center gap-1 text-sm text-[#8A857D] hover:text-[#F0EDE8] transition-colors mb-3"
          >
            <ArrowLeft size={14} />
            Patient Profiles
          </button>
          <h1 className="text-2xl font-bold text-[#F0EDE8]">Patient Profile</h1>
          <p className="mt-1 text-sm text-[#8A857D]">Patient #{parsedPersonId}</p>
        </div>
      </div>

      {/* Loading */}
      {loadingProfile && (
        <div className="flex items-center justify-center h-64">
          <Loader2 size={24} className="animate-spin text-[#8A857D]" />
        </div>
      )}

      {/* Error */}
      {profileError && (
        <div className="flex items-center justify-center h-48">
          <div className="text-center">
            <p className="text-[#E85A6B] text-sm">Failed to load patient profile</p>
            <p className="mt-1 text-xs text-[#8A857D]">
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
          />

          <PatientSummaryStats
            profile={profile}
            stats={profileStats}
            onDrillDown={(view, domain) => {
              setViewMode(view as ViewMode);
              if (domain) setDomainTab(domain as DomainTab);
            }}
          />

          {/* View controls */}
          <div className="flex items-center justify-between gap-3 flex-wrap">
            <span className="text-sm font-semibold text-[#F0EDE8]">
              Clinical Events ({allEvents.length})
            </span>
            <div className="flex items-center gap-2">
              <div className="flex items-center gap-1 rounded-lg border border-[#232328] bg-[#0E0E11] p-0.5">
                {VIEW_BUTTONS.filter((b) => (b.mode !== "eras" || hasEras)).map(({ mode, icon, label }) => (
                  <button
                    key={mode}
                    type="button"
                    onClick={() => setViewMode(mode)}
                    className={cn(
                      "inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors",
                      viewMode === mode
                        ? "bg-[#2DD4BF]/10 text-[#2DD4BF]"
                        : "text-[#8A857D] hover:text-[#C5C0B8]",
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
                  className="inline-flex items-center gap-1.5 rounded-lg border border-[#323238] px-3 py-1.5 text-xs text-[#8A857D] hover:text-[#F0EDE8] hover:border-[#5A5650] transition-colors"
                >
                  <Download size={12} />
                  Export CSV
                </button>
              )}
            </div>
          </div>

          {viewMode === "timeline" && (
            <PatientTimeline
              events={allEvents}
              observationPeriods={profile.observation_periods}
            />
          )}

          {viewMode === "labs" && <PatientLabPanel events={allEvents} />}
          {viewMode === "visits" && <PatientVisitView events={allEvents} />}
          {viewMode === "notes" && parsedPersonId && (
            <PatientNotesTab patientId={parsedPersonId} />
          )}
          {viewMode === "eras" && (
            <EraTimeline
              conditionEras={profile.condition_eras ?? []}
              drugEras={profile.drug_eras ?? []}
            />
          )}

          {viewMode === "similar" && parsedPersonId && (
            <PatientsLikeThis patientId={parsedPersonId} />
          )}

          {viewMode === "list" && (
            <div className="space-y-4">
              <div className="flex items-center gap-1 border-b border-[#232328] overflow-x-auto">
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
                          : "text-[#8A857D] hover:text-[#C5C0B8]",
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
                <div className="flex items-center justify-center h-32 rounded-lg border border-dashed border-[#323238] bg-[#151518]">
                  <p className="text-sm text-[#8A857D]">No events in this category</p>
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
