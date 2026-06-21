export function TimelineEmptyState() {
  return (
    <div className="flex items-center justify-center h-48 rounded-lg border border-dashed border-[var(--border-default)] bg-[var(--surface-raised)]">
      <p className="text-sm text-[var(--text-muted)]">No clinical events to display</p>
    </div>
  );
}
