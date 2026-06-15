import { Trash2 } from "lucide-react";
import type { ClassificationCriterion } from "../types";

interface ClassificationCriteriaListProps {
  criteria: ClassificationCriterion[];
  onRemove?: (criterionId: number) => void;
  removing?: boolean;
}

export function ClassificationCriteriaList({ criteria, onRemove, removing }: ClassificationCriteriaListProps) {
  if (criteria.length === 0) {
    return <p className="text-sm text-[var(--text-muted)]">No criteria applied yet.</p>;
  }

  return (
    <ul className="divide-y divide-[var(--surface-elevated)]">
      {criteria.map((c) => (
        <li key={c.id} className="flex items-center gap-2 py-2 text-sm">
          <span className="font-mono font-semibold text-[var(--text-primary)]">{c.code}</span>
          <span className="text-xs text-[var(--text-secondary)]">{c.applied_strength.replace("_", " ")}</span>
          <span className="font-mono text-xs text-[var(--text-muted)]">{c.points > 0 ? `+${c.points}` : c.points}</span>
          {c.evidence_value && <span className="text-xs text-[var(--text-muted)]">· {c.evidence_value}</span>}
          <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-[10px] text-[var(--text-muted)]">{c.set_by}</span>
          {onRemove && (
            <button
              type="button"
              aria-label={`Remove ${c.code}`}
              disabled={removing}
              onClick={() => onRemove(c.id)}
              className="ml-auto text-[var(--text-muted)] hover:text-[var(--primary)] disabled:opacity-50"
            >
              <Trash2 size={15} />
            </button>
          )}
        </li>
      ))}
    </ul>
  );
}
