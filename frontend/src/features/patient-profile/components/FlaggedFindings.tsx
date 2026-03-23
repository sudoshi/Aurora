import type { PatientFlag, FlagSeverity } from "../types/collaboration";

interface FlaggedFindingsProps {
  flags: PatientFlag[];
  onResolve: (flagId: number) => void;
  onNavigate: (recordRef: string) => void;
}

const SEVERITY_ORDER: Record<FlagSeverity, number> = {
  critical: 0,
  attention: 1,
  informational: 2,
};

const SEVERITY_DOT_COLOR: Record<FlagSeverity, string> = {
  critical: "#ef4444",
  attention: "#fbbf24",
  informational: "#3b82f6",
};

const SEVERITY_LABEL: Record<FlagSeverity, string> = {
  critical: "Critical",
  attention: "Attention",
  informational: "Info",
};

export function FlaggedFindings({ flags, onResolve, onNavigate }: FlaggedFindingsProps) {
  const unresolved = [...flags]
    .filter((f) => f.resolved_at == null)
    .sort((a, b) => SEVERITY_ORDER[a.severity] - SEVERITY_ORDER[b.severity]);

  return (
    <div className="flex flex-col gap-1">
      <p
        className="text-[11px] font-semibold uppercase tracking-wide mb-2"
        style={{ color: "#fbbf24" }}
      >
        Flagged Findings
      </p>

      {unresolved.length === 0 && (
        <p className="text-xs text-[var(--text-ghost)] italic py-1">
          No flags raised. Flag a finding from any data view to see it here.
        </p>
      )}

      <div className="flex flex-col gap-1">
        {unresolved.map((flag) => (
          <div
            key={flag.id}
            className="flex items-start gap-2 rounded px-2 py-1.5 hover:bg-white/5 transition-colors group"
          >
            {/* Severity dot */}
            <span
              className="mt-0.5 h-2 w-2 shrink-0 rounded-full"
              style={{ backgroundColor: SEVERITY_DOT_COLOR[flag.severity] }}
              title={SEVERITY_LABEL[flag.severity]}
            />

            {/* Content */}
            <button
              type="button"
              className="flex-1 min-w-0 text-left"
              onClick={() => onNavigate(flag.record_ref)}
            >
              <p className="text-xs text-[var(--text-primary)] truncate group-hover:text-white transition-colors">
                {flag.title}
              </p>
              {flag.description && (
                <p className="text-[10px] text-[var(--text-ghost)] truncate mt-0.5">
                  {flag.description}
                </p>
              )}
            </button>

            {/* Resolve button */}
            <button
              type="button"
              title="Resolve flag"
              onClick={() => onResolve(flag.id)}
              className="shrink-0 text-[10px] text-[var(--text-ghost)] hover:text-[var(--text-muted)] opacity-0 group-hover:opacity-100 transition-opacity px-1 py-0.5 rounded hover:bg-white/10"
            >
              ✓
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}
