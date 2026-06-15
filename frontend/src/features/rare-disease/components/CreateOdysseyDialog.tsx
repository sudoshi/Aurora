import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import { useDebounce } from "../hooks/useDebounce";
import { useCreateOdyssey } from "../hooks/useRareDisease";

interface PatientHit {
  id: number;
  name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  mrn?: string | null;
}

function hitName(p: PatientHit): string {
  const full = [p.first_name, p.last_name].filter(Boolean).join(" ").trim();
  return p.name || full || p.mrn || `#${p.id}`;
}

function usePatientSearch(q: string) {
  const debounced = useDebounce(q.trim(), 300);
  return useQuery({
    queryKey: ["patient-search", debounced],
    queryFn: async (): Promise<PatientHit[]> => {
      const { data } = await apiClient.get("/patients/search", { params: { q: debounced } });
      return data.data ?? data;
    },
    enabled: debounced.length >= 2,
  });
}

export function CreateOdysseyDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const navigate = useNavigate();
  const create = useCreateOdyssey();
  const [q, setQ] = useState("");
  const [patient, setPatient] = useState<PatientHit | null>(null);
  const [title, setTitle] = useState("");
  const [reason, setReason] = useState("");
  const { data: hits } = usePatientSearch(q);

  if (!open) return null;

  function submit() {
    if (!patient || title.trim() === "") return;
    create.mutate(
      { patient_id: patient.id, title: title.trim(), referral_reason: reason || undefined },
      { onSuccess: (o) => { onClose(); navigate(`/odysseys/${o.id}`); } },
    );
  }

  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-md rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-3 text-sm font-semibold text-[var(--text-primary)]">New Diagnostic Odyssey</h3>

        {!patient ? (
          <div>
            <label htmlFor="patient-q" className="mb-1 block text-xs text-[var(--text-secondary)]">Patient</label>
            <input id="patient-q" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search patients…"
              className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-3 py-2 text-[var(--text-primary)]" />
            <ul className="mt-1 max-h-40 overflow-auto">
              {hits?.map((p) => (
                <li key={p.id}>
                  <button type="button" onClick={() => setPatient(p)} className="block w-full px-2 py-1 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--surface-elevated)]">{hitName(p)}</button>
                </li>
              ))}
            </ul>
          </div>
        ) : (
          <p className="mb-2 text-sm text-[var(--text-secondary)]">Patient: <span className="text-[var(--text-primary)]">{hitName(patient)}</span>{" "}
            <button type="button" onClick={() => setPatient(null)} className="text-xs text-[var(--text-muted)] underline">change</button></p>
        )}

        <label htmlFor="od-title" className="mb-1 mt-3 block text-xs text-[var(--text-secondary)]">Title</label>
        <input id="od-title" value={title} onChange={(e) => setTitle(e.target.value)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-3 py-2 text-[var(--text-primary)]" />

        <label htmlFor="od-reason" className="mb-1 mt-3 block text-xs text-[var(--text-secondary)]">Referral reason (optional)</label>
        <textarea id="od-reason" rows={2} value={reason} onChange={(e) => setReason(e.target.value)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-3 py-2 text-[var(--text-primary)]" />

        <div className="mt-4 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-md px-3 py-1 text-sm text-[var(--text-secondary)]">Cancel</button>
          <button type="button" onClick={submit} disabled={!patient || title.trim() === "" || create.isPending}
            className="rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50">Create</button>
        </div>
      </div>
    </div>
  );
}
