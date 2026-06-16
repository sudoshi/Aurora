import { useMmeMatches, useRunMmeSearch } from "../hooks/useMatchmaker";

interface MmeMatchesPanelProps {
  odysseyId: number;
}

export function MmeMatchesPanel({ odysseyId }: MmeMatchesPanelProps) {
  const { data: matches, isLoading } = useMmeMatches(odysseyId);
  const search = useRunMmeSearch(odysseyId);

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <button
          type="button"
          disabled={search.isPending}
          onClick={() => search.mutate()}
          className="rounded-md border border-[var(--surface-elevated)] px-3 py-1 text-xs text-[var(--text-secondary)] hover:bg-[var(--surface-elevated)] disabled:opacity-50"
        >
          Search Matchmaker Exchange
        </button>
      </div>

      {isLoading && (
        <p className="text-sm text-[var(--text-muted)]">Loading matches…</p>
      )}

      {!isLoading && (matches ?? []).length === 0 && (
        <p className="text-sm text-[var(--text-muted)]">No matchmaker matches yet.</p>
      )}

      {!isLoading && (matches ?? []).length > 0 && (
        <section className="space-y-2">
          {(matches ?? []).map((m) => (
            <div
              key={m.id}
              className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-3"
            >
              <div className="flex items-center gap-2">
                <span className="text-sm text-[var(--text-primary)]">
                  {m.matched_label ?? "External case"}
                </span>
                <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-xs font-medium text-[var(--accent)]">
                  {Math.round(m.score * 100)}%
                </span>
                <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-xs text-[var(--text-muted)]">
                  {m.status}
                </span>
              </div>
              {m.matched_contact_href && m.matched_contact_name && (
                <p className="mt-1 text-xs text-[var(--text-muted)]">
                  Contact:{" "}
                  <a
                    href={m.matched_contact_href}
                    className="text-[var(--teal)] hover:underline"
                  >
                    {m.matched_contact_name}
                  </a>
                </p>
              )}
            </div>
          ))}
        </section>
      )}
    </div>
  );
}
