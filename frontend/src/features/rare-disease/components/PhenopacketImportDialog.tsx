import { useState } from "react";
import { useImportPhenopacket } from "../hooks/useRareDisease";
import { validatePhenopacket } from "../types/phenopacketSchema";

interface PhenopacketImportDialogProps {
  odysseyId: number;
  open: boolean;
  onClose: () => void;
}

export function PhenopacketImportDialog({ odysseyId, open, onClose }: PhenopacketImportDialogProps) {
  const [raw, setRaw] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<string | null>(null);
  const importMut = useImportPhenopacket(odysseyId);

  if (!open) return null;

  function submit() {
    setError(null);
    setResult(null);
    let parsed: unknown;
    try {
      parsed = JSON.parse(raw);
    } catch {
      setError("Invalid JSON");
      return;
    }
    const check = validatePhenopacket(parsed);
    if (!check.success || !check.data) {
      setError(check.error ?? "Invalid phenopacket");
      return;
    }
    importMut.mutate(check.data as Record<string, unknown>, {
      onSuccess: (r) => setResult(`Imported ${r.imported}, skipped ${r.skipped}`),
      onError: () => setError("Import failed"),
    });
  }

  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-lg rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-2 text-sm font-semibold text-[var(--text-primary)]">Import Phenopacket (GA4GH v2)</h3>
        <label htmlFor="pp-json" className="mb-1 block text-xs text-[var(--text-secondary)]">Phenopacket JSON</label>
        <textarea
          id="pp-json"
          rows={10}
          value={raw}
          onChange={(e) => setRaw(e.target.value)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] p-2 font-mono text-xs text-[var(--text-primary)]"
        />
        {error && <p className="mt-2 text-sm text-[var(--primary)]">{error}</p>}
        {result && <p className="mt-2 text-sm text-[var(--teal)]">{result}</p>}
        <div className="mt-3 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-md px-3 py-1 text-sm text-[var(--text-secondary)]">Close</button>
          <button
            type="button"
            onClick={submit}
            disabled={importMut.isPending}
            className="rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50"
          >
            Import
          </button>
        </div>
      </div>
    </div>
  );
}
