import { useState } from "react";
import { FlaskConical, ChevronDown, AlertTriangle, CheckCircle2 } from "lucide-react";
import { cn } from "@/lib/utils";
import { Badge } from "@/components/ui/Badge";
import { Skeleton } from "@/components/ui/Skeleton";
import { EmptyState } from "@/components/ui/EmptyState";
import type { TrialSuggestion } from "../types/decision-support";

// ---------------------------------------------------------------------------
// Confidence helpers
// ---------------------------------------------------------------------------

const CONFIDENCE_STYLES: Record<string, { color: string; bg: string; border: string }> = {
  high:   { color: "#2DD4BF", bg: "#2DD4BF15", border: "#2DD4BF30" },
  medium: { color: "#F59E0B", bg: "#F59E0B15", border: "#F59E0B30" },
  low:    { color: "#E85A6B", bg: "#E85A6B15", border: "#E85A6B30" },
};

// ---------------------------------------------------------------------------
// TrialCard
// ---------------------------------------------------------------------------

function TrialCard({ trial }: { trial: TrialSuggestion }) {
  const [expanded, setExpanded] = useState(false);
  const style = CONFIDENCE_STYLES[trial.confidence] ?? CONFIDENCE_STYLES.low;

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)]">
      <button
        type="button"
        onClick={() => setExpanded((v) => !v)}
        className="w-full text-left p-4 flex items-start justify-between gap-3 hover:bg-[var(--surface-overlay)] transition-colors"
      >
        <div className="min-w-0 flex-1 space-y-1">
          <p className="text-sm font-medium text-[var(--text-primary)]">
            {trial.trial_type}
          </p>
          <p className="text-xs text-[var(--text-muted)] line-clamp-2">
            {trial.rationale}
          </p>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          <span
            className="text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase"
            style={{ color: style.color, backgroundColor: style.bg, border: `1px solid ${style.border}` }}
          >
            {trial.confidence}
          </span>
          <ChevronDown
            size={14}
            className={cn(
              "text-[var(--text-ghost)] transition-transform",
              expanded && "rotate-180",
            )}
          />
        </div>
      </button>

      {expanded && (
        <div className="border-t border-[var(--border-default)] px-4 py-3 space-y-3">
          {/* Criteria met */}
          {trial.key_criteria_met.length > 0 && (
            <div>
              <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1.5 flex items-center gap-1">
                <CheckCircle2 size={10} className="text-[#2DD4BF]" />
                Criteria Met
              </p>
              <div className="flex flex-wrap gap-1">
                {trial.key_criteria_met.map((c) => (
                  <Badge key={c} variant="success" className="text-[10px]">
                    {c}
                  </Badge>
                ))}
              </div>
            </div>
          )}

          {/* Potential exclusions */}
          {trial.potential_exclusions.length > 0 && (
            <div>
              <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1.5 flex items-center gap-1">
                <AlertTriangle size={10} className="text-[#F59E0B]" />
                Potential Exclusions
              </p>
              <div className="flex flex-wrap gap-1">
                {trial.potential_exclusions.map((e) => (
                  <Badge key={e} variant="warning" className="text-[10px]">
                    {e}
                  </Badge>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// TrialMatchResults
// ---------------------------------------------------------------------------

interface TrialMatchResultsProps {
  trials: TrialSuggestion[] | undefined;
  isLoading: boolean;
  isError: boolean;
}

export function TrialMatchResults({ trials, isLoading, isError }: TrialMatchResultsProps) {
  if (isLoading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 space-y-2">
            <Skeleton variant="text" width="60%" />
            <Skeleton variant="text" width="90%" />
            <Skeleton variant="text" width="40%" height="20px" />
          </div>
        ))}
      </div>
    );
  }

  if (isError) {
    return (
      <div className="rounded-lg border border-[#E85A6B]/20 bg-[#E85A6B]/5 p-4 text-center">
        <p className="text-sm text-[#E85A6B]">Failed to load trial matches</p>
        <p className="text-xs text-[var(--text-muted)] mt-1">Please try again later.</p>
      </div>
    );
  }

  if (!trials || trials.length === 0) {
    return (
      <EmptyState
        icon={<FlaskConical size={32} className="text-[var(--text-ghost)]" />}
        title="No trial matches found"
        message="No clinical trials matched this patient's profile. Try adjusting the condition focus."
      />
    );
  }

  return (
    <div className="space-y-3">
      {trials.map((trial, i) => (
        <TrialCard key={`${trial.trial_type}-${i}`} trial={trial} />
      ))}
    </div>
  );
}
