import type { KbAlertSeverity } from "../types";

export function KbAlertSeverityBadge({ severity }: { severity: KbAlertSeverity }) {
  const color = severity === "high" ? "var(--primary)" : "var(--accent)";
  return (
    <span className="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase" style={{ color, border: `1px solid ${color}` }}>
      {severity}
    </span>
  );
}
