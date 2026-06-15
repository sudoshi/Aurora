import { useState } from "react";
import { AcmgPointsBar } from "./AcmgPointsBar";
import { ClassificationCriteriaList } from "./ClassificationCriteriaList";
import { AddCriterionForm } from "./AddCriterionForm";
import { ConfirmClassificationDialog } from "./ConfirmClassificationDialog";
import { useCreateClassification, useDeleteCriterion } from "../hooks/useClassification";
import { CLASSIFICATION_LABEL, type VariantClassification } from "../types";

export function VariantClassificationPanel({ genomicVariantId }: { genomicVariantId: number }) {
  const create = useCreateClassification(genomicVariantId);
  const [classification, setClassification] = useState<VariantClassification | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const del = useDeleteCriterion(classification?.id ?? 0);

  function classify() {
    create.mutate({}, { onSuccess: (c) => setClassification(c) });
  }

  if (!classification) {
    return (
      <div className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <p className="mb-2 text-sm text-[var(--text-secondary)]">No ACMG classification yet.</p>
        <button type="button" onClick={classify} disabled={create.isPending}
          className="rounded-md bg-[var(--primary)] px-3 py-1.5 text-sm text-white disabled:opacity-50">Classify variant</button>
      </div>
    );
  }

  const confirmed = classification.status === "confirmed";

  return (
    <div className="space-y-3 rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
      <AcmgPointsBar classification={classification.computed_classification} points={classification.computed_points} />

      {confirmed && classification.final_classification && (
        <p className="text-sm text-[var(--teal)]">
          Signed off: {CLASSIFICATION_LABEL[classification.final_classification]}
          {classification.override_reason ? ` — ${classification.override_reason}` : ""}
        </p>
      )}

      <ClassificationCriteriaList
        criteria={classification.criteria ?? []}
        onRemove={confirmed ? undefined : (id) => del.mutate(id, { onSuccess: (c) => setClassification(c) })}
        removing={del.isPending}
      />

      {!confirmed && (
        <>
          <AddCriterionForm classificationId={classification.id} />
          <div className="flex justify-end">
            <button type="button" onClick={() => setConfirmOpen(true)}
              className="rounded-md border border-[var(--primary)] px-3 py-1 text-sm text-[var(--primary)] hover:bg-[var(--surface-elevated)]">
              Confirm &amp; sign off
            </button>
          </div>
        </>
      )}

      <ConfirmClassificationDialog
        classification={classification}
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
      />
    </div>
  );
}
