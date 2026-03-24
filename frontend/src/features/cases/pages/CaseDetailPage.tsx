import { useState, useMemo, useEffect } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import {
  ArrowLeft, Pencil, Loader2, Clock,
  MessageSquare, Tag, FileText, Gavel, Users,
  Download, Trash2, Upload, ExternalLink,
  ChevronDown, ChevronUp,
  Activity, FlaskConical, Hospital, LayoutList, ScanLine, Dna, Brain, User,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
  useCase,
  useUpdateCase,
  useCaseDocuments,
  useUploadDocument,
  useDeleteDocument,
} from "../hooks/useCases";
import { CaseForm } from "../components/CaseForm";
import { CaseTeamPanel } from "../components/CaseTeamPanel";
import type { UpdateCaseData } from "../types/case";
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

// ── Color maps ───────────────────────────────────────────────────────────────

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  draft:     { bg: "#2A2A6020", text: "#7A8298" },
  active:    { bg: "#2DD4BF15", text: "#2DD4BF" },
  in_review: { bg: "#60A5FA15", text: "#60A5FA" },
  closed:    { bg: "#4A506815", text: "#4A5068" },
  archived:  { bg: "#2A2A6015", text: "#4A5068" },
};

const SPECIALTY_COLORS: Record<string, string> = {
  oncology:        "#F0607A",
  surgical:        "#60A5FA",
  rare_disease:    "#A78BFA",
  complex_medical: "#F59E0B",
};

const URGENCY_COLORS: Record<string, string> = {
  routine:  "#2DD4BF",
  urgent:   "#F59E0B",
  emergent: "#F0607A",
};

// ── Tab definitions ──────────────────────────────────────────────────────────

const TABS = [
  { id: "overview",   label: "Overview",   icon: <Clock size={14} /> },
  { id: "documents",  label: "Documents",  icon: <FileText size={14} /> },
  { id: "team",       label: "Team",       icon: <Users size={14} /> },
];

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

// ── Documents tab content ────────────────────────────────────────────────────

function DocumentsTab({ caseId }: { caseId: number }) {
  const { data: documents, isLoading } = useCaseDocuments(caseId);
  const uploadDoc = useUploadDocument();
  const deleteDoc = useDeleteDocument();
  const [docType, setDocType] = useState("clinical_report");
  const [description, setDescription] = useState("");

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    uploadDoc.mutate(
      {
        caseId,
        file,
        documentType: docType,
        description: description.trim() || undefined,
      },
      {
        onSuccess: () => {
          setDescription("");
          e.target.value = "";
        },
      },
    );
  };

  const DOC_TYPES = [
    "clinical_report",
    "imaging",
    "pathology_report",
    "lab_results",
    "consent_form",
    "referral_letter",
    "other",
  ];

  const formatSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <span className="text-sm text-[#4A5068]">Loading documents...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Document list */}
      {(documents ?? []).length > 0 ? (
        <div className="space-y-2">
          {(documents ?? []).map((doc) => (
            <div
              key={doc.id}
              className="flex items-center justify-between rounded-lg border border-[#1C1C48] bg-[#16163A] p-3"
            >
              <div className="flex items-center gap-3">
                <FileText size={16} className="text-[#4A5068]" />
                <div>
                  <p className="text-sm font-medium text-[#B4BAC8]">
                    {doc.filename}
                  </p>
                  <div className="flex items-center gap-2 text-[10px] text-[#4A5068]">
                    <span className="rounded bg-[#1C1C48] px-1.5 py-0.5 font-['IBM_Plex_Mono',monospace]">
                      {doc.document_type.replace(/_/g, " ")}
                    </span>
                    <span>&middot;</span>
                    <span className="font-['IBM_Plex_Mono',monospace]">
                      {formatSize(doc.size)}
                    </span>
                    {doc.uploader && (
                      <>
                        <span>&middot;</span>
                        <span>{doc.uploader.name}</span>
                      </>
                    )}
                  </div>
                </div>
              </div>
              <div className="flex items-center gap-1">
                <a
                  href={doc.filepath}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#1C1C48] hover:text-[#7A8298]"
                  title="Download"
                >
                  <Download size={14} />
                </a>
                <button
                  type="button"
                  onClick={() => deleteDoc.mutate(doc.id)}
                  disabled={deleteDoc.isPending}
                  className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#00D68F15] hover:text-[#F0607A]"
                  title="Delete"
                >
                  <Trash2 size={14} />
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
          <FileText size={24} className="mb-2 text-[#4A5068]" />
          <p className="text-sm text-[#7A8298]">No documents uploaded</p>
        </div>
      )}

      {/* Upload form */}
      <div className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-4">
        <h4 className="mb-3 text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
          Upload Document
        </h4>
        <div className="grid grid-cols-2 gap-3 mb-3">
          <div className="form-group">
            <label htmlFor="doc-type" className="form-label">
              Document Type
            </label>
            <select
              id="doc-type"
              value={docType}
              onChange={(e) => setDocType(e.target.value)}
              className="form-input"
            >
              {DOC_TYPES.map((t) => (
                <option key={t} value={t}>
                  {t.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase())}
                </option>
              ))}
            </select>
          </div>
          <div className="form-group">
            <label htmlFor="doc-desc" className="form-label">
              Description (optional)
            </label>
            <input
              id="doc-desc"
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Brief description..."
              className="form-input"
            />
          </div>
        </div>
        <label
          htmlFor="doc-file"
          className={cn(
            "flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-[#2A2A60] py-6 transition-colors",
            "hover:border-[#2DD4BF]/30 hover:bg-[#10102A]",
            uploadDoc.isPending && "pointer-events-none opacity-50",
          )}
        >
          <Upload size={16} className="text-[#4A5068]" />
          <span className="text-sm text-[#7A8298]">
            {uploadDoc.isPending ? "Uploading..." : "Click to select a file"}
          </span>
          <input
            id="doc-file"
            type="file"
            onChange={handleFileUpload}
            className="hidden"
            disabled={uploadDoc.isPending}
          />
        </label>
      </div>
    </div>
  );
}

// ── Main page ────────────────────────────────────────────────────────────────

export default function CaseDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const caseId = parseInt(id ?? "0", 10);

  const { data: clinicalCase, isLoading } = useCase(caseId);
  const updateCase = useUpdateCase();

  const [activeTab, setActiveTab] = useState("overview");
  const [showEditForm, setShowEditForm] = useState(false);
  const [contextCollapsed, setContextCollapsed] = useState(false);

  // Patient profile state (for Overview tab)
  const [viewMode, setViewMode] = useState<ViewMode>("briefing");
  const [domainTab, setDomainTab] = useState<DomainTab>("all");
  const [panelOpen, setPanelOpen] = useState(false);
  const [panelTab] = useState<"discuss" | "tasks" | "flags" | "decisions">("discuss");
  const [panelRecordRef, _setPanelRecordRef] = useState<string | undefined>();

  // Fetch patient profile when case has a patient_id
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

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={24} className="animate-spin text-[#4A5068]" />
      </div>
    );
  }

  if (!clinicalCase) {
    return (
      <div className="flex flex-col items-center justify-center py-24">
        <h2 className="text-lg font-semibold text-[#E8ECF4]">Case not found</h2>
        <p className="mt-1 text-sm text-[#7A8298]">
          The case you are looking for does not exist.
        </p>
        <button
          type="button"
          onClick={() => navigate("/cases")}
          className="mt-4 inline-flex items-center gap-2 rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm text-[#7A8298] transition-colors hover:text-[#B4BAC8]"
        >
          <ArrowLeft size={14} />
          Back to Cases
        </button>
      </div>
    );
  }

  const statusColors = STATUS_COLORS[clinicalCase.status] ?? { bg: "#2A2A6020", text: "#7A8298" };
  const specialtyColor = SPECIALTY_COLORS[clinicalCase.specialty] ?? "#7A8298";
  const urgencyColor = URGENCY_COLORS[clinicalCase.urgency] ?? "#7A8298";

  const handleUpdate = (data: UpdateCaseData) => {
    updateCase.mutate(
      { id: caseId, data },
      { onSuccess: () => setShowEditForm(false) },
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <button
          type="button"
          onClick={() => navigate("/cases")}
          className="mb-4 inline-flex items-center gap-1.5 text-xs text-[#4A5068] transition-colors hover:text-[#7A8298]"
        >
          <ArrowLeft size={12} />
          Back to Cases
        </button>

        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold text-[#E8ECF4]">
              {clinicalCase.title}
            </h1>
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <span
                className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                style={{ backgroundColor: statusColors.bg, color: statusColors.text }}
              >
                {clinicalCase.status.replace(/_/g, " ")}
              </span>
              <span
                className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                style={{ backgroundColor: `${specialtyColor}15`, color: specialtyColor }}
              >
                {clinicalCase.specialty.replace(/_/g, " ")}
              </span>
              <span
                className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                style={{ backgroundColor: `${urgencyColor}15`, color: urgencyColor }}
              >
                <span
                  className="inline-block h-1.5 w-1.5 rounded-full"
                  style={{ backgroundColor: urgencyColor }}
                />
                {clinicalCase.urgency}
              </span>
            </div>
          </div>

          <button
            type="button"
            onClick={() => setShowEditForm(true)}
            className="inline-flex items-center gap-2 rounded-lg border border-[#222256] bg-[#10102A] px-3 py-2 text-sm text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8]"
          >
            <Pencil size={14} />
            Edit
          </button>
        </div>
      </div>

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

      {/* Tab bar */}
      <div className="tab-bar" role="tablist">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            className={cn("tab-item", activeTab === tab.id && "active")}
            onClick={() => setActiveTab(tab.id)}
            role="tab"
            aria-selected={activeTab === tab.id}
          >
            <span className="mr-2 inline-flex">{tab.icon}</span>
            {tab.label}
          </button>
        ))}
      </div>

      {/* Tab content */}
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
      {activeTab === "documents" && <DocumentsTab caseId={caseId} />}
      {activeTab === "team" && (
        <CaseTeamPanel
          caseId={caseId}
          createdBy={clinicalCase.created_by}
          teamMembers={clinicalCase.team_members ?? []}
        />
      )}

      {/* Edit modal */}
      {showEditForm && (
        <CaseForm
          clinicalCase={clinicalCase}
          isPending={updateCase.isPending}
          onSubmit={handleUpdate}
          onClose={() => setShowEditForm(false)}
        />
      )}
    </div>
  );
}
