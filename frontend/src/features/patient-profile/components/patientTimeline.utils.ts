import type { ClinicalDomain, ClinicalEvent } from "../types/profile";

// ---------------------------------------------------------------------------
// Domain configuration
// ---------------------------------------------------------------------------

export const DOMAIN_CONFIG: Record<
  ClinicalDomain,
  { label: string; color: string; order: number }
> = {
  condition:   { label: "Conditions",   color: "var(--domain-condition)",   order: 0 },
  medication:  { label: "Medications",  color: "var(--domain-drug)",        order: 1 },
  procedure:   { label: "Procedures",   color: "var(--domain-procedure)",   order: 2 },
  measurement: { label: "Measurements", color: "var(--domain-measurement)", order: 3 },
  observation: { label: "Observations", color: "var(--domain-observation)", order: 4 },
  visit:       { label: "Visits",       color: "var(--domain-visit)",       order: 5 },
};

// Resolved colors for SVG rendering (CSS vars don't work in SVG fill/stroke)
export const DOMAIN_RESOLVED: Record<ClinicalDomain, string> = {
  condition:   "#00D68F",
  medication:  "#60A5FA",
  procedure:   "#F472B6",
  measurement: "#2DD4BF",
  observation: "#A78BFA",
  visit:       "#9D75F8",
};

export const ALL_DOMAINS: ClinicalDomain[] = [
  "condition",
  "medication",
  "procedure",
  "measurement",
  "observation",
  "visit",
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

export function parseDate(d: string): number {
  return new Date(d).getTime();
}

export function formatTimelineDate(ms: number): string {
  return new Date(ms).toLocaleDateString("en-US", {
    month: "short",
    year: "numeric",
  });
}

export function formatTooltipDate(d: string): string {
  return new Date(d).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

export function formatDuration(startDate: string, endDate: string): string {
  const diffMs = new Date(endDate).getTime() - new Date(startDate).getTime();
  if (diffMs <= 0) return "";
  const days = Math.round(diffMs / (24 * 60 * 60 * 1000));
  if (days === 0) return "same day";
  if (days === 1) return "1 day";
  if (days < 30) return `${days} days`;
  const months = Math.round(days / 30.44);
  if (months < 12) return months === 1 ? "1 month" : `${months} months`;
  const years = Math.round(days / 365.25);
  return years === 1 ? "1 year" : `${years} years`;
}

// ---------------------------------------------------------------------------
// Lane packing
// ---------------------------------------------------------------------------

export const LANE_HEIGHT = 28;
export const EVENT_HEIGHT = 8;
export const EVENT_GAP = 3;
export const MIN_EVENT_WIDTH = 4;
export const MAX_ROWS = 12;
export const TIMELINE_PADDING = 60;
export const LABEL_WIDTH = 148;

export interface PackedEvent {
  event: ClinicalEvent;
  row: number;
  startMs: number;
  endMs: number;
}

export function packDomainEvents(
  events: ClinicalEvent[],
  timeRange: number,
): { packed: PackedEvent[]; rowCount: number } {
  if (events.length === 0) return { packed: [], rowCount: 0 };

  const minGapMs = timeRange * 0.008;

  const items = events.map((ev) => ({
    event: ev,
    startMs: parseDate(ev.start_date),
    endMs: ev.end_date ? parseDate(ev.end_date) : parseDate(ev.start_date),
  }));
  items.sort((a, b) => a.startMs - b.startMs || (a.endMs - a.startMs) - (b.endMs - b.startMs));

  const rowEnds: number[] = [];
  const packed: PackedEvent[] = [];

  for (const item of items) {
    const effectiveEnd = Math.max(item.endMs, item.startMs + minGapMs);
    let assignedRow = -1;

    for (let r = 0; r < rowEnds.length; r++) {
      if (rowEnds[r] <= item.startMs) {
        assignedRow = r;
        break;
      }
    }

    if (assignedRow === -1) {
      if (rowEnds.length < MAX_ROWS) {
        assignedRow = rowEnds.length;
        rowEnds.push(0);
      } else {
        assignedRow = 0;
        let minEnd = rowEnds[0];
        for (let r = 1; r < MAX_ROWS; r++) {
          if (rowEnds[r] < minEnd) {
            minEnd = rowEnds[r];
            assignedRow = r;
          }
        }
      }
    }

    rowEnds[assignedRow] = effectiveEnd;
    packed.push({ ...item, row: assignedRow });
  }

  return { packed, rowCount: rowEnds.length };
}
