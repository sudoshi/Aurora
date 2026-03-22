import { useNavigate } from "react-router-dom";
import { Clock, MessageSquare, FileText, Users } from "lucide-react";
import { cn } from "@/lib/utils";
import type { ClinicalCase } from "../types/case";

// ── Color maps ───────────────────────────────────────────────────────────────

const SPECIALTY_COLORS: Record<string, { bg: string; text: string }> = {
  oncology:        { bg: "#00D68F15", text: "#F0607A" },
  surgical:        { bg: "#60A5FA15", text: "#60A5FA" },
  rare_disease:    { bg: "#A78BFA15", text: "#A78BFA" },
  complex_medical: { bg: "#F59E0B15", text: "#F59E0B" },
};

const URGENCY_COLORS: Record<string, { bg: string; text: string }> = {
  routine:  { bg: "#2DD4BF15", text: "#2DD4BF" },
  urgent:   { bg: "#F59E0B15", text: "#F59E0B" },
  emergent: { bg: "#00D68F15", text: "#F0607A" },
};

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  draft:     { bg: "#2A2A6020", text: "#7A8298" },
  active:    { bg: "#2DD4BF15", text: "#2DD4BF" },
  in_review: { bg: "#60A5FA15", text: "#60A5FA" },
  closed:    { bg: "#4A506815", text: "#4A5068" },
  archived:  { bg: "#2A2A6015", text: "#4A5068" },
};

function SpecialtyLabel({ specialty }: { specialty: string }) {
  const label = specialty.replace(/_/g, " ");
  const colors = SPECIALTY_COLORS[specialty] ?? { bg: "#2A2A6020", text: "#7A8298" };
  return (
    <span
      className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
      style={{ backgroundColor: colors.bg, color: colors.text }}
    >
      {label}
    </span>
  );
}

function UrgencyDot({ urgency }: { urgency: string }) {
  const colors = URGENCY_COLORS[urgency] ?? { bg: "#2A2A6020", text: "#7A8298" };
  return (
    <span
      className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
      style={{ backgroundColor: colors.bg, color: colors.text }}
    >
      <span
        className="inline-block h-1.5 w-1.5 rounded-full"
        style={{ backgroundColor: colors.text }}
      />
      {urgency}
    </span>
  );
}

function StatusBadge({ status }: { status: string }) {
  const label = status.replace(/_/g, " ");
  const colors = STATUS_COLORS[status] ?? { bg: "#2A2A6020", text: "#7A8298" };
  return (
    <span
      className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
      style={{ backgroundColor: colors.bg, color: colors.text }}
    >
      {label}
    </span>
  );
}

interface CaseCardProps {
  clinicalCase: ClinicalCase;
  className?: string;
}

export function CaseCard({ clinicalCase, className }: CaseCardProps) {
  const navigate = useNavigate();

  const teamCount = clinicalCase.team_members?.length ?? 0;
  const createdDate = new Date(clinicalCase.created_at).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  });

  return (
    <button
      type="button"
      onClick={() => navigate(`/cases/${clinicalCase.id}`)}
      className={cn(
        "w-full text-left rounded-lg border border-[#1C1C48] bg-[#10102A] p-4 transition-all",
        "hover:border-[#2DD4BF]/30 hover:bg-[#16163A] focus:outline-none focus:ring-1 focus:ring-[#2DD4BF]/40",
        className,
      )}
    >
      {/* Top row: badges */}
      <div className="mb-3 flex flex-wrap items-center gap-2">
        <SpecialtyLabel specialty={clinicalCase.specialty} />
        <UrgencyDot urgency={clinicalCase.urgency} />
        <StatusBadge status={clinicalCase.status} />
      </div>

      {/* Title */}
      <h3 className="mb-1 text-sm font-semibold text-[#E8ECF4] line-clamp-1">
        {clinicalCase.title}
      </h3>

      {/* Clinical question preview */}
      {clinicalCase.clinical_question && (
        <p className="mb-3 text-xs text-[#7A8298] line-clamp-2">
          {clinicalCase.clinical_question}
        </p>
      )}

      {/* Bottom row: meta */}
      <div className="flex items-center justify-between border-t border-[#16163A] pt-3">
        <div className="flex items-center gap-3">
          {/* Team avatars (stacked) */}
          {teamCount > 0 && (
            <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
              <Users size={12} />
              {teamCount}
            </span>
          )}
          {(clinicalCase.discussions_count ?? 0) > 0 && (
            <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
              <MessageSquare size={12} />
              {clinicalCase.discussions_count}
            </span>
          )}
          {(clinicalCase.documents_count ?? 0) > 0 && (
            <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
              <FileText size={12} />
              {clinicalCase.documents_count}
            </span>
          )}
        </div>

        <div className="flex items-center gap-2 text-[10px] text-[#4A5068]">
          <Clock size={10} />
          <span className="font-['IBM_Plex_Mono',monospace]">{createdDate}</span>
          {clinicalCase.creator && (
            <>
              <span>&middot;</span>
              <span>{clinicalCase.creator.name}</span>
            </>
          )}
        </div>
      </div>
    </button>
  );
}
