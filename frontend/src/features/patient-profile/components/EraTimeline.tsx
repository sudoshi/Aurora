import type { ConditionEra, DrugEra } from "../types/profile";

interface EraTimelineProps {
  conditionEras: ConditionEra[];
  drugEras: DrugEra[];
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function EraBar({
  startDate,
  endDate,
  minDate,
  maxDate,
  color,
}: {
  startDate: string;
  endDate: string;
  minDate: number;
  maxDate: number;
  color: string;
}) {
  const range = maxDate - minDate || 1;
  const start = new Date(startDate).getTime();
  const end = new Date(endDate).getTime();
  const left = ((start - minDate) / range) * 100;
  const width = Math.max(((end - start) / range) * 100, 0.5);

  return (
    <div
      className="absolute top-0 h-full rounded-sm"
      style={{
        left: `${left}%`,
        width: `${width}%`,
        backgroundColor: color,
        opacity: 0.7,
      }}
    />
  );
}

export function EraTimeline({ conditionEras, drugEras }: EraTimelineProps) {
  const allDates = [
    ...conditionEras.flatMap((e) => [
      new Date(e.start_date).getTime(),
      new Date(e.end_date).getTime(),
    ]),
    ...drugEras.flatMap((e) => [
      new Date(e.start_date).getTime(),
      new Date(e.end_date).getTime(),
    ]),
  ];

  if (allDates.length === 0) {
    return (
      <div className="rounded-lg border border-dashed border-[var(--border-default)] bg-[var(--surface-raised)] p-6 text-center">
        <p className="text-xs text-[var(--text-muted)]">No era data available</p>
      </div>
    );
  }

  const minDate = Math.min(...allDates);
  const maxDate = Math.max(...allDates);

  return (
    <div className="space-y-4">
      {conditionEras.length > 0 && (
        <div>
          <h4 className="text-xs font-semibold text-[var(--domain-condition)] mb-2 uppercase tracking-wider">
            Condition Eras ({conditionEras.length})
          </h4>
          <div className="space-y-1">
            {conditionEras.map((era) => (
              <div key={era.id} className="flex items-center gap-3 group">
                <div className="w-48 shrink-0 truncate">
                  <span className="text-xs text-[var(--text-primary)]">{era.condition_name}</span>
                </div>
                <div className="flex-1 relative h-4 rounded bg-[var(--surface-overlay)] border border-[var(--border-default)]">
                  <EraBar
                    startDate={era.start_date}
                    endDate={era.end_date}
                    minDate={minDate}
                    maxDate={maxDate}
                    color="#00D68F"
                  />
                </div>
                <div className="w-28 shrink-0 text-right">
                  <span className="text-[10px] text-[var(--text-muted)]">
                    {formatDate(era.start_date)} - {formatDate(era.end_date)}
                  </span>
                </div>
                <div className="w-12 shrink-0 text-right">
                  <span className="text-[10px] font-mono text-[var(--text-ghost)]">
                    x{era.occurrence_count}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {drugEras.length > 0 && (
        <div>
          <h4 className="text-xs font-semibold text-[var(--domain-drug)] mb-2 uppercase tracking-wider">
            Drug Eras ({drugEras.length})
          </h4>
          <div className="space-y-1">
            {drugEras.map((era) => (
              <div key={era.id} className="flex items-center gap-3 group">
                <div className="w-48 shrink-0 truncate">
                  <span className="text-xs text-[var(--text-primary)]">{era.drug_name}</span>
                </div>
                <div className="flex-1 relative h-4 rounded bg-[var(--surface-overlay)] border border-[var(--border-default)]">
                  <EraBar
                    startDate={era.start_date}
                    endDate={era.end_date}
                    minDate={minDate}
                    maxDate={maxDate}
                    color="#60A5FA"
                  />
                </div>
                <div className="w-28 shrink-0 text-right">
                  <span className="text-[10px] text-[var(--text-muted)]">
                    {formatDate(era.start_date)} - {formatDate(era.end_date)}
                  </span>
                </div>
                <div className="w-12 shrink-0 text-right">
                  <span className="text-[10px] font-mono text-[var(--text-ghost)]">
                    x{era.exposure_count}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
