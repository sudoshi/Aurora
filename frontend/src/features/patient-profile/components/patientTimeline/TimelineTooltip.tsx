import type { ClinicalEvent } from "../../types/profile";
import {
  DOMAIN_CONFIG,
  DOMAIN_RESOLVED,
  formatDuration,
  formatTooltipDate,
} from "../patientTimeline.utils";

interface TimelineTooltipProps {
  event: ClinicalEvent;
  x: number;
  y: number;
  containerWidth: number;
}

export function TimelineTooltip({ event: ev, x, y, containerWidth }: TimelineTooltipProps) {
  const TOOLTIP_W = 260;
  const TOOLTIP_OFFSET = 14;
  const leftPos = x + TOOLTIP_OFFSET + TOOLTIP_W > containerWidth
    ? x - TOOLTIP_W - TOOLTIP_OFFSET
    : x + TOOLTIP_OFFSET;
  const duration = ev.end_date && ev.end_date !== ev.start_date
    ? formatDuration(ev.start_date, ev.end_date)
    : null;
  const resolvedColor = DOMAIN_RESOLVED[ev.domain];

  return (
    <div
      className="absolute pointer-events-none z-50"
      style={{ left: Math.max(4, leftPos), top: y - 10 }}
    >
      <div className="rounded-lg bg-[var(--surface-base)] border border-[var(--border-default)] px-3 py-2 shadow-xl" style={{ maxWidth: TOOLTIP_W }}>
        <p className="text-xs font-semibold text-[var(--text-primary)]">
          {ev.concept_name}
        </p>
        <div className="mt-1 space-y-0.5">
          <p className="text-[10px] text-[var(--text-muted)]">
            <span
              className="inline-block w-2 h-2 rounded-sm mr-1"
              style={{ backgroundColor: resolvedColor }}
            />
            {DOMAIN_CONFIG[ev.domain].label}
          </p>
          <p className="text-[10px] text-[var(--text-muted)]">
            {formatTooltipDate(ev.start_date)}
            {ev.end_date && ev.end_date !== ev.start_date && ` – ${formatTooltipDate(ev.end_date)}`}
            {duration && <span className="ml-1 text-[var(--text-ghost)]">({duration})</span>}
          </p>
          {ev.value_numeric != null && (
            <p className="text-[10px] text-[var(--warning)]">
              {String(ev.value_numeric)}{ev.unit ? ` ${ev.unit}` : ""}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
