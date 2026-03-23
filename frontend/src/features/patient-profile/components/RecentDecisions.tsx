import type { PatientDecision } from "../types/collaboration";

interface RecentDecisionsProps {
  decisions: PatientDecision[];
}

type DecisionStatus =
  | "proposed"
  | "under_review"
  | "approved"
  | "rejected"
  | "deferred"
  | string;

interface StatusBadgeConfig {
  label: string;
  bg: string;
  text: string;
}

function getStatusConfig(status: DecisionStatus): StatusBadgeConfig {
  switch (status) {
    case "proposed":
      return { label: "Proposed", bg: "bg-gray-500/20", text: "text-gray-400" };
    case "under_review":
      return { label: "Under Review", bg: "bg-blue-500/20", text: "text-blue-400" };
    case "approved":
      return { label: "Approved", bg: "bg-green-500/20", text: "text-green-400" };
    case "rejected":
      return { label: "Rejected", bg: "bg-red-500/20", text: "text-red-400" };
    case "deferred":
      return { label: "Deferred", bg: "bg-amber-500/20", text: "text-amber-400" };
    default:
      return { label: status, bg: "bg-gray-500/20", text: "text-gray-400" };
  }
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function VoteSummary({ votes }: { votes: PatientDecision["votes"] }) {
  if (!votes || votes.length === 0) return null;

  const agree = votes.filter((v) => v.vote === "agree").length;
  const disagree = votes.filter((v) => v.vote === "disagree").length;

  return (
    <span className="text-[10px] text-[var(--text-ghost)]">
      {agree} agree
      {disagree > 0 && `, ${disagree} disagree`}
    </span>
  );
}

export function RecentDecisions({ decisions }: RecentDecisionsProps) {
  const sorted = [...decisions].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
  );

  return (
    <div className="flex flex-col gap-1">
      <p
        className="text-[11px] font-semibold uppercase tracking-wide mb-2"
        style={{ color: "#a78bfa" }}
      >
        Recent Decisions
      </p>

      {sorted.length === 0 && (
        <p className="text-xs text-[var(--text-ghost)] italic py-1">
          No case decisions yet. Create a case to start collaborating.
        </p>
      )}

      <div className="flex flex-col gap-2">
        {sorted.map((decision) => {
          const statusCfg = getStatusConfig(decision.status);
          return (
            <div
              key={decision.id}
              className="rounded px-2 py-1.5 hover:bg-white/5 transition-colors"
            >
              {/* Recommendation text */}
              <p className="text-xs text-[var(--text-primary)] line-clamp-2 leading-relaxed">
                {decision.recommendation}
              </p>

              {/* Meta row */}
              <div className="flex items-center gap-2 mt-1.5 flex-wrap">
                {/* Status badge */}
                <span
                  className={`inline-block rounded-full px-1.5 py-0.5 text-[9px] font-semibold leading-none border border-transparent ${statusCfg.bg} ${statusCfg.text}`}
                >
                  {statusCfg.label}
                </span>

                {/* Vote tally */}
                <VoteSummary votes={decision.votes} />

                {/* Source case */}
                {decision.clinical_case && (
                  <span className="text-[10px] text-[var(--text-ghost)] truncate">
                    {decision.clinical_case.title}
                  </span>
                )}

                {/* Date */}
                <span className="text-[10px] text-[var(--text-ghost)] ml-auto shrink-0">
                  {formatDate(decision.created_at)}
                </span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
