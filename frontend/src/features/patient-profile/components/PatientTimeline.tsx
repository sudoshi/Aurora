import { useState, useMemo, useRef, useCallback, useEffect, useId } from "react";
import { Search, X, ZoomIn, ZoomOut } from "lucide-react";
import { cn } from "@/lib/utils";
import type { ClinicalEvent, ClinicalDomain, ObservationPeriod } from "../types/profile";

// ---------------------------------------------------------------------------
// Domain configuration
// ---------------------------------------------------------------------------

const DOMAIN_CONFIG: Record<
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
const DOMAIN_RESOLVED: Record<ClinicalDomain, string> = {
  condition:   "#00D68F",
  medication:  "#60A5FA",
  procedure:   "#F472B6",
  measurement: "#2DD4BF",
  observation: "#A78BFA",
  visit:       "#9D75F8",
};

const ALL_DOMAINS: ClinicalDomain[] = [
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

function parseDate(d: string): number {
  return new Date(d).getTime();
}

function formatTimelineDate(ms: number): string {
  return new Date(ms).toLocaleDateString("en-US", {
    month: "short",
    year: "numeric",
  });
}

function formatTooltipDate(d: string): string {
  return new Date(d).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function formatDuration(startDate: string, endDate: string): string {
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

const LANE_HEIGHT = 28;
const EVENT_HEIGHT = 8;
const EVENT_GAP = 3;
const MIN_EVENT_WIDTH = 4;
const MAX_ROWS = 12;
const TIMELINE_PADDING = 60;
const LABEL_WIDTH = 148;

interface PackedEvent {
  event: ClinicalEvent;
  row: number;
  startMs: number;
  endMs: number;
}

function packDomainEvents(
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

// ---------------------------------------------------------------------------
// Time Range Slider
// ---------------------------------------------------------------------------

const SLIDER_HANDLE_W = 8;

function TimeRangeSlider({
  viewStart,
  viewEnd,
  onViewChange,
  timeMin,
  timeMax,
  years,
}: {
  viewStart: number;
  viewEnd: number;
  onViewChange: (start: number, end: number) => void;
  timeMin: number;
  timeMax: number;
  years: number[];
}) {
  const trackRef = useRef<HTMLDivElement>(null);
  const dragging = useRef<"start" | "end" | "middle" | null>(null);
  const dragOrigin = useRef({ x: 0, start: 0, end: 0 });

  const fracToMs = (f: number) => timeMin + f * (timeMax - timeMin);

  const formatLabel = (f: number) => {
    const d = new Date(fracToMs(f));
    return d.toLocaleDateString("en-US", { month: "short", year: "numeric" });
  };

  const getFrac = (clientX: number) => {
    const rect = trackRef.current?.getBoundingClientRect();
    if (!rect) return 0;
    return Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
  };

  const handlePointerDown = (e: React.PointerEvent, target: "start" | "end" | "middle") => {
    e.preventDefault();
    (e.target as HTMLElement).setPointerCapture(e.pointerId);
    dragging.current = target;
    dragOrigin.current = { x: e.clientX, start: viewStart, end: viewEnd };
  };

  const handlePointerMove = (e: React.PointerEvent) => {
    if (!dragging.current) return;
    const minSpan = 0.02;

    if (dragging.current === "start") {
      const frac = getFrac(e.clientX);
      onViewChange(Math.min(frac, viewEnd - minSpan), viewEnd);
    } else if (dragging.current === "end") {
      const frac = getFrac(e.clientX);
      onViewChange(viewStart, Math.max(frac, viewStart + minSpan));
    } else {
      const dx = e.clientX - dragOrigin.current.x;
      const rect = trackRef.current?.getBoundingClientRect();
      if (!rect) return;
      const fracDx = dx / rect.width;
      const span = dragOrigin.current.end - dragOrigin.current.start;
      let newStart = dragOrigin.current.start + fracDx;
      let newEnd = newStart + span;
      if (newStart < 0) { newEnd -= newStart; newStart = 0; }
      if (newEnd > 1) { newStart -= newEnd - 1; newEnd = 1; }
      onViewChange(Math.max(0, newStart), Math.min(1, newEnd));
    }
  };

  const handlePointerUp = () => { dragging.current = null; };

  // Year tick marks for the slider track
  const yearTicks = years.map((y) => {
    const ms = new Date(`${y}-01-01`).getTime();
    return { year: y, frac: (ms - timeMin) / (timeMax - timeMin) };
  }).filter((t) => t.frac > 0.02 && t.frac < 0.98);

  return (
    <div className="flex items-center gap-3 px-4 py-2.5 bg-[var(--surface-base)] border-b border-[var(--surface-overlay)]">
      <span className="text-[10px] text-[var(--text-muted)] tabular-nums shrink-0 w-16 text-right">
        {formatLabel(viewStart)}
      </span>

      {/* Slider track */}
      <div
        ref={trackRef}
        className="relative flex-1 h-6 select-none"
        onPointerMove={handlePointerMove}
        onPointerUp={handlePointerUp}
        onPointerLeave={handlePointerUp}
      >
        {/* Background track */}
        <div className="absolute top-1/2 -translate-y-1/2 left-0 right-0 h-1.5 rounded-full bg-[var(--surface-overlay)]" />

        {/* Year tick marks */}
        {yearTicks.map((t) => (
          <div
            key={t.year}
            className="absolute top-0 flex flex-col items-center"
            style={{ left: `${t.frac * 100}%`, transform: "translateX(-50%)" }}
          >
            <span className="text-[8px] text-[var(--text-ghost)] leading-none">{t.year}</span>
            <div className="w-px h-1 bg-[var(--text-ghost)] opacity-40 mt-0.5" />
          </div>
        ))}

        {/* Active range fill */}
        <div
          className="absolute top-1/2 -translate-y-1/2 h-1.5 rounded-full cursor-grab active:cursor-grabbing"
          style={{
            left: `${viewStart * 100}%`,
            width: `${(viewEnd - viewStart) * 100}%`,
            background: "linear-gradient(90deg, #22D3EE, #00D68F, #9D75F8)",
            opacity: 0.7,
          }}
          onPointerDown={(e) => handlePointerDown(e, "middle")}
        />

        {/* Start handle */}
        <div
          className="absolute top-1/2 -translate-y-1/2 rounded-sm cursor-ew-resize"
          style={{
            left: `calc(${viewStart * 100}% - ${SLIDER_HANDLE_W / 2}px)`,
            width: SLIDER_HANDLE_W,
            height: 18,
            backgroundColor: "#22D3EE",
            border: "1px solid rgba(255,255,255,0.2)",
          }}
          onPointerDown={(e) => handlePointerDown(e, "start")}
        />

        {/* End handle */}
        <div
          className="absolute top-1/2 -translate-y-1/2 rounded-sm cursor-ew-resize"
          style={{
            left: `calc(${viewEnd * 100}% - ${SLIDER_HANDLE_W / 2}px)`,
            width: SLIDER_HANDLE_W,
            height: 18,
            backgroundColor: "#9D75F8",
            border: "1px solid rgba(255,255,255,0.2)",
          }}
          onPointerDown={(e) => handlePointerDown(e, "end")}
        />
      </div>

      <span className="text-[10px] text-[var(--text-muted)] tabular-nums shrink-0 w-16">
        {formatLabel(viewEnd)}
      </span>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

interface PatientTimelineProps {
  events: ClinicalEvent[];
  observationPeriods?: ObservationPeriod[];
  onEventClick?: (event: ClinicalEvent) => void;
}

export function PatientTimeline({ events, observationPeriods = [], onEventClick }: PatientTimelineProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [collapsedDomains, setCollapsedDomains] = useState<Set<ClinicalDomain>>(new Set());
  const [hiddenDomains, setHiddenDomains] = useState<Set<ClinicalDomain>>(new Set());
  const [tooltip, setTooltip] = useState<{
    event: ClinicalEvent;
    x: number;
    y: number;
  } | null>(null);
  const [searchQuery, setSearchQuery] = useState("");

  const instanceId = useId();
  const clipId = `chart-clip-${instanceId}`;

  // View range: 0–1 fractions of the full time range
  const [viewStart, setViewStart] = useState(0);
  const [viewEnd, setViewEnd] = useState(1);
  const isDragging = useRef(false);
  const dragStart = useRef(0);
  const viewStartOnDrag = useRef(0);
  const hasSetInitialView = useRef(false);

  const [containerWidth, setContainerWidth] = useState(0);
  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;
    const ro = new ResizeObserver((entries) => {
      for (const entry of entries) {
        setContainerWidth(entry.contentRect.width);
      }
    });
    ro.observe(el);
    return () => ro.disconnect();
  }, []);
  const svgWidth = containerWidth > 0 ? Math.round(containerWidth) : 900;
  const chartWidth = svgWidth - LABEL_WIDTH - TIMELINE_PADDING;

  // Derive zoom and panOffset from the view range
  const viewSpan = Math.max(viewEnd - viewStart, 0.01);
  const zoom = 1 / viewSpan;
  const panOffset = -viewStart * chartWidth * zoom;

  // Group events by domain
  const domainEvents = useMemo(() => {
    const grouped: Record<ClinicalDomain, ClinicalEvent[]> = {
      condition: [],
      medication: [],
      procedure: [],
      measurement: [],
      observation: [],
      visit: [],
    };
    for (const ev of events) {
      if (grouped[ev.domain] && !hiddenDomains.has(ev.domain)) {
        grouped[ev.domain].push(ev);
      }
    }
    return grouped;
  }, [events, hiddenDomains]);

  // Compute time bounds — always extend to today so the "Today" marker is visible
  const { timeMin, timeMax } = useMemo(() => {
    const now = Date.now();
    if (events.length === 0) {
      return { timeMin: now - 365 * 24 * 60 * 60 * 1000, timeMax: now };
    }
    let min = Infinity;
    let max = -Infinity;
    for (const ev of events) {
      const start = parseDate(ev.start_date);
      if (start < min) min = start;
      if (start > max) max = start;
      if (ev.end_date) {
        const end = parseDate(ev.end_date);
        if (end > max) max = end;
      }
    }
    for (const op of observationPeriods) {
      const s = parseDate(op.start_date);
      const e = parseDate(op.end_date);
      if (s < min) min = s;
      if (e > max) max = e;
    }
    // Ensure today is always within the visible range
    if (now > max) max = now;
    const range = max - min || 365 * 24 * 60 * 60 * 1000;
    return { timeMin: min - range * 0.03, timeMax: max + range * 0.02 };
  }, [events, observationPeriods]);

  const timeRange = timeMax - timeMin;

  // Smart initial view: show last 5 years for long histories
  useEffect(() => {
    if (hasSetInitialView.current || events.length === 0) return;
    hasSetInitialView.current = true;
    const FIVE_YEARS_MS = 5 * 365.25 * 24 * 60 * 60 * 1000;
    const totalMs = timeMax - timeMin;
    if (totalMs <= FIVE_YEARS_MS) return;
    const startFrac = Math.max(0, 1 - FIVE_YEARS_MS / totalMs);
    setViewStart(startFrac);
    setViewEnd(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [events]);

  const activeDomains = useMemo(
    () =>
      ALL_DOMAINS
        .filter((d) => domainEvents[d].length > 0)
        .sort((a, b) => DOMAIN_CONFIG[a].order - DOMAIN_CONFIG[b].order),
    [domainEvents],
  );

  const allPresentDomains = useMemo(
    () => ALL_DOMAINS.filter((d) => events.filter((e) => e.domain === d).length > 0),
    [events],
  );

  const packedLayouts = useMemo(() => {
    const layouts: Record<ClinicalDomain, { packed: PackedEvent[]; rowCount: number }> = {
      condition: { packed: [], rowCount: 0 },
      medication: { packed: [], rowCount: 0 },
      procedure: { packed: [], rowCount: 0 },
      measurement: { packed: [], rowCount: 0 },
      observation: { packed: [], rowCount: 0 },
      visit: { packed: [], rowCount: 0 },
    };
    for (const domain of ALL_DOMAINS) {
      if (domainEvents[domain].length > 0) {
        layouts[domain] = packDomainEvents(domainEvents[domain], timeRange);
      }
    }
    return layouts;
  }, [domainEvents, timeRange]);

  const toggleCollapse = (domain: ClinicalDomain) => {
    setCollapsedDomains((prev) => {
      const next = new Set(prev);
      if (next.has(domain)) next.delete(domain);
      else next.add(domain);
      return next;
    });
  };

  const toggleHide = (domain: ClinicalDomain) => {
    setHiddenDomains((prev) => {
      const next = new Set(prev);
      if (next.has(domain)) next.delete(domain);
      else next.add(domain);
      return next;
    });
  };

  let yOffset = 34;
  const lanePositions: { domain: ClinicalDomain; y: number; height: number }[] = [];
  for (const domain of activeDomains) {
    const isCollapsed = collapsedDomains.has(domain);
    const rows = isCollapsed ? 0 : packedLayouts[domain].rowCount;
    const height = isCollapsed ? LANE_HEIGHT : LANE_HEIGHT + rows * (EVENT_HEIGHT + EVENT_GAP);
    lanePositions.push({ domain, y: yOffset, height });
    yOffset += height + 2;
  }

  const svgHeight = Math.max(yOffset + 10, 120);

  const timeToX = useCallback(
    (t: number) => {
      const normalized = (t - timeMin) / timeRange;
      return LABEL_WIDTH + (normalized * chartWidth * zoom + panOffset);
    },
    [timeMin, timeRange, chartWidth, zoom, panOffset],
  );

  const ticks = useMemo(() => {
    const count = Math.max(4, Math.floor(chartWidth / 110));
    const result: { x: number; label: string }[] = [];
    for (let i = 0; i <= count; i++) {
      const t = timeMin + (timeRange * i) / count;
      const x = timeToX(t);
      if (x >= LABEL_WIDTH && x <= svgWidth - 10) {
        result.push({ x, label: formatTimelineDate(t) });
      }
    }
    return result;
  }, [timeMin, timeRange, timeToX, chartWidth, svgWidth]);

  const years = useMemo(() => {
    const startYear = new Date(timeMin).getFullYear();
    const endYear = new Date(timeMax).getFullYear();
    const result: number[] = [];
    for (let y = startYear; y <= endYear; y++) result.push(y);
    return result;
  }, [timeMin, timeMax]);

  const obsPeriodBands = useMemo(() => {
    return observationPeriods.map((op) => ({
      x1: timeToX(parseDate(op.start_date)),
      x2: timeToX(parseDate(op.end_date)),
    }));
  }, [observationPeriods, timeToX]);

  const todayX = useMemo(() => {
    const now = Date.now();
    if (now < timeMin || now > timeMax) return null;
    return timeToX(now);
  }, [timeMin, timeMax, timeToX]);

  const matchingEventKey = useMemo(() => {
    if (!searchQuery.trim()) return null;
    const q = searchQuery.toLowerCase();
    const set = new Set<ClinicalEvent>();
    for (const ev of events) {
      if (ev.concept_name.toLowerCase().includes(q)) set.add(ev);
    }
    return set;
  }, [events, searchQuery]);

  const handleWheel = (e: React.WheelEvent) => {
    if (!e.ctrlKey && !e.metaKey) return;
    e.preventDefault();
    const rect = containerRef.current?.getBoundingClientRect();
    if (!rect) return;
    const cursorFrac = viewStart + ((e.clientX - rect.left - LABEL_WIDTH) / chartWidth) * viewSpan;
    const factor = e.deltaY > 0 ? 1.1 : 0.9;
    const newSpan = Math.max(0.01, Math.min(1, viewSpan * factor));
    const ratio = (cursorFrac - viewStart) / viewSpan;
    let newStart = cursorFrac - ratio * newSpan;
    let newEnd = newStart + newSpan;
    if (newStart < 0) { newEnd -= newStart; newStart = 0; }
    if (newEnd > 1) { newStart -= newEnd - 1; newEnd = 1; }
    setViewStart(Math.max(0, newStart));
    setViewEnd(Math.min(1, newEnd));
  };

  const zoomBy = (factor: number) => {
    const center = (viewStart + viewEnd) / 2;
    const newSpan = Math.max(0.01, Math.min(1, viewSpan * factor));
    let newStart = center - newSpan / 2;
    let newEnd = center + newSpan / 2;
    if (newStart < 0) { newEnd -= newStart; newStart = 0; }
    if (newEnd > 1) { newStart -= newEnd - 1; newEnd = 1; }
    setViewStart(Math.max(0, newStart));
    setViewEnd(Math.min(1, newEnd));
  };

  const handleZoomIn = () => zoomBy(0.7);
  const handleZoomOut = () => zoomBy(1.4);

  const handleMouseDown = (e: React.MouseEvent) => {
    isDragging.current = true;
    dragStart.current = e.clientX;
    viewStartOnDrag.current = viewStart;
    setTooltip(null);
  };

  const handleMouseMove = (e: React.MouseEvent) => {
    if (!isDragging.current) return;
    const dx = e.clientX - dragStart.current;
    const fracDx = -(dx / chartWidth) * viewSpan;
    let newStart = viewStartOnDrag.current + fracDx;
    let newEnd = newStart + viewSpan;
    if (newStart < 0) { newEnd -= newStart; newStart = 0; }
    if (newEnd > 1) { newStart -= newEnd - 1; newEnd = 1; }
    setViewStart(Math.max(0, newStart));
    setViewEnd(Math.min(1, newEnd));
  };

  const handleMouseUp = () => { isDragging.current = false; };

  if (events.length === 0) {
    return (
      <div className="flex items-center justify-center h-48 rounded-lg border border-dashed border-[var(--border-default)] bg-[var(--surface-raised)]">
        <p className="text-sm text-[var(--text-muted)]">No clinical events to display</p>
      </div>
    );
  }

  return (
    <div className="relative rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] overflow-hidden">
      {/* Toolbar */}
      <div className="flex items-center justify-between gap-3 px-4 py-2 bg-[var(--surface-overlay)] border-b border-[var(--border-default)] flex-wrap">
        <div className="flex items-center gap-2">
          <span className="text-xs text-[var(--text-muted)]">
            {events.length} events &middot; {activeDomains.length} domains
          </span>
        </div>

        <div className="flex items-center gap-2">
          <div className="relative">
            <Search
              size={11}
              className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--text-ghost)]"
            />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Highlight events..."
              className={cn(
                "w-44 rounded-md border border-[var(--border-default)] bg-[var(--surface-base)] pl-7 pr-2 py-1 text-xs",
                "text-[var(--text-primary)] placeholder:text-[var(--text-ghost)]",
                "focus:border-[var(--border-focus)] focus:outline-none",
              )}
            />
            {searchQuery && (
              <button
                type="button"
                onClick={() => setSearchQuery("")}
                className="absolute right-2 top-1/2 -translate-y-1/2 text-[var(--text-ghost)] hover:text-[var(--text-primary)]"
              >
                <X size={10} />
              </button>
            )}
          </div>

          <div className="flex items-center gap-0.5 rounded-md border border-[var(--border-default)] bg-[var(--surface-base)]">
            <button
              type="button"
              onClick={handleZoomOut}
              disabled={zoom <= 0.5}
              className="p-1.5 text-[var(--text-muted)] hover:text-[var(--text-primary)] disabled:text-[var(--text-disabled)] disabled:cursor-not-allowed transition-colors"
            >
              <ZoomOut size={12} />
            </button>
            <span className="text-[10px] text-[var(--text-ghost)] w-8 text-center tabular-nums">
              {Math.round(zoom * 100)}%
            </span>
            <button
              type="button"
              onClick={handleZoomIn}
              disabled={zoom >= 10}
              className="p-1.5 text-[var(--text-muted)] hover:text-[var(--text-primary)] disabled:text-[var(--text-disabled)] disabled:cursor-not-allowed transition-colors"
            >
              <ZoomIn size={12} />
            </button>
          </div>
          <button
            type="button"
            onClick={() => { setViewStart(0); setViewEnd(1); }}
            className="text-[10px] text-[var(--text-muted)] hover:text-[var(--text-primary)] transition-colors px-2 py-1 rounded border border-[var(--border-default)]"
          >
            Reset
          </button>
        </div>
      </div>

      {/* Domain filter toggles */}
      <div className="flex items-center gap-1.5 px-4 py-2 bg-[var(--surface-raised)] border-b border-[var(--border-default)] overflow-x-auto">
        <span className="text-[10px] text-[var(--text-ghost)] shrink-0 mr-1">Domains:</span>
        {allPresentDomains.map((domain) => {
          const cfg = DOMAIN_CONFIG[domain];
          const resolvedColor = DOMAIN_RESOLVED[domain];
          const hidden = hiddenDomains.has(domain);
          const count = events.filter((e) => e.domain === domain).length;
          return (
            <button
              key={domain}
              type="button"
              onClick={() => toggleHide(domain)}
              className={cn(
                "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-medium border transition-all shrink-0",
                hidden && "border-[var(--border-default)] text-[var(--text-ghost)] bg-transparent",
              )}
              style={
                hidden
                  ? {}
                  : {
                      backgroundColor: `${resolvedColor}15`,
                      color: resolvedColor,
                      borderColor: `${resolvedColor}40`,
                    }
              }
            >
              {cfg.label} ({count})
            </button>
          );
        })}
      </div>

      {/* Time range slider */}
      <TimeRangeSlider
        viewStart={viewStart}
        viewEnd={viewEnd}
        onViewChange={(s, e) => { setViewStart(s); setViewEnd(e); }}
        timeMin={timeMin}
        timeMax={timeMax}
        years={years}
      />

      {/* SVG Timeline */}
      <div
        ref={containerRef}
        className="overflow-hidden cursor-grab active:cursor-grabbing"
        tabIndex={0}
        onWheel={handleWheel}
        onMouseDown={handleMouseDown}
        onMouseMove={handleMouseMove}
        onMouseUp={handleMouseUp}
        onMouseLeave={handleMouseUp}
      >
        <svg
          width="100%"
          viewBox={`0 0 ${svgWidth} ${svgHeight}`}
          className="select-none"
        >
          <defs>
            <clipPath id={clipId}>
              <rect
                x={LABEL_WIDTH}
                y={0}
                width={chartWidth + TIMELINE_PADDING}
                height={svgHeight}
              />
            </clipPath>
          </defs>

          {/* Observation period bands */}
          <g clipPath={`url(#${clipId})`}>
            {obsPeriodBands.map((band, i) => {
              const bw = Math.max(band.x2 - band.x1, 2);
              return (
                <rect
                  key={i}
                  x={band.x1}
                  y={28}
                  width={bw}
                  height={svgHeight - 28}
                  fill="#2DD4BF"
                  opacity={0.05}
                />
              );
            })}
          </g>

          {/* Time axis */}
          <g clipPath={`url(#${clipId})`}>
            <line
              x1={LABEL_WIDTH}
              x2={svgWidth}
              y1={26}
              y2={26}
              stroke="#2A2A60"
              strokeWidth={1}
            />
            {ticks.map((tick, i) => (
              <g key={i}>
                <line x1={tick.x} x2={tick.x} y1={22} y2={30} stroke="#4A5068" strokeWidth={1} />
                <text x={tick.x} y={18} textAnchor="middle" fill="#7A8298" style={{ fontSize: 9 }}>
                  {tick.label}
                </text>
                <line
                  x1={tick.x} x2={tick.x} y1={30} y2={svgHeight}
                  stroke="#16163A" strokeWidth={1} strokeDasharray="2 4"
                />
              </g>
            ))}

            {todayX != null && (
              <g>
                <line
                  x1={todayX} x2={todayX} y1={26} y2={svgHeight}
                  stroke="#9D75F8" strokeWidth={1} strokeDasharray="3 3" opacity={0.5}
                />
                <text x={todayX + 3} y={18} fill="#9D75F8" style={{ fontSize: 8 }}>
                  Today
                </text>
              </g>
            )}
          </g>

          {/* Swim lanes */}
          {lanePositions.map(({ domain, y, height }) => {
            const config = DOMAIN_CONFIG[domain];
            const resolvedColor = DOMAIN_RESOLVED[domain];
            const isCollapsed = collapsedDomains.has(domain);
            const domEvts = domainEvents[domain];

            return (
              <g key={domain}>
                <rect x={0} y={y} width={svgWidth} height={height} fill={`${resolvedColor}04`} />
                <line x1={0} x2={svgWidth} y1={y} y2={y} stroke="#16163A" strokeWidth={1} />

                <g className="cursor-pointer" onClick={() => toggleCollapse(domain)}>
                  <rect x={0} y={y} width={LABEL_WIDTH} height={LANE_HEIGHT} fill="transparent" />
                  <text x={10} y={y + LANE_HEIGHT / 2 + 4} fill="#4A5068" style={{ fontSize: 8 }}>
                    {isCollapsed ? "\u25B6" : "\u25BC"}
                  </text>
                  <rect x={22} y={y + LANE_HEIGHT / 2 - 4} width={8} height={8} rx={2} fill={resolvedColor} />
                  <text x={36} y={y + LANE_HEIGHT / 2 + 3} fill="#B4BAC8" style={{ fontSize: 10, fontWeight: 500 }}>
                    {config.label}
                  </text>
                  <text x={LABEL_WIDTH - 6} y={y + LANE_HEIGHT / 2 + 3} textAnchor="end" fill="#4A5068" style={{ fontSize: 9 }}>
                    {domEvts.length}
                  </text>
                </g>

                {!isCollapsed && (
                  <g clipPath={`url(#${clipId})`}>
                    {packedLayouts[domain].packed.map((pe, peIdx) => {
                      const ev = pe.event;
                      const startX = timeToX(pe.startMs);
                      const endX = ev.end_date ? timeToX(pe.endMs) : startX + MIN_EVENT_WIDTH;
                      const w = Math.max(endX - startX, MIN_EVENT_WIDTH);
                      const evY = y + LANE_HEIGHT + pe.row * (EVENT_HEIGHT + EVENT_GAP);

                      const isSingleDay = !ev.end_date || w <= MIN_EVENT_WIDTH + 2;
                      const isMatch = matchingEventKey != null ? matchingEventKey.has(ev) : true;
                      const opacity = matchingEventKey != null ? (isMatch ? 1.0 : 0.15) : 0.75;
                      const hitH = EVENT_HEIGHT + EVENT_GAP;

                      return (
                        <g
                          key={peIdx}
                          onMouseEnter={(e) => {
                            if (isDragging.current) return;
                            const rect = containerRef.current?.getBoundingClientRect();
                            if (rect) setTooltip({ event: ev, x: e.clientX - rect.left, y: e.clientY - rect.top });
                          }}
                          onMouseMove={(e) => {
                            if (isDragging.current) return;
                            const rect = containerRef.current?.getBoundingClientRect();
                            if (rect) setTooltip({ event: ev, x: e.clientX - rect.left, y: e.clientY - rect.top });
                          }}
                          onMouseLeave={() => setTooltip(null)}
                          onClick={() => onEventClick?.(ev)}
                          className="cursor-pointer"
                          opacity={opacity}
                        >
                          <rect x={startX - 4} y={evY - 1} width={Math.max(w + 8, 16)} height={hitH} fill="transparent" />
                          {isSingleDay ? (
                            <circle cx={startX} cy={evY + EVENT_HEIGHT / 2} r={EVENT_HEIGHT / 2} fill={resolvedColor} />
                          ) : (
                            <rect x={startX} y={evY} width={w} height={EVENT_HEIGHT} rx={2} fill={resolvedColor} />
                          )}
                          {isMatch && matchingEventKey != null && (
                            isSingleDay ? (
                              <circle cx={startX} cy={evY + EVENT_HEIGHT / 2} r={EVENT_HEIGHT / 2 + 2} fill="none" stroke={resolvedColor} strokeWidth={1} opacity={0.6} />
                            ) : (
                              <rect x={startX - 1} y={evY - 1} width={w + 2} height={EVENT_HEIGHT + 2} rx={3} fill="none" stroke={resolvedColor} strokeWidth={1} opacity={0.6} />
                            )
                          )}
                        </g>
                      );
                    })}
                  </g>
                )}
              </g>
            );
          })}

          {/* Obs period border lines */}
          <g clipPath={`url(#${clipId})`}>
            {obsPeriodBands.map((band, i) => (
              <g key={i}>
                <line x1={band.x1} x2={band.x1} y1={28} y2={svgHeight} stroke="#2DD4BF" strokeWidth={1} strokeDasharray="3 2" opacity={0.3} />
                <line x1={band.x2} x2={band.x2} y1={28} y2={svgHeight} stroke="#2DD4BF" strokeWidth={1} strokeDasharray="3 2" opacity={0.3} />
              </g>
            ))}
          </g>
        </svg>
      </div>

      {/* Tooltip */}
      {tooltip && !isDragging.current && (() => {
        const ev = tooltip.event;
        const TOOLTIP_W = 260;
        const TOOLTIP_OFFSET = 14;
        const containerW = containerRef.current?.clientWidth ?? svgWidth;
        const leftPos = tooltip.x + TOOLTIP_OFFSET + TOOLTIP_W > containerW
          ? tooltip.x - TOOLTIP_W - TOOLTIP_OFFSET
          : tooltip.x + TOOLTIP_OFFSET;
        const duration = ev.end_date && ev.end_date !== ev.start_date
          ? formatDuration(ev.start_date, ev.end_date)
          : null;
        const resolvedColor = DOMAIN_RESOLVED[ev.domain];
        return (
          <div
            className="absolute pointer-events-none z-50"
            style={{ left: Math.max(4, leftPos), top: tooltip.y - 10 }}
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
                  {ev.end_date && ev.end_date !== ev.start_date && ` \u2013 ${formatTooltipDate(ev.end_date)}`}
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
      })()}

      {/* Legend */}
      <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-2 border-t border-[var(--border-default)] bg-[var(--surface-overlay)]">
        <div className="flex flex-wrap gap-3">
          {activeDomains.map((domain) => {
            const resolvedColor = DOMAIN_RESOLVED[domain];
            return (
              <div key={domain} className="flex items-center gap-1.5">
                <div className="w-2.5 h-2.5 rounded-sm" style={{ backgroundColor: resolvedColor }} />
                <span className="text-[10px] text-[var(--text-muted)]">
                  {DOMAIN_CONFIG[domain].label} ({domainEvents[domain].length})
                </span>
              </div>
            );
          })}
        </div>
        <span className="text-[10px] text-[var(--text-disabled)]">
          Ctrl+scroll to zoom &middot; Drag to pan &middot; Click event for details
        </span>
      </div>
    </div>
  );
}
