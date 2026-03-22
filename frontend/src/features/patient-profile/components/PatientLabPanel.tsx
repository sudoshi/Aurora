import { useState, useMemo } from "react";
import { ChevronDown, ChevronRight, FlaskConical, TrendingUp, TrendingDown, Minus } from "lucide-react";
import { cn } from "@/lib/utils";
import type { ClinicalEvent } from "../types/profile";

interface PatientLabPanelProps {
  events: ClinicalEvent[];
}

interface LabGroup {
  concept_key: string;
  concept_name: string;
  unit: string;
  values: { date: string; value: number }[];
  range_low: number | null;
  range_high: number | null;
  latest: number;
  count: number;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function Sparkline({
  values,
  rangeLow,
  rangeHigh,
}: {
  values: number[];
  rangeLow: number | null;
  rangeHigh: number | null;
}) {
  if (values.length === 0) return null;

  const w = 100;
  const h = 28;
  const pad = 2;

  const min = Math.min(...values);
  const max = Math.max(...values);
  const range = max - min || 1;

  const toY = (v: number) => pad + ((max - v) / range) * (h - pad * 2);
  const toX = (i: number) =>
    pad + (i / Math.max(values.length - 1, 1)) * (w - pad * 2);

  const points = values.map((v, i) => `${toX(i)},${toY(v)}`).join(" ");

  return (
    <svg width={w} height={h} viewBox={`0 0 ${w} ${h}`} className="shrink-0">
      {rangeLow != null && rangeHigh != null && (
        <rect
          x={pad}
          y={toY(Math.min(rangeHigh, max))}
          width={w - pad * 2}
          height={Math.max(toY(Math.max(rangeLow, min)) - toY(Math.min(rangeHigh, max)), 0)}
          fill="#22C55E"
          opacity={0.12}
        />
      )}
      <polyline
        points={points}
        fill="none"
        stroke="#A78BFA"
        strokeWidth={1.5}
        strokeLinejoin="round"
        strokeLinecap="round"
      />
      {values.length > 0 && (
        <circle
          cx={toX(values.length - 1)}
          cy={toY(values[values.length - 1])}
          r={2.5}
          fill="#A78BFA"
        />
      )}
    </svg>
  );
}

function RangeIndicator({
  value,
  rangeLow,
  rangeHigh,
}: {
  value: number;
  rangeLow: number | null;
  rangeHigh: number | null;
}) {
  if (rangeLow == null || rangeHigh == null) {
    return <Minus size={12} className="text-[var(--text-ghost)]" />;
  }
  if (value < rangeLow) {
    return (
      <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-[var(--info)]">
        <TrendingDown size={11} />
        Low
      </span>
    );
  }
  if (value > rangeHigh) {
    return (
      <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-[var(--critical)]">
        <TrendingUp size={11} />
        High
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-[var(--success)]">
      <Minus size={11} />
      Normal
    </span>
  );
}

function LabRow({ group }: { group: LabGroup }) {
  const [expanded, setExpanded] = useState(false);
  const sparkValues = group.values.map((v) => v.value);

  const trend =
    group.values.length >= 2
      ? group.latest > group.values[group.values.length - 2].value
        ? "up"
        : group.latest < group.values[group.values.length - 2].value
          ? "down"
          : "flat"
      : "flat";

  return (
    <div className="border-b border-[var(--surface-overlay)] last:border-0">
      <button
        type="button"
        onClick={() => setExpanded((p) => !p)}
        className="w-full flex items-center gap-3 px-4 py-3 hover:bg-[var(--surface-overlay)] transition-colors text-left"
      >
        <span className="text-[var(--text-ghost)] shrink-0">
          {expanded ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
        </span>

        <div className="flex-1 min-w-0">
          <p className="text-xs font-medium text-[var(--text-primary)] truncate">
            {group.concept_name}
          </p>
        </div>

        <span className="text-[10px] text-[var(--text-ghost)] shrink-0 w-8 text-right">
          x{group.count}
        </span>

        <div className="shrink-0">
          <Sparkline values={sparkValues} rangeLow={group.range_low} rangeHigh={group.range_high} />
        </div>

        <div className="shrink-0 w-28 text-right">
          <p className="text-sm font-bold text-[var(--text-primary)]">
            {group.latest.toLocaleString(undefined, { maximumFractionDigits: 3 })}
            {group.unit ? (
              <span className="text-[10px] font-normal text-[var(--text-muted)] ml-1">{group.unit}</span>
            ) : null}
          </p>
          {group.range_low != null && group.range_high != null && (
            <p className="text-[9px] text-[var(--text-ghost)]">
              ref: {group.range_low}&ndash;{group.range_high}
            </p>
          )}
        </div>

        <div className="shrink-0 w-6 flex justify-center">
          {trend === "up" ? (
            <TrendingUp size={14} className="text-[var(--critical)]" />
          ) : trend === "down" ? (
            <TrendingDown size={14} className="text-[var(--info)]" />
          ) : (
            <Minus size={14} className="text-[var(--text-ghost)]" />
          )}
        </div>

        <div className="shrink-0 w-14">
          <RangeIndicator value={group.latest} rangeLow={group.range_low} rangeHigh={group.range_high} />
        </div>
      </button>

      {expanded && (
        <div className="px-4 pb-3 bg-[var(--surface-base)]">
          <table className="w-full">
            <thead>
              <tr>
                <th className="py-1.5 text-left text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Date</th>
                <th className="py-1.5 text-right text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Value</th>
                <th className="py-1.5 text-right text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Range</th>
                <th className="py-1.5 text-right text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Status</th>
              </tr>
            </thead>
            <tbody>
              {[...group.values]
                .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                .map((v, i) => (
                  <tr key={i} className="border-t border-[var(--surface-overlay)]">
                    <td className="py-1.5 text-[11px] text-[var(--text-muted)]">{formatDate(v.date)}</td>
                    <td className="py-1.5 text-right text-[11px] font-medium text-[var(--text-primary)]">
                      {v.value.toLocaleString(undefined, { maximumFractionDigits: 3 })}
                      {group.unit && <span className="text-[var(--text-ghost)] ml-1">{group.unit}</span>}
                    </td>
                    <td className="py-1.5 text-right text-[10px] text-[var(--text-ghost)]">
                      {group.range_low != null && group.range_high != null
                        ? `${group.range_low}\u2013${group.range_high}`
                        : "\u2014"}
                    </td>
                    <td className="py-1.5 text-right">
                      <RangeIndicator value={v.value} rangeLow={group.range_low} rangeHigh={group.range_high} />
                    </td>
                  </tr>
                ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

export function PatientLabPanel({ events }: PatientLabPanelProps) {
  const [search, setSearch] = useState("");

  const labGroups = useMemo<LabGroup[]>(() => {
    const measurements = events.filter((e) => e.domain === "measurement");
    const grouped = new Map<string, LabGroup>();

    for (const m of measurements) {
      const numVal = m.value_numeric;
      if (numVal == null || isNaN(numVal)) continue;

      const key = m.concept_code ?? m.concept_name;
      const existing = grouped.get(key);
      if (existing) {
        existing.values.push({ date: m.start_date, value: numVal });
        existing.count++;
        if (existing.range_low == null && m.reference_range_low != null) {
          existing.range_low = m.reference_range_low;
          existing.range_high = m.reference_range_high ?? null;
        }
        if (!existing.unit && m.unit) {
          existing.unit = m.unit;
        }
      } else {
        grouped.set(key, {
          concept_key: key,
          concept_name: m.concept_name,
          unit: m.unit ?? "",
          values: [{ date: m.start_date, value: numVal }],
          range_low: m.reference_range_low ?? null,
          range_high: m.reference_range_high ?? null,
          latest: numVal,
          count: 1,
        });
      }
    }

    const groups = Array.from(grouped.values()).map((g) => {
      const sorted = [...g.values].sort(
        (a, b) => new Date(a.date).getTime() - new Date(b.date).getTime(),
      );
      return { ...g, values: sorted, latest: sorted[sorted.length - 1].value };
    });

    return groups.sort((a, b) => a.concept_name.localeCompare(b.concept_name));
  }, [events]);

  const filtered = useMemo(() => {
    if (!search.trim()) return labGroups;
    const q = search.toLowerCase();
    return labGroups.filter((g) => g.concept_name.toLowerCase().includes(q));
  }, [labGroups, search]);

  if (labGroups.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[var(--border-default)] bg-[var(--surface-raised)] py-16">
        <FlaskConical size={24} className="text-[var(--text-ghost)] mb-3" />
        <p className="text-sm text-[var(--text-muted)]">No lab measurements available</p>
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] overflow-hidden">
      <div className="flex items-center justify-between gap-3 px-4 py-2.5 bg-[var(--surface-overlay)] border-b border-[var(--border-default)]">
        <div className="flex items-center gap-2">
          <FlaskConical size={14} className="text-[var(--domain-measurement)]" />
          <span className="text-xs font-semibold text-[var(--text-primary)]">Lab Panel</span>
          <span className="text-[10px] text-[var(--text-ghost)]">
            {labGroups.length} tests &middot; {labGroups.reduce((s, g) => s + g.count, 0)} values
          </span>
        </div>
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Filter tests..."
          className={cn(
            "w-48 rounded-md border border-[var(--border-default)] bg-[var(--surface-base)] px-3 py-1 text-xs",
            "text-[var(--text-primary)] placeholder:text-[var(--text-ghost)]",
            "focus:border-[var(--border-focus)] focus:outline-none",
          )}
        />
      </div>

      <div className="flex items-center gap-3 px-4 py-1.5 bg-[var(--surface-raised)] border-b border-[var(--border-default)]">
        <div className="w-5 shrink-0" />
        <div className="flex-1 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Test</div>
        <div className="w-8 text-right text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">N</div>
        <div className="w-[100px] text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Trend</div>
        <div className="w-28 text-right text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Latest</div>
        <div className="w-6" />
        <div className="w-14 text-right text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">Status</div>
      </div>

      {filtered.length === 0 ? (
        <div className="flex items-center justify-center h-24">
          <p className="text-sm text-[var(--text-muted)]">No tests match &quot;{search}&quot;</p>
        </div>
      ) : (
        filtered.map((group) => <LabRow key={group.concept_key} group={group} />)
      )}
    </div>
  );
}
