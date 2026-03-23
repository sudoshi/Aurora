import { Stethoscope, Pill } from "lucide-react";
import type { ClinicalEvent } from "../types/profile";

interface ActiveProblemsListProps {
  conditions: ClinicalEvent[];
  medications: ClinicalEvent[];
  onNavigate: (tab: string) => void;
}

const FOURTEEN_DAYS_MS = 14 * 24 * 60 * 60 * 1000;

function isNew(startDate: string): boolean {
  return Date.now() - new Date(startDate).getTime() < FOURTEEN_DAYS_MS;
}

function isActive(event: ClinicalEvent): boolean {
  return event.end_date == null || event.end_date === "";
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

interface ProblemRowProps {
  event: ClinicalEvent;
  onClick: () => void;
}

function ProblemRow({ event, onClick }: ProblemRowProps) {
  const showNew = isNew(event.start_date);
  return (
    <button
      type="button"
      onClick={onClick}
      className="w-full flex items-center justify-between gap-2 rounded px-2 py-1.5 hover:bg-white/5 transition-colors text-left group"
    >
      <span className="text-xs text-[var(--text-primary)] truncate group-hover:text-white transition-colors">
        {event.concept_name}
      </span>
      <div className="flex items-center gap-1.5 shrink-0">
        {showNew && (
          <span className="inline-block rounded-full bg-red-500/20 px-1.5 py-0.5 text-[9px] font-semibold text-red-400 border border-red-500/30 leading-none">
            NEW
          </span>
        )}
        <span className="text-[10px] text-[var(--text-ghost)]">
          {formatDate(event.start_date)}
        </span>
      </div>
    </button>
  );
}

export function ActiveProblemsList({ conditions, medications, onNavigate }: ActiveProblemsListProps) {
  const activeConditions = conditions.filter(isActive);
  const activeMedications = medications.filter(isActive);
  const hasAny = activeConditions.length > 0 || activeMedications.length > 0;

  return (
    <div className="flex flex-col gap-1">
      <p
        className="text-[11px] font-semibold uppercase tracking-wide mb-2"
        style={{ color: "#ef4444" }}
      >
        Active Problems
      </p>

      {!hasAny && (
        <p className="text-xs text-[var(--text-ghost)] italic py-1">
          No active conditions recorded.
        </p>
      )}

      {activeConditions.length > 0 && (
        <div className="mb-2">
          <div className="flex items-center gap-1 mb-1">
            <Stethoscope size={10} className="text-[var(--text-ghost)]" />
            <span className="text-[10px] text-[var(--text-ghost)] uppercase tracking-wide">
              Conditions
            </span>
          </div>
          <div className="flex flex-col gap-0.5">
            {activeConditions.map((c) => (
              <ProblemRow
                key={c.id}
                event={c}
                onClick={() => onNavigate("condition")}
              />
            ))}
          </div>
        </div>
      )}

      {activeMedications.length > 0 && (
        <div>
          <div className="flex items-center gap-1 mb-1">
            <Pill size={10} className="text-[var(--text-ghost)]" />
            <span className="text-[10px] text-[var(--text-ghost)] uppercase tracking-wide">
              Medications
            </span>
          </div>
          <div className="flex flex-col gap-0.5">
            {activeMedications.map((m) => (
              <ProblemRow
                key={m.id}
                event={m}
                onClick={() => onNavigate("medication")}
              />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
