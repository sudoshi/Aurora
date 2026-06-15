import { useState } from "react";
import { useAcmgCatalog, useAddCriterion } from "../hooks/useClassification";
import type { AcmgStrength } from "../types";

const STRENGTHS: AcmgStrength[] = ["very_strong", "strong", "moderate", "supporting"];

export function AddCriterionForm({ classificationId }: { classificationId: number }) {
  const { data: catalog } = useAcmgCatalog();
  const add = useAddCriterion(classificationId);
  const [code, setCode] = useState("");
  const [strength, setStrength] = useState<AcmgStrength>("supporting");
  const [rationale, setRationale] = useState("");

  const codes = catalog ? Object.keys(catalog) : [];

  function submit() {
    if (!code) return;
    add.mutate(
      { code, applied_strength: strength, rationale: rationale || undefined },
      { onSuccess: () => { setCode(""); setRationale(""); } },
    );
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      <label htmlFor="acmg-code" className="sr-only">ACMG code</label>
      <select id="acmg-code" value={code} onChange={(e) => {
        setCode(e.target.value);
        if (e.target.value && catalog) setStrength(catalog[e.target.value].default_strength);
      }} className="rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]">
        <option value="">Add criterion…</option>
        {codes.map((c) => <option key={c} value={c}>{c}</option>)}
      </select>
      <label htmlFor="acmg-strength" className="sr-only">Strength</label>
      <select id="acmg-strength" value={strength} onChange={(e) => setStrength(e.target.value as AcmgStrength)}
        className="rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]">
        {STRENGTHS.map((s) => <option key={s} value={s}>{s.replace("_", " ")}</option>)}
      </select>
      <input value={rationale} onChange={(e) => setRationale(e.target.value)} placeholder="rationale (optional)"
        className="flex-1 rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]" />
      <button type="button" onClick={submit} disabled={!code || add.isPending}
        className="rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50">Add</button>
    </div>
  );
}
