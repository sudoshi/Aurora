import type { PatientFlag, FlagSeverity } from '../types/collaboration';

interface PanelFlagsTabProps {
  flags: PatientFlag[];
  onResolve: (flagId: number) => void;
}

const SEVERITY_COLOR: Record<FlagSeverity, string> = {
  critical: '#ef4444',
  attention: '#fbbf24',
  informational: '#3b82f6',
};

const SEVERITY_ORDER: Record<FlagSeverity, number> = {
  critical: 0,
  attention: 1,
  informational: 2,
};

const SEVERITY_LABEL: Record<FlagSeverity, string> = {
  critical: 'Critical',
  attention: 'Attention',
  informational: 'Info',
};

export function PanelFlagsTab({ flags, onResolve }: PanelFlagsTabProps) {
  const unresolved = [...flags]
    .filter((f) => f.resolved_at == null)
    .sort((a, b) => SEVERITY_ORDER[a.severity] - SEVERITY_ORDER[b.severity]);

  return (
    <div className="flex flex-col gap-1 px-3 py-2">
      {unresolved.length === 0 && (
        <p className="text-xs text-[var(--text-ghost)] italic py-2">
          No unresolved flags.
        </p>
      )}

      {unresolved.map((flag) => (
        <div
          key={flag.id}
          className="flex items-start gap-2 rounded px-2 py-1.5 hover:bg-white/5 transition-colors group"
        >
          {/* Severity dot */}
          <span
            className="mt-1 h-2 w-2 shrink-0 rounded-full"
            style={{ backgroundColor: SEVERITY_COLOR[flag.severity] }}
            title={SEVERITY_LABEL[flag.severity]}
          />

          {/* Content */}
          <div className="flex-1 min-w-0">
            <p className="text-[12px] text-[var(--text-primary)] truncate">
              {flag.title}
            </p>
            {flag.description && (
              <p className="text-[11px] text-[var(--text-ghost)] truncate mt-0.5">
                {flag.description}
              </p>
            )}
          </div>

          {/* Resolve button */}
          <button
            type="button"
            title="Resolve flag"
            onClick={() => onResolve(flag.id)}
            className="shrink-0 text-[10px] text-[var(--text-ghost)] hover:text-green-400 opacity-0 group-hover:opacity-100 transition-all px-1.5 py-0.5 rounded hover:bg-green-400/10"
          >
            Resolve
          </button>
        </div>
      ))}
    </div>
  );
}
