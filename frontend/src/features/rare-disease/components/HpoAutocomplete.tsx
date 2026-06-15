import { useState } from "react";
import { useHpoSearch } from "../hooks/useRareDisease";
import type { HpoTerm } from "../types";

interface HpoAutocompleteProps {
  onSelect: (term: HpoTerm) => void;
  placeholder?: string;
}

export function HpoAutocomplete({ onSelect, placeholder = "Search HPO terms…" }: HpoAutocompleteProps) {
  const [query, setQuery] = useState("");
  const [open, setOpen] = useState(false);
  const { data: terms, isFetching } = useHpoSearch(query);

  function choose(term: HpoTerm) {
    onSelect(term);
    setQuery("");
    setOpen(false);
  }

  return (
    <div className="relative">
      <label htmlFor="hpo-autocomplete" className="sr-only">HPO term</label>
      <input
        id="hpo-autocomplete"
        type="text"
        value={query}
        placeholder={placeholder}
        autoComplete="off"
        onChange={(e) => { setQuery(e.target.value); setOpen(true); }}
        onFocus={() => setOpen(true)}
        onKeyDown={(e) => { if (e.key === "Escape") setOpen(false); }}
        className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-raised)] px-3 py-2 text-[var(--text-primary)]"
      />
      {open && query.trim().length >= 2 && (
        <ul role="listbox" className="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-raised)] shadow-lg">
          {isFetching && <li className="px-3 py-2 text-sm text-[var(--text-muted)]">Searching…</li>}
          {!isFetching && (terms?.length ?? 0) === 0 && (
            <li className="px-3 py-2 text-sm text-[var(--text-muted)]">No matches</li>
          )}
          {terms?.map((term) => (
            <li key={term.id}>
              <button
                type="button"
                onClick={() => choose(term)}
                className="block w-full px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--surface-elevated)]"
              >
                <span className="font-medium">{term.label}</span>{" "}
                <span className="font-mono text-xs text-[var(--text-muted)]">{term.id}</span>
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
