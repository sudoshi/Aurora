import type { PatientDecision } from "../../patient-profile/types/collaboration";

// ── Types ─────────────────────────────────────────────────────────────────────

interface SessionCase {
  case_id: number;
  clinical_case: {
    id: number;
    title: string;
    patient_id: number | null;
    patient?: { id: number; first_name: string; last_name: string };
  };
}

interface SessionDecisionLogProps {
  sessionId: number;
  decisions: PatientDecision[];
  sessionCases: SessionCase[];
}

// ── Constants ─────────────────────────────────────────────────────────────────

const STATUS_STYLE: Record<string, { label: string; color: string; bg: string }> = {
  proposed:     { label: "Proposed",     color: "#9CA3AF", bg: "#1F2937" },
  under_review: { label: "Under Review", color: "#60A5FA", bg: "#1E3A5F" },
  approved:     { label: "Approved",     color: "#2DD4BF", bg: "#0D3D38" },
  rejected:     { label: "Rejected",     color: "#F0607A", bg: "#3D1020" },
  deferred:     { label: "Deferred",     color: "#F59E0B", bg: "#3D2A00" },
};

// ── Decision row ──────────────────────────────────────────────────────────────

function DecisionRow({ decision }: { decision: PatientDecision }) {
  const statusCfg = STATUS_STYLE[decision.status] ?? STATUS_STYLE.proposed;

  const votes = decision.votes ?? [];
  const agree    = votes.filter((v) => v.vote === "agree").length;
  const disagree = votes.filter((v) => v.vote === "disagree").length;
  const abstain  = votes.filter((v) => v.vote === "abstain").length;
  const hasVotes = agree + disagree + abstain > 0;

  const date = new Date(decision.created_at).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  });

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-3 space-y-2">
      {/* Badges */}
      <div className="flex flex-wrap items-center gap-2">
        <span
          className="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
          style={{ color: statusCfg.color, backgroundColor: statusCfg.bg }}
        >
          {statusCfg.label}
        </span>
        <span className="rounded bg-[#1C1C48] px-1.5 py-0.5 text-[10px] font-medium text-[#7A8298]">
          {decision.decision_type.replace(/_/g, " ")}
        </span>
      </div>

      {/* Recommendation */}
      <p className="text-sm text-[#B4BAC8]">{decision.recommendation}</p>

      {/* Footer */}
      <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
        {decision.proposer && (
          <span className="text-[10px] text-[#4A5068]">
            <span className="text-[#7A8298]">{decision.proposer.name}</span>
            {" · "}
            <span className="font-['IBM_Plex_Mono',monospace]">{date}</span>
          </span>
        )}
        {hasVotes && (
          <span className="font-['IBM_Plex_Mono',monospace] text-[10px] text-[#4A5068]">
            {agree} agree · {disagree} disagree · {abstain} abstain
          </span>
        )}
      </div>
    </div>
  );
}

// ── Main component ────────────────────────────────────────────────────────────

export function SessionDecisionLog({
  sessionId: _sessionId,
  decisions,
  sessionCases,
}: SessionDecisionLogProps) {
  if (sessionCases.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
        <p className="text-sm text-[#7A8298]">Decisions will appear here after the meeting.</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {sessionCases.map((sc, idx) => {
        const { clinical_case: cc } = sc;
        const groupDecisions = decisions.filter((d) => d.case_id === cc.id);
        const patientName = cc.patient
          ? `${cc.patient.first_name} ${cc.patient.last_name}`
          : null;
        const groupLabel = patientName ?? cc.title;

        return (
          <div key={sc.case_id}>
            {/* Group separator */}
            {idx > 0 && <div className="border-t border-[#1C1C48]" />}

            {/* Group header */}
            <div className="flex items-center gap-2 py-2">
              <span className="text-xs font-semibold text-[#B4BAC8]">{groupLabel}</span>
              <span className="rounded-full bg-[#1C1C48] px-2 py-0.5 font-['IBM_Plex_Mono',monospace] text-[10px] text-[#7A8298]">
                {groupDecisions.length}
              </span>
            </div>

            {/* Decision list */}
            {groupDecisions.length > 0 ? (
              <div className="space-y-2">
                {groupDecisions.map((d) => (
                  <DecisionRow key={d.id} decision={d} />
                ))}
              </div>
            ) : (
              <p className="rounded-lg border border-dashed border-[#1C1C48] bg-[#10102A] px-3 py-4 text-center text-xs text-[#4A5068]">
                No decisions captured yet
              </p>
            )}
          </div>
        );
      })}
    </div>
  );
}
