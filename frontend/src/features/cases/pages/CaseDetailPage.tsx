import { useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  ArrowLeft, Pencil, Loader2, Clock,
  MessageSquare, Tag, FileText, Gavel, Users,
  Download, Trash2, Upload,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
  useCase,
  useUpdateCase,
  useCaseDocuments,
  useUploadDocument,
  useDeleteDocument,
} from "../hooks/useCases";
import { useDecisions } from "../../collaboration/hooks/useDecisions";
import { CaseForm } from "../components/CaseForm";
import { CaseDiscussionThread } from "../components/CaseDiscussionThread";
import { CaseAnnotationPanel } from "../components/CaseAnnotationPanel";
import { CaseTeamPanel } from "../components/CaseTeamPanel";
import { DecisionCapture } from "../../collaboration/components/DecisionCapture";
import type { UpdateCaseData } from "../types/case";

// ── Color maps ───────────────────────────────────────────────────────────────

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  draft:     { bg: "#3A3A4220", text: "#8A857D" },
  active:    { bg: "#2DD4BF15", text: "#2DD4BF" },
  in_review: { bg: "#60A5FA15", text: "#60A5FA" },
  closed:    { bg: "#5A565015", text: "#5A5650" },
  archived:  { bg: "#3A3A4215", text: "#5A5650" },
};

const SPECIALTY_COLORS: Record<string, string> = {
  oncology:        "#E85A6B",
  surgical:        "#60A5FA",
  rare_disease:    "#A78BFA",
  complex_medical: "#F59E0B",
};

const URGENCY_COLORS: Record<string, string> = {
  routine:  "#2DD4BF",
  urgent:   "#F59E0B",
  emergent: "#E85A6B",
};

// ── Tab definitions ──────────────────────────────────────────────────────────

const TABS = [
  { id: "overview",    label: "Overview",    icon: <Clock size={14} /> },
  { id: "discussion",  label: "Discussion",  icon: <MessageSquare size={14} /> },
  { id: "annotations", label: "Annotations", icon: <Tag size={14} /> },
  { id: "documents",   label: "Documents",   icon: <FileText size={14} /> },
  { id: "decisions",   label: "Decisions",   icon: <Gavel size={14} /> },
  { id: "team",        label: "Team",        icon: <Users size={14} /> },
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
        <span className="text-sm text-[#5A5650]">Loading documents...</span>
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
              className="flex items-center justify-between rounded-lg border border-[#232328] bg-[#1A1A1E] p-3"
            >
              <div className="flex items-center gap-3">
                <FileText size={16} className="text-[#5A5650]" />
                <div>
                  <p className="text-sm font-medium text-[#C5C0B8]">
                    {doc.filename}
                  </p>
                  <div className="flex items-center gap-2 text-[10px] text-[#5A5650]">
                    <span className="rounded bg-[#232328] px-1.5 py-0.5 font-['IBM_Plex_Mono',monospace]">
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
                  className="flex h-7 w-7 items-center justify-center rounded-md text-[#5A5650] transition-colors hover:bg-[#232328] hover:text-[#8A857D]"
                  title="Download"
                >
                  <Download size={14} />
                </a>
                <button
                  type="button"
                  onClick={() => deleteDoc.mutate(doc.id)}
                  disabled={deleteDoc.isPending}
                  className="flex h-7 w-7 items-center justify-center rounded-md text-[#5A5650] transition-colors hover:bg-[#9B1B3015] hover:text-[#E85A6B]"
                  title="Delete"
                >
                  <Trash2 size={14} />
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#323238] bg-[#151518] py-12">
          <FileText size={24} className="mb-2 text-[#5A5650]" />
          <p className="text-sm text-[#8A857D]">No documents uploaded</p>
        </div>
      )}

      {/* Upload form */}
      <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
        <h4 className="mb-3 text-xs font-semibold uppercase tracking-wider text-[#8A857D]">
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
            "flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-[#323238] py-6 transition-colors",
            "hover:border-[#2DD4BF]/30 hover:bg-[#151518]",
            uploadDoc.isPending && "pointer-events-none opacity-50",
          )}
        >
          <Upload size={16} className="text-[#5A5650]" />
          <span className="text-sm text-[#8A857D]">
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

// ── Overview tab content ─────────────────────────────────────────────────────

function OverviewTab({
  clinicalCase,
}: {
  clinicalCase: NonNullable<ReturnType<typeof useCase>["data"]>;
}) {
  return (
    <div className="space-y-6">
      {/* Clinical question */}
      {clinicalCase.clinical_question && (
        <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
          <h4 className="mb-2 text-xs font-semibold uppercase tracking-wider text-[#8A857D]">
            Clinical Question
          </h4>
          <p className="text-sm text-[#C5C0B8] whitespace-pre-wrap">
            {clinicalCase.clinical_question}
          </p>
        </div>
      )}

      {/* Summary */}
      {clinicalCase.summary && (
        <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
          <h4 className="mb-2 text-xs font-semibold uppercase tracking-wider text-[#8A857D]">
            Summary
          </h4>
          <p className="text-sm text-[#C5C0B8] whitespace-pre-wrap">
            {clinicalCase.summary}
          </p>
        </div>
      )}

      {/* Details grid */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
          <p className="text-[10px] font-semibold uppercase tracking-wider text-[#5A5650]">
            Case Type
          </p>
          <p className="mt-1 text-sm font-medium text-[#C5C0B8]">
            {clinicalCase.case_type.replace(/_/g, " ")}
          </p>
        </div>
        <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
          <p className="text-[10px] font-semibold uppercase tracking-wider text-[#5A5650]">
            Created
          </p>
          <p className="mt-1 text-sm font-medium text-[#C5C0B8] font-['IBM_Plex_Mono',monospace]">
            {new Date(clinicalCase.created_at).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
            })}
          </p>
        </div>
        {clinicalCase.scheduled_at && (
          <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
            <p className="text-[10px] font-semibold uppercase tracking-wider text-[#5A5650]">
              Scheduled
            </p>
            <p className="mt-1 text-sm font-medium text-[#C5C0B8] font-['IBM_Plex_Mono',monospace]">
              {new Date(clinicalCase.scheduled_at).toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
              })}
            </p>
          </div>
        )}
        <div className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4">
          <p className="text-[10px] font-semibold uppercase tracking-wider text-[#5A5650]">
            Creator
          </p>
          <p className="mt-1 text-sm font-medium text-[#C5C0B8]">
            {clinicalCase.creator?.name ?? `User #${clinicalCase.created_by}`}
          </p>
        </div>
      </div>

      {/* Activity stats */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="flex items-center gap-2 rounded-lg border border-[#232328] bg-[#1A1A1E] p-3">
          <MessageSquare size={14} className="text-[#5A5650]" />
          <span className="text-xs text-[#8A857D]">Discussions</span>
          <span className="ml-auto font-['IBM_Plex_Mono',monospace] text-sm font-semibold text-[#C5C0B8]">
            {clinicalCase.discussions_count ?? 0}
          </span>
        </div>
        <div className="flex items-center gap-2 rounded-lg border border-[#232328] bg-[#1A1A1E] p-3">
          <Tag size={14} className="text-[#5A5650]" />
          <span className="text-xs text-[#8A857D]">Annotations</span>
          <span className="ml-auto font-['IBM_Plex_Mono',monospace] text-sm font-semibold text-[#C5C0B8]">
            {clinicalCase.annotations_count ?? 0}
          </span>
        </div>
        <div className="flex items-center gap-2 rounded-lg border border-[#232328] bg-[#1A1A1E] p-3">
          <FileText size={14} className="text-[#5A5650]" />
          <span className="text-xs text-[#8A857D]">Documents</span>
          <span className="ml-auto font-['IBM_Plex_Mono',monospace] text-sm font-semibold text-[#C5C0B8]">
            {clinicalCase.documents_count ?? 0}
          </span>
        </div>
        <div className="flex items-center gap-2 rounded-lg border border-[#232328] bg-[#1A1A1E] p-3">
          <Gavel size={14} className="text-[#5A5650]" />
          <span className="text-xs text-[#8A857D]">Decisions</span>
          <span className="ml-auto font-['IBM_Plex_Mono',monospace] text-sm font-semibold text-[#C5C0B8]">
            {clinicalCase.decisions_count ?? 0}
          </span>
        </div>
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
  const { data: decisionsData } = useDecisions({ case_id: caseId });

  const [activeTab, setActiveTab] = useState("overview");
  const [showEditForm, setShowEditForm] = useState(false);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={24} className="animate-spin text-[#5A5650]" />
      </div>
    );
  }

  if (!clinicalCase) {
    return (
      <div className="flex flex-col items-center justify-center py-24">
        <h2 className="text-lg font-semibold text-[#F0EDE8]">Case not found</h2>
        <p className="mt-1 text-sm text-[#8A857D]">
          The case you are looking for does not exist.
        </p>
        <button
          type="button"
          onClick={() => navigate("/cases")}
          className="mt-4 inline-flex items-center gap-2 rounded-lg border border-[#2A2A30] bg-[#151518] px-4 py-2 text-sm text-[#8A857D] transition-colors hover:text-[#C5C0B8]"
        >
          <ArrowLeft size={14} />
          Back to Cases
        </button>
      </div>
    );
  }

  const statusColors = STATUS_COLORS[clinicalCase.status] ?? { bg: "#3A3A4220", text: "#8A857D" };
  const specialtyColor = SPECIALTY_COLORS[clinicalCase.specialty] ?? "#8A857D";
  const urgencyColor = URGENCY_COLORS[clinicalCase.urgency] ?? "#8A857D";

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
          className="mb-4 inline-flex items-center gap-1.5 text-xs text-[#5A5650] transition-colors hover:text-[#8A857D]"
        >
          <ArrowLeft size={12} />
          Back to Cases
        </button>

        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold text-[#F0EDE8]">
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
            className="inline-flex items-center gap-2 rounded-lg border border-[#2A2A30] bg-[#151518] px-3 py-2 text-sm text-[#8A857D] transition-colors hover:border-[#3A3A42] hover:text-[#C5C0B8]"
          >
            <Pencil size={14} />
            Edit
          </button>
        </div>
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
      {activeTab === "overview" && <OverviewTab clinicalCase={clinicalCase} />}
      {activeTab === "discussion" && <CaseDiscussionThread caseId={caseId} />}
      {activeTab === "annotations" && <CaseAnnotationPanel caseId={caseId} />}
      {activeTab === "documents" && <DocumentsTab caseId={caseId} />}
      {activeTab === "decisions" && (
        <DecisionCapture
          caseId={caseId}
          decisions={decisionsData?.data ?? []}
        />
      )}
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
