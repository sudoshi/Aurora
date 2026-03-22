import { useState } from "react";
import { TrendingDown, TrendingUp, Minus, ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";
import type { ClinicalEvent, ClinicalDomain } from "../types/profile";

const DOMAIN_COLORS: Record<ClinicalDomain, string> = {
  condition:   "#00D68F",
  medication:  "#60A5FA",
  procedure:   "#F472B6",
  measurement: "#2DD4BF",
  observation: "#A78BFA",
  visit:       "#9D75F8",
};

const DOMAIN_LABELS: Record<ClinicalDomain, string> = {
  condition:   "Condition",
  medication:  "Medication",
  procedure:   "Procedure",
  measurement: "Measurement",
  observation: "Observation",
  visit:       "Visit",
};

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function RangeStatus({
  value,
  rangeLow,
  rangeHigh,
}: {
  value: number;
  rangeLow: number | null | undefined;
  rangeHigh: number | null | undefined;
}) {
  if (rangeLow == null || rangeHigh == null) return null;
  if (value < rangeLow) {
    return (
      <span className="inline-flex items-center gap-0.5 text-[10px] text-[var(--info)]">
        <TrendingDown size={10} /> Below range ({rangeLow})
      </span>
    );
  }
  if (value > rangeHigh) {
    return (
      <span className="inline-flex items-center gap-0.5 text-[10px] text-[var(--critical)]">
        <TrendingUp size={10} /> Above range ({rangeHigh})
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-0.5 text-[10px] text-[var(--success)]">
      <Minus size={10} /> Normal ({rangeLow}&ndash;{rangeHigh})
    </span>
  );
}

// ---------------------------------------------------------------------------
// Grouped concept card
// ---------------------------------------------------------------------------

interface GroupedConceptCardProps {
  conceptName: string;
  domain: ClinicalDomain;
  events: ClinicalEvent[];
  firstDate: string;
  lastDate: string;
}

export function GroupedConceptCard({
  conceptName,
  domain,
  events,
  firstDate,
  lastDate,
}: GroupedConceptCardProps) {
  const [expanded, setExpanded] = useState(false);
  const color = DOMAIN_COLORS[domain] ?? "#7A8298";
  const label = DOMAIN_LABELS[domain] ?? domain;
  const count = events.length;
  const latestWithValue = domain === "measurement" ? events.find((e) => e.value_numeric != null) : null;

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)]">
      <div
        className={cn(
          "flex items-start justify-between gap-3 p-3 transition-colors",
          count > 1 && "cursor-pointer hover:bg-[var(--surface-overlay)]",
        )}
        onClick={() => count > 1 && setExpanded((v) => !v)}
      >
        <div className="min-w-0 flex-1 space-y-1">
          <p className="text-sm font-medium text-[var(--text-primary)] truncate">{conceptName}</p>
          <p className="text-xs text-[var(--text-muted)]">
            {count === 1 || firstDate === lastDate
              ? formatDate(firstDate)
              : `${formatDate(firstDate)} \u2013 ${formatDate(lastDate)}`}
          </p>
          {latestWithValue?.value_numeric != null && (
            <p className="text-xs font-semibold text-[var(--warning)]">
              Latest: {String(latestWithValue.value_numeric)}
              {latestWithValue.unit ? ` ${latestWithValue.unit}` : ""}
            </p>
          )}
        </div>
        <div className="flex items-center gap-2 shrink-0">
          {count > 1 && (
            <span className="text-[10px] text-[var(--text-muted)] bg-[var(--surface-elevated)] rounded-full px-2 py-0.5">
              {count}x
            </span>
          )}
          <span
            className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
            style={{
              backgroundColor: `${color}15`,
              color,
              border: `1px solid ${color}30`,
            }}
          >
            {label}
          </span>
          {count > 1 && (
            <ChevronDown
              size={12}
              className={cn(
                "text-[var(--text-ghost)] transition-transform shrink-0",
                expanded && "rotate-180",
              )}
            />
          )}
        </div>
      </div>

      {expanded && (
        <div className="border-t border-[var(--border-default)] px-3 py-2 space-y-1">
          {events.map((ev, i) => (
            <div key={i} className="flex items-center justify-between text-xs text-[var(--text-muted)]">
              <span>
                {formatDate(ev.start_date)}
                {ev.end_date && ev.end_date !== ev.start_date ? ` \u2013 ${formatDate(ev.end_date)}` : ""}
              </span>
              {ev.value_numeric != null && (
                <span className="text-[var(--warning)]">
                  {String(ev.value_numeric)}{ev.unit ? ` ${ev.unit}` : ""}
                </span>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Individual event card
// ---------------------------------------------------------------------------

interface ClinicalEventCardProps {
  event: ClinicalEvent;
}

export function ClinicalEventCard({ event }: ClinicalEventCardProps) {
  const color = DOMAIN_COLORS[event.domain] ?? "#7A8298";
  const label = DOMAIN_LABELS[event.domain] ?? event.domain;

  const displayValue =
    event.value_numeric != null
      ? `${event.value_numeric}${event.unit ? ` ${event.unit}` : ""}`
      : event.value_as_string && event.value_as_string !== ""
        ? event.value_as_string
        : null;

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-3 hover:bg-[var(--surface-overlay)] transition-colors">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0 flex-1 space-y-1">
          <p className="text-sm font-medium text-[var(--text-primary)] truncate">
            {event.concept_name}
          </p>

          <p className="text-xs text-[var(--text-muted)]">
            {formatDate(event.start_date)}
            {event.end_date && event.end_date !== event.start_date
              ? ` \u2013 ${formatDate(event.end_date)}`
              : ""}
          </p>

          {displayValue && (
            <p className="text-xs font-semibold text-[var(--warning)]">{displayValue}</p>
          )}

          {event.value_numeric != null && (
            <RangeStatus
              value={event.value_numeric}
              rangeLow={event.reference_range_low}
              rangeHigh={event.reference_range_high}
            />
          )}

          {event.abnormal_flag && (
            <p className="text-[10px] text-[var(--critical)]">
              Flag: {event.abnormal_flag}
            </p>
          )}

          {event.domain === "medication" && (
            <div className="flex flex-wrap gap-x-3 gap-y-0.5">
              {event.route && (
                <p className="text-[10px] text-[var(--text-muted)]">Route: {event.route}</p>
              )}
              {event.dose_value != null && (
                <p className="text-[10px] text-[var(--text-muted)]">
                  Dose: {event.dose_value}{event.dose_unit ? ` ${event.dose_unit}` : ""}
                </p>
              )}
              {event.frequency && (
                <p className="text-[10px] text-[var(--text-muted)]">Freq: {event.frequency}</p>
              )}
            </div>
          )}

          {event.aurora_domain && (
            <span className="inline-flex items-center rounded-full bg-[var(--primary-bg)] px-2 py-0.5 text-[10px] font-medium text-[var(--primary-light)]">
              {event.aurora_domain}
            </span>
          )}

          {event.type_name && (
            <p className="text-[10px] text-[var(--text-ghost)]">{event.type_name}</p>
          )}
        </div>

        <span
          className="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
          style={{
            backgroundColor: `${color}15`,
            color,
            border: `1px solid ${color}30`,
          }}
        >
          {label}
        </span>
      </div>
    </div>
  );
}
