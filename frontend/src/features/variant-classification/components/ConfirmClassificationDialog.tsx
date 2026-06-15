import { useState } from "react";
import { useConfirmClassification } from "../hooks/useClassification";
import { CLASSIFICATION_LABEL, type AcmgClassification, type VariantClassification } from "../types";

const OPTIONS: AcmgClassification[] = ["pathogenic", "likely_pathogenic", "vus", "likely_benign", "benign"];

export function ConfirmClassificationDialog({
  classification, open, onClose, onUpdated,
}: {
  classification: VariantClassification;
  open: boolean;
  onClose: () => void;
  onUpdated?: (classification: VariantClassification) => void;
}) {
  const confirm = useConfirmClassification(classification.id);
  const [final, setFinal] = useState<AcmgClassification>(classification.computed_classification);
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);

  if (!open) return null;
  const differs = final !== classification.computed_classification;

  function submit() {
    setError(null);
    if (differs && reason.trim() === "") { setError("An override reason is required when changing the computed call."); return; }
    confirm.mutate(
      { final_classification: final, override_reason: differs ? reason : undefined },
      { onSuccess: (c) => { onUpdated?.(c); onClose(); }, onError: () => setError("Confirmation failed") },
    );
  }

  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-md rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-1 text-sm font-semibold text-[var(--text-primary)]">Confirm classification</h3>
        <p className="mb-3 text-xs text-[var(--text-muted)]">Computed: {CLASSIFICATION_LABEL[classification.computed_classification]} ({classification.computed_points} pts). You are the deciding clinician.</p>
        <label htmlFor="final-cls" className="mb-1 block text-xs text-[var(--text-secondary)]">Final classification</label>
        <select id="final-cls" value={final} onChange={(e) => setFinal(e.target.value as AcmgClassification)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]">
          {OPTIONS.map((o) => <option key={o} value={o}>{CLASSIFICATION_LABEL[o]}</option>)}
        </select>
        {differs && (
          <>
            <label htmlFor="ovr" className="mb-1 mt-3 block text-xs text-[var(--text-secondary)]">Override reason (required)</label>
            <textarea id="ovr" rows={3} value={reason} onChange={(e) => setReason(e.target.value)}
              className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]" />
          </>
        )}
        {error && <p className="mt-2 text-sm text-[var(--primary)]">{error}</p>}
        <div className="mt-4 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-md px-3 py-1 text-sm text-[var(--text-secondary)]">Cancel</button>
          <button type="button" onClick={submit} disabled={confirm.isPending}
            className="rounded-md bg-[var(--primary)] px-3 py-1 text-sm text-white disabled:opacity-50">Confirm &amp; sign off</button>
        </div>
      </div>
    </div>
  );
}
