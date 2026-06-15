import { useState } from "react";
import { useParams, Link } from "react-router-dom";
import { OdysseyStatusStepper } from "../components/OdysseyStatusStepper";
import { PhenotypeCapturePanel } from "../components/PhenotypeCapturePanel";
import { PhenopacketExportButton } from "../components/PhenopacketExportButton";
import { PhenopacketImportDialog } from "../components/PhenopacketImportDialog";
import { useOdyssey, useTransitionOdyssey } from "../hooks/useRareDisease";
import { odysseyPatientName } from "../types";
import { ReanalysisAlertsPanel } from "@/features/reanalysis/components/ReanalysisAlertsPanel";

export default function OdysseyDetailPage() {
  const { id } = useParams<{ id: string }>();
  const odysseyId = Number(id);
  const { data, isLoading, isError } = useOdyssey(odysseyId);
  const transition = useTransitionOdyssey(odysseyId);
  const [importOpen, setImportOpen] = useState(false);

  if (isLoading) return <p className="text-[var(--text-muted)]">Loading odyssey…</p>;
  if (isError || !data) return <p className="text-[var(--primary)]">Could not load this odyssey.</p>;

  const { odyssey, allowed_transitions } = data;

  return (
    <div className="space-y-4">
      <div>
        <Link to="/rare-disease" className="text-xs text-[var(--text-muted)] hover:text-[var(--text-secondary)]">← Rare-disease worklist</Link>
        <h1 className="mt-1 text-2xl font-semibold text-[var(--text-primary)]">{odyssey.title}</h1>
        <div className="mt-1 flex items-center gap-2 text-sm">
          <span className="rounded bg-[var(--surface-elevated)] px-2 py-0.5 text-[var(--text-secondary)]">{odyssey.status}</span>
          <span className="rounded bg-[var(--surface-elevated)] px-2 py-0.5 text-[var(--accent)]">{odyssey.progress_status}</span>
          {odyssey.patient && (
            <Link to={`/profiles/${odyssey.patient_id}`} className="text-[var(--teal)] hover:underline">{odysseyPatientName(odyssey.patient)}</Link>
          )}
        </div>
        {odyssey.referral_reason && <p className="mt-2 text-sm text-[var(--text-secondary)]">{odyssey.referral_reason}</p>}
      </div>

      <OdysseyStatusStepper
        current={odyssey.status}
        allowed={allowed_transitions}
        isPending={transition.isPending}
        onTransition={(to) => transition.mutate({ to_status: to })}
      />

      <div className="flex flex-wrap gap-2">
        <PhenopacketExportButton odysseyId={odysseyId} />
        <button
          type="button"
          onClick={() => setImportOpen(true)}
          className="rounded-md border border-[var(--surface-elevated)] px-3 py-1 text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-elevated)]"
        >
          Import Phenopacket
        </button>
      </div>

      <PhenotypeCapturePanel odysseyId={odysseyId} />

      <section className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-2 text-sm font-semibold text-[var(--text-primary)]">Reanalysis alerts</h3>
        <ReanalysisAlertsPanel patientId={odyssey.patient_id} />
      </section>

      {odyssey.transitions && odyssey.transitions.length > 0 && (
        <section className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
          <h3 className="mb-2 text-sm font-semibold text-[var(--text-primary)]">History</h3>
          <ul className="space-y-1 text-xs text-[var(--text-muted)]">
            {odyssey.transitions.map((t) => (
              <li key={t.id}>
                {t.from_status ?? "—"} → <span className="text-[var(--text-secondary)]">{t.to_status}</span>
                {t.actor ? ` · ${t.actor.name}` : ""} {t.note ? `· ${t.note}` : ""}
              </li>
            ))}
          </ul>
        </section>
      )}

      <PhenopacketImportDialog odysseyId={odysseyId} open={importOpen} onClose={() => setImportOpen(false)} />
    </div>
  );
}
