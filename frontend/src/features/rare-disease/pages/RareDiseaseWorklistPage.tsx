import { useState } from "react";
import { Link } from "react-router-dom";
import { useOdysseyWorklist } from "../hooks/useRareDisease";
import { CreateOdysseyDialog } from "../components/CreateOdysseyDialog";
import { odysseyPatientName } from "../types";

export default function RareDiseaseWorklistPage() {
  const { data, isLoading } = useOdysseyWorklist();
  const [createOpen, setCreateOpen] = useState(false);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-[var(--text-primary)]">Rare-Disease Odysseys</h1>
        <button type="button" onClick={() => setCreateOpen(true)}
          className="rounded-md bg-[var(--primary)] px-3 py-1.5 text-sm text-white">+ New Odyssey</button>
      </div>

      {isLoading && <p className="text-[var(--text-muted)]">Loading worklist…</p>}
      {!isLoading && (data?.data.length ?? 0) === 0 && (
        <p className="text-[var(--text-muted)]">No odysseys yet. Create one to begin a diagnostic odyssey.</p>
      )}

      {data && data.data.length > 0 && (
        <table className="w-full text-left text-sm">
          <thead className="text-xs text-[var(--text-muted)]">
            <tr><th className="py-2">Title</th><th>Patient</th><th>Status</th><th>Progress</th><th>Phenotypes</th></tr>
          </thead>
          <tbody>
            {data.data.map((o) => (
              <tr key={o.id} className="border-t border-[var(--surface-elevated)]">
                <td className="py-2"><Link to={`/odysseys/${o.id}`} className="text-[var(--teal)] hover:underline">{o.title}</Link></td>
                <td className="text-[var(--text-secondary)]">{odysseyPatientName(o.patient)}</td>
                <td className="text-[var(--text-secondary)]">{o.status}</td>
                <td className="text-[var(--text-secondary)]">{o.progress_status}</td>
                <td className="text-[var(--text-secondary)]">{o.phenotype_features_count ?? 0}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      <CreateOdysseyDialog open={createOpen} onClose={() => setCreateOpen(false)} />
    </div>
  );
}
