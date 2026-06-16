import { useState } from "react";
import { Link } from "react-router-dom";
import { useKbAlertWorklist } from "../hooks/useReanalysis";
import { KbAlertSeverityBadge } from "../components/KbAlertSeverityBadge";
import { BUCKET_LABEL } from "../types";
import type { KbAlertSeverity, KbAlertStatus } from "../types";

export default function KbAlertWorklistPage() {
  const [status, setStatus] = useState<KbAlertStatus | undefined>(undefined);
  const [severity, setSeverity] = useState<KbAlertSeverity | undefined>(undefined);
  const { data, isLoading } = useKbAlertWorklist({ status, severity });

  const rows = data?.data ?? [];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-[var(--text-primary)]">Reanalysis Alerts</h1>
        <div className="flex items-center gap-2">
          <select
            value={status ?? ""}
            onChange={(e) => setStatus((e.target.value as KbAlertStatus) || undefined)}
            className="rounded-md border border-[var(--surface-elevated)] bg-[var(--surface)] px-2 py-1 text-sm text-[var(--text-secondary)]"
          >
            <option value="">All statuses</option>
            <option value="new">New</option>
            <option value="acknowledged">Acknowledged</option>
            <option value="dismissed">Dismissed</option>
          </select>
          <select
            value={severity ?? ""}
            onChange={(e) => setSeverity((e.target.value as KbAlertSeverity) || undefined)}
            className="rounded-md border border-[var(--surface-elevated)] bg-[var(--surface)] px-2 py-1 text-sm text-[var(--text-secondary)]"
          >
            <option value="">All severities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
          </select>
        </div>
      </div>

      {isLoading && <p className="text-[var(--text-muted)]">Loading alerts…</p>}
      {!isLoading && rows.length === 0 && (
        <p className="text-[var(--text-muted)]">No reanalysis alerts match the selected filters.</p>
      )}

      {rows.length > 0 && (
        <table className="w-full text-left text-sm">
          <thead className="text-xs text-[var(--text-muted)]">
            <tr>
              <th className="py-2">Gene</th>
              <th>Patient</th>
              <th>Source</th>
              <th>Change</th>
              <th>Severity</th>
              <th>Status</th>
              <th>Raised</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((a) => (
              <tr key={a.id} className="border-t border-[var(--surface-elevated)]">
                <td className="py-2 text-[var(--text-secondary)]">
                  {a.variant?.gene ?? a.evidence?.gene ?? "—"}
                </td>
                <td>
                  <Link
                    to={`/profiles/${a.patient_id}`}
                    className="text-[var(--teal)] hover:underline"
                  >
                    #{a.patient_id}
                  </Link>
                </td>
                <td className="text-[var(--text-secondary)]">
                  {a.source === "clingen_gdv" ? "ClinGen GDV" : "ClinVar"}
                </td>
                <td className="text-[var(--text-secondary)]">
                  {BUCKET_LABEL[a.from_bucket] ?? a.from_bucket} → {BUCKET_LABEL[a.to_bucket] ?? a.to_bucket}
                </td>
                <td>
                  <KbAlertSeverityBadge severity={a.severity} />
                </td>
                <td className="text-[var(--text-secondary)]">{a.status}</td>
                <td className="text-[var(--text-secondary)]">
                  {new Date(a.created_at).toLocaleDateString()}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
