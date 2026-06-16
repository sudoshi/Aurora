import { KbAlertSeverityBadge } from "./KbAlertSeverityBadge";
import { usePatientKbAlerts, useAcknowledgeKbAlert } from "../hooks/useReanalysis";
import { BUCKET_LABEL } from "../types";

export function ReanalysisAlertsPanel({ patientId }: { patientId: number }) {
  const { data: alerts, isLoading } = usePatientKbAlerts(patientId);
  const ack = useAcknowledgeKbAlert(patientId);

  if (isLoading) return <p className="text-sm text-[var(--text-muted)]">Loading reanalysis alerts…</p>;

  const open = (alerts ?? []).filter((a) => a.status === "new");
  if (open.length === 0) {
    return <p className="text-sm text-[var(--text-muted)]">No reanalysis alerts.</p>;
  }

  return (
    <section className="space-y-2">
      {open.map((a) => (
        <div key={a.id} className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-3">
          {(() => {
            const isGdv = a.source === "clingen_gdv";
            return (
              <>
                <div className="flex items-center gap-2">
                  <KbAlertSeverityBadge severity={a.severity} />
                  <span className="text-sm text-[var(--text-primary)]">
                    {a.evidence?.gene ?? "Variant"}: {BUCKET_LABEL[a.from_bucket] ?? a.from_bucket} → <span className="text-[var(--accent)]">{BUCKET_LABEL[a.to_bucket] ?? a.to_bucket}</span>
                  </span>
                  {isGdv
                    ? a.evidence?.report_url && (
                        <a href={a.evidence.report_url} target="_blank" rel="noreferrer" className="text-xs text-[var(--teal)] hover:underline">
                          ClinGen report
                        </a>
                      )
                    : a.clinvar_variation_id && a.evidence?.variation_url && (
                        <a href={a.evidence.variation_url} target="_blank" rel="noreferrer" className="text-xs text-[var(--teal)] hover:underline">
                          ClinVar {a.clinvar_variation_id}
                        </a>
                      )}
                </div>
                <p className="mt-1 text-xs text-[var(--text-muted)]">
                  {isGdv
                    ? `ClinGen reclassified the gene–disease validity${a.evidence?.disease ? ` for ${a.evidence.disease}` : ""} since last review.`
                    : `ClinVar reclassified (${a.from_stars}→${a.to_stars}★) since last review. ${a.evidence?.review_status ?? ""}`}
                </p>
              </>
            );
          })()}
          <div className="mt-2 flex justify-end gap-2">
            <button type="button" disabled={ack.isPending}
              onClick={() => ack.mutate({ alertId: a.id, status: "acknowledged" })}
              className="rounded-md border border-[var(--surface-elevated)] px-2 py-0.5 text-xs text-[var(--text-secondary)] disabled:opacity-50">
              Acknowledge
            </button>
            <button type="button" disabled={ack.isPending}
              onClick={() => ack.mutate({ alertId: a.id, status: "dismissed", resolution_note: "Dismissed" })}
              className="rounded-md border border-[var(--surface-elevated)] px-2 py-0.5 text-xs text-[var(--text-secondary)] disabled:opacity-50">
              Dismiss
            </button>
          </div>
        </div>
      ))}
    </section>
  );
}
