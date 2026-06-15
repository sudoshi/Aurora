import { CLASSIFICATION_LABEL, type AcmgClassification } from "../types";

interface AcmgPointsBarProps {
  classification: AcmgClassification;
  points: number;
}

const COLOR: Record<AcmgClassification, string> = {
  pathogenic: "var(--primary)",
  likely_pathogenic: "var(--primary)",
  vus: "var(--accent)",
  likely_benign: "var(--teal)",
  benign: "var(--teal)",
};

export function AcmgPointsBar({ classification, points }: AcmgPointsBarProps) {
  const signed = points > 0 ? `+${points}` : `${points}`;
  return (
    <div className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-3">
      <div className="flex items-center justify-between">
        <span className="text-sm font-semibold" style={{ color: COLOR[classification] }}>
          {CLASSIFICATION_LABEL[classification]}
        </span>
        <span className="font-mono text-sm text-[var(--text-primary)]">{signed} pts</span>
      </div>
      <p className="mt-1 text-xs text-[var(--text-muted)]">
        Thresholds: Pathogenic ≥ 10 · Likely Path. 6–9 · VUS 0–5 · Likely Benign −1…−6 · Benign ≤ −7
      </p>
    </div>
  );
}
