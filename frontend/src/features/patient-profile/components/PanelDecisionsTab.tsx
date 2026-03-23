import type { PatientDecision } from '../types/collaboration';

interface PanelDecisionsTabProps {
  decisions: PatientDecision[];
}

interface StatusConfig {
  label: string;
  bg: string;
  text: string;
}

function getStatusConfig(status: string): StatusConfig {
  switch (status) {
    case 'proposed':
      return { label: 'Proposed', bg: 'bg-gray-500/20', text: 'text-gray-400' };
    case 'under_review':
      return { label: 'Under Review', bg: 'bg-blue-500/20', text: 'text-blue-400' };
    case 'approved':
      return { label: 'Approved', bg: 'bg-green-500/20', text: 'text-green-400' };
    case 'rejected':
      return { label: 'Rejected', bg: 'bg-red-500/20', text: 'text-red-400' };
    case 'deferred':
      return { label: 'Deferred', bg: 'bg-amber-500/20', text: 'text-amber-400' };
    default:
      return { label: status, bg: 'bg-gray-500/20', text: 'text-gray-400' };
  }
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  });
}

function VoteTally({ votes }: { votes: PatientDecision['votes'] }) {
  if (!votes || votes.length === 0) return null;
  const agree = votes.filter((v) => v.vote === 'agree').length;
  const disagree = votes.filter((v) => v.vote === 'disagree').length;
  const abstain = votes.filter((v) => v.vote === 'abstain').length;

  return (
    <span className="text-[10px] text-[var(--text-ghost)]">
      {agree}↑{disagree > 0 && ` ${disagree}↓`}{abstain > 0 && ` ${abstain}–`}
    </span>
  );
}

export function PanelDecisionsTab({ decisions }: PanelDecisionsTabProps) {
  const sorted = [...decisions].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
  );

  return (
    <div className="flex flex-col gap-2 px-3 py-2">
      {sorted.length === 0 && (
        <p className="text-xs text-[var(--text-ghost)] italic py-2">
          No decisions recorded for this patient.
        </p>
      )}

      {sorted.map((decision) => {
        const cfg = getStatusConfig(decision.status);
        return (
          <div
            key={decision.id}
            className="rounded-md px-2.5 py-2 bg-white/5 border border-white/8 hover:border-white/15 transition-colors"
          >
            {/* Recommendation (truncated to 3 lines) */}
            <p className="text-[12px] text-[var(--text-primary)] leading-snug line-clamp-3">
              {decision.recommendation}
            </p>

            {/* Meta row */}
            <div className="flex items-center gap-2 mt-1.5 flex-wrap">
              {/* Status badge */}
              <span
                className={`inline-block rounded-full px-1.5 py-px text-[9px] font-semibold leading-none ${cfg.bg} ${cfg.text}`}
              >
                {cfg.label}
              </span>

              {/* Vote tally */}
              <VoteTally votes={decision.votes} />

              {/* Source case */}
              {decision.clinical_case && (
                <span className="text-[10px] text-[var(--text-ghost)] truncate flex-1 min-w-0">
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
  );
}
