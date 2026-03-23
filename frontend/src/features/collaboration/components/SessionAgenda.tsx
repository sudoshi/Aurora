import { ChevronUp, ChevronDown, CheckCircle, ExternalLink, User } from "lucide-react";
import { cn } from "@/lib/utils";

// ── Types ────────────────────────────────────────────────────────────────────

interface SessionCase {
  id: number;
  session_id: number;
  case_id: number;
  order: number;
  presenter_id: number | null;
  presenter?: { id: number; name: string };
  time_allotted_minutes: number;
  status: "pending" | "presenting" | "discussed" | "skipped";
  clinical_case: {
    id: number;
    title: string;
    specialty: string;
    patient_id: number | null;
    patient?: {
      id: number;
      first_name: string;
      last_name: string;
      mrn: string;
    };
  };
}

interface SessionAgendaProps {
  sessionId: number;
  sessionCases: SessionCase[];
  onReorder?: (caseId: number, newOrder: number) => void;
  onRemove?: (caseId: number) => void;
}

// ── Status config ─────────────────────────────────────────────────────────────

const STATUS_CONFIG = {
  pending:    { label: "Pending",    borderColor: "#4A5068", bgHighlight: false },
  presenting: { label: "Presenting", borderColor: "#60A5FA", bgHighlight: true  },
  discussed:  { label: "Discussed",  borderColor: "#2DD4BF", bgHighlight: false },
  skipped:    { label: "Skipped",    borderColor: "#F0607A", bgHighlight: false },
} as const;

// ── Case row ──────────────────────────────────────────────────────────────────

function AgendaCaseRow({
  sc,
  isFirst,
  isLast,
  onMoveUp,
  onMoveDown,
}: {
  sc: SessionCase;
  isFirst: boolean;
  isLast: boolean;
  onMoveUp?: () => void;
  onMoveDown?: () => void;
}) {
  const cfg = STATUS_CONFIG[sc.status];
  const patient = sc.clinical_case.patient;
  const displayName = patient
    ? `${patient.first_name} ${patient.last_name}`
    : sc.clinical_case.title;

  return (
    <div
      className={cn(
        "relative flex items-start gap-4 rounded-lg border-l-2 border border-[#1C1C48] p-4 transition-colors",
        cfg.bgHighlight ? "bg-[#0F1830]" : "bg-[#10102A]",
      )}
      style={{ borderLeftColor: cfg.borderColor }}
    >
      {/* Order number */}
      <div
        className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm font-bold"
        style={{ color: cfg.borderColor, backgroundColor: `${cfg.borderColor}18` }}
      >
        {sc.order}
      </div>

      {/* Main content */}
      <div className="min-w-0 flex-1">
        {/* Patient / case name */}
        <div className="flex flex-wrap items-center gap-2">
          <span
            className={cn(
              "text-sm font-semibold text-[#E8ECF4]",
              sc.status === "skipped" && "line-through text-[#4A5068]",
            )}
          >
            {displayName}
          </span>
          {patient && (
            <span className="font-['IBM_Plex_Mono',monospace] text-[10px] text-[#4A5068]">
              MRN {patient.mrn}
            </span>
          )}
        </div>

        {/* Meta row */}
        <div className="mt-1 flex flex-wrap items-center gap-3">
          {/* Specialty */}
          <span className="rounded bg-[#1C1C48] px-1.5 py-0.5 text-[10px] font-medium text-[#7A8298]">
            {sc.clinical_case.specialty}
          </span>

          {/* Time allotment */}
          <span className="rounded bg-[#1C1C48] px-1.5 py-0.5 font-['IBM_Plex_Mono',monospace] text-[10px] text-[#7A8298]">
            {sc.time_allotted_minutes} min
          </span>

          {/* Presenter */}
          {sc.presenter && (
            <span className="flex items-center gap-1 text-[10px] text-[#4A5068]">
              <User size={10} />
              {sc.presenter.name}
            </span>
          )}

          {/* Status badge */}
          <span
            className="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider"
            style={{ color: cfg.borderColor }}
          >
            {sc.status === "discussed" && <CheckCircle size={10} />}
            {sc.status === "presenting" && (
              <span
                className="inline-block h-1.5 w-1.5 animate-pulse rounded-full"
                style={{ backgroundColor: cfg.borderColor }}
              />
            )}
            {cfg.label}
          </span>

          {/* Patient profile link */}
          {patient && (
            <a
              href={`/profiles/${patient.id}`}
              className="flex items-center gap-1 text-[10px] font-medium text-[#2DD4BF] transition-colors hover:text-[#25B8A5]"
            >
              Open Patient
              <ExternalLink size={10} />
            </a>
          )}
        </div>
      </div>

      {/* Reorder arrows */}
      {(onMoveUp || onMoveDown) && (
        <div className="flex shrink-0 flex-col gap-0.5">
          <button
            type="button"
            onClick={onMoveUp}
            disabled={isFirst}
            className="flex h-6 w-6 items-center justify-center rounded text-[#4A5068] transition-colors hover:bg-[#1C1C48] hover:text-[#7A8298] disabled:pointer-events-none disabled:opacity-30"
            aria-label="Move case up"
          >
            <ChevronUp size={14} />
          </button>
          <button
            type="button"
            onClick={onMoveDown}
            disabled={isLast}
            className="flex h-6 w-6 items-center justify-center rounded text-[#4A5068] transition-colors hover:bg-[#1C1C48] hover:text-[#7A8298] disabled:pointer-events-none disabled:opacity-30"
            aria-label="Move case down"
          >
            <ChevronDown size={14} />
          </button>
        </div>
      )}
    </div>
  );
}

// ── Main component ────────────────────────────────────────────────────────────

export function SessionAgenda({
  sessionCases,
  onReorder,
}: SessionAgendaProps) {
  const sorted = [...sessionCases].sort((a, b) => a.order - b.order);

  const handleMove = (sc: SessionCase, direction: "up" | "down") => {
    if (!onReorder) return;
    const idx = sorted.findIndex((c) => c.id === sc.id);
    const swapIdx = direction === "up" ? idx - 1 : idx + 1;
    if (swapIdx < 0 || swapIdx >= sorted.length) return;
    const swapCase = sorted[swapIdx];
    onReorder(sc.id, swapCase.order);
  };

  if (sorted.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
        <p className="text-sm text-[#7A8298]">No cases on the agenda</p>
        <p className="mt-1 text-xs text-[#4A5068]">Add cases to build the session agenda.</p>
      </div>
    );
  }

  return (
    <div className="space-y-2">
      {sorted.map((sc, idx) => (
        <AgendaCaseRow
          key={sc.id}
          sc={sc}
          isFirst={idx === 0}
          isLast={idx === sorted.length - 1}
          onMoveUp={onReorder ? () => handleMove(sc, "up") : undefined}
          onMoveDown={onReorder ? () => handleMove(sc, "down") : undefined}
        />
      ))}
    </div>
  );
}
