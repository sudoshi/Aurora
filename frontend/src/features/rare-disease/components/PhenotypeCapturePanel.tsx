import { useState } from "react";
import { Trash2 } from "lucide-react";
import { HpoAutocomplete } from "./HpoAutocomplete";
import { useAddPhenotype, useDeletePhenotype, usePhenotypes } from "../hooks/useRareDisease";
import type { HpoTerm } from "../types";

interface PhenotypeCapturePanelProps {
  odysseyId: number;
}

export function PhenotypeCapturePanel({ odysseyId }: PhenotypeCapturePanelProps) {
  const { data: features, isLoading } = usePhenotypes(odysseyId);
  const add = useAddPhenotype(odysseyId);
  const remove = useDeletePhenotype(odysseyId);

  const [selected, setSelected] = useState<HpoTerm | null>(null);
  const [excluded, setExcluded] = useState(false);

  function submit() {
    if (!selected) return;
    add.mutate(
      { hpo_id: selected.id, hpo_label: selected.label, excluded },
      { onSuccess: () => { setSelected(null); setExcluded(false); } },
    );
  }

  return (
    <section className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
      <h3 className="mb-3 text-sm font-semibold text-[var(--text-primary)]">Phenotype (HPO)</h3>

      <div className="mb-2"><HpoAutocomplete onSelect={setSelected} /></div>

      {selected && (
        <div className="mb-3 flex items-center gap-3 rounded-md bg-[var(--surface-elevated)] px-3 py-2">
          <span className="text-sm text-[var(--text-primary)]">
            {selected.label} <span className="font-mono text-xs text-[var(--text-muted)]">{selected.id}</span>
          </span>
          <label className="flex items-center gap-1 text-xs text-[var(--text-secondary)]">
            <input type="checkbox" checked={excluded} onChange={(e) => setExcluded(e.target.checked)} />
            Explicitly absent
          </label>
          <button
            type="button"
            onClick={submit}
            disabled={add.isPending}
            className="ml-auto rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50"
          >
            Add phenotype
          </button>
        </div>
      )}

      {isLoading && <p className="text-sm text-[var(--text-muted)]">Loading…</p>}
      {!isLoading && (features?.length ?? 0) === 0 && (
        <p className="text-sm text-[var(--text-muted)]">No phenotypes recorded yet.</p>
      )}

      <ul className="divide-y divide-[var(--surface-elevated)]">
        {features?.map((f) => (
          <li key={f.id} className="flex items-center gap-2 py-2">
            <span className="text-sm text-[var(--text-primary)]">{f.hpo_label}</span>
            <span className="font-mono text-xs text-[var(--text-muted)]">{f.hpo_id}</span>
            {f.excluded && (
              <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-xs text-[var(--accent)]">Absent</span>
            )}
            {f.severity_hpo_id && (
              <span className="font-mono text-xs text-[var(--text-muted)]">sev:{f.severity_hpo_id}</span>
            )}
            <button
              type="button"
              aria-label={`Remove ${f.hpo_label}`}
              onClick={() => remove.mutate(f.id)}
              className="ml-auto text-[var(--text-muted)] hover:text-[var(--primary)]"
            >
              <Trash2 size={16} />
            </button>
          </li>
        ))}
      </ul>
    </section>
  );
}
