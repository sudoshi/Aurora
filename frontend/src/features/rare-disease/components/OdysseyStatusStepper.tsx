import { ODYSSEY_STATES, type OdysseyStatus } from "../types";

interface OdysseyStatusStepperProps {
  current: OdysseyStatus;
  allowed: OdysseyStatus[];
  onTransition: (to: OdysseyStatus) => void;
  isPending: boolean;
}

const LABELS: Record<OdysseyStatus, string> = {
  referral: "Referral",
  phenotyping: "Phenotyping",
  testing: "Testing",
  prioritization: "Prioritization",
  mdt_review: "MDT Review",
  matchmaking: "Matchmaking",
  diagnosed: "Diagnosed",
  reanalysis: "Reanalysis",
  closed: "Closed",
};

export function OdysseyStatusStepper({ current, allowed, onTransition, isPending }: OdysseyStatusStepperProps) {
  return (
    <div>
      <ol className="flex flex-wrap gap-2">
        {ODYSSEY_STATES.map((state) => {
          const isCurrent = state === current;
          return (
            <li
              key={state}
              aria-current={isCurrent ? "step" : undefined}
              className={
                "rounded-full px-3 py-1 text-xs " +
                (isCurrent
                  ? "bg-[var(--primary)] text-white"
                  : "bg-[var(--surface-raised)] text-[var(--text-muted)]")
              }
            >
              {LABELS[state]}
            </li>
          );
        })}
      </ol>

      {allowed.length > 0 && (
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <span className="text-sm text-[var(--text-secondary)]">Advance to:</span>
          {allowed.map((to) => (
            <button
              key={to}
              type="button"
              disabled={isPending}
              onClick={() => onTransition(to)}
              className="rounded-md border border-[var(--accent)] px-3 py-1 text-sm text-[var(--accent)] hover:bg-[var(--surface-elevated)] disabled:opacity-50"
            >
              {LABELS[to]}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
