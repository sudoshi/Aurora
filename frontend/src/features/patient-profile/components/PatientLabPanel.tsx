import { useState, useMemo } from "react";
import { FlaskConical, TrendingUp, TrendingDown, Minus } from "lucide-react";
import { cn } from "@/lib/utils";
import type { ClinicalEvent } from "../types/profile";
import { InlineActionMenu } from "./InlineActionMenu";
import { SelectActToolbar } from "./SelectActToolbar";

interface PatientLabPanelProps {
  events: ClinicalEvent[];
  patientId: number;
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
  first_id: number;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

// Wide sparkline for the expanded card layout
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

  const w = 140;
  const h = 28;
  const padX = 3;
  const padY = 4;

  const allVals = [...values];
  if (rangeLow != null) allVals.push(rangeLow);
  if (rangeHigh != null) allVals.push(rangeHigh);
  const min = Math.min(...allVals);
  const max = Math.max(...allVals);
  const range = max - min || 1;

  const toY = (v: number) => padY + ((max - v) / range) * (h - padY * 2);
  const toX = (i: number) =>
    padX + (i / Math.max(values.length - 1, 1)) * (w - padX * 2);

  const points = values.map((v, i) => `${toX(i)},${toY(v)}`).join(" ");

  return (
    <svg width="100%" viewBox={`0 0 ${w} ${h}`} className="block">
      {/* Reference range band */}
      {rangeLow != null && rangeHigh != null && (
        <rect
          x={padX}
          y={toY(Math.min(rangeHigh, max))}
          width={w - padX * 2}
          height={Math.max(toY(Math.max(rangeLow, min)) - toY(Math.min(rangeHigh, max)), 1)}
          fill="#22C55E"
          opacity={0.1}
          rx={2}
        />
      )}
      {/* Line */}
      <polyline
        points={points}
        fill="none"
        stroke="#A78BFA"
        strokeWidth={1.5}
        strokeLinejoin="round"
        strokeLinecap="round"
      />
      {/* Latest dot */}
      {values.length > 0 && (
        <circle
          cx={toX(values.length - 1)}
          cy={toY(values[values.length - 1])}
          r={2}
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
    return <Minus size={10} className="text-[var(--text-ghost)]" />;
  }
  if (value < rangeLow) {
    return (
      <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-[var(--info)]">
        <TrendingDown size={10} /> Low
      </span>
    );
  }
  if (value > rangeHigh) {
    return (
      <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-[var(--critical)]">
        <TrendingUp size={10} /> High
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-0.5 text-[10px] font-semibold text-[var(--success)]">
      <Minus size={10} /> Normal
    </span>
  );
}

function LabCard({
  group,
  patientId,
  isSelected,
  onToggleSelect,
}: {
  group: LabGroup;
  patientId: number;
  isSelected: boolean;
  onToggleSelect: () => void;
}) {
  const [showHistory, setShowHistory] = useState(false);
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
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] overflow-hidden">
      {/* Header row */}
      <div className="flex items-center justify-between gap-2 px-3 py-2">
        <div className="flex items-center gap-2 min-w-0">
          <input
            type="checkbox"
            checked={isSelected}
            onChange={onToggleSelect}
            className="rounded border-gray-600 bg-transparent shrink-0"
          />
          <p className="text-xs font-semibold text-[var(--text-primary)] truncate">
            {group.concept_name}
          </p>
          <span className="text-[9px] text-[var(--text-ghost)] shrink-0">x{group.count}</span>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          <div className="text-right">
            <span className="text-sm font-bold text-[var(--text-primary)]">
              {group.latest.toLocaleString(undefined, { maximumFractionDigits: 3 })}
            </span>
            {group.unit && (
              <span className="text-[10px] text-[var(--text-muted)] ml-0.5">{group.unit}</span>
            )}
          </div>
          {trend === "up" ? (
            <TrendingUp size={12} className="text-[var(--critical)]" />
          ) : trend === "down" ? (
            <TrendingDown size={12} className="text-[var(--info)]" />
          ) : (
            <Minus size={12} className="text-[var(--text-ghost)]" />
          )}
          <RangeIndicator value={group.latest} rangeLow={group.range_low} rangeHigh={group.range_high} />
          <InlineActionMenu
            recordRef={`measurement:${group.first_id}`}
            domain="measurement"
            patientId={patientId}
            onDiscuss={() => {}}
          />
        </div>
      </div>

      {/* Sparkline */}
      <div className="px-3 pb-1">
        <Sparkline
          values={sparkValues}
          rangeLow={group.range_low}
          rangeHigh={group.range_high}
        />
        {group.range_low != null && group.range_high != null && (
          <p className="text-[8px] text-[var(--text-ghost)] text-right mt-0.5">
            ref: {group.range_low}&ndash;{group.range_high}
          </p>
        )}
      </div>

      {/* Expandable history */}
      {group.count > 1 && (
        <button
          type="button"
          onClick={() => setShowHistory((p) => !p)}
          className="w-full text-[10px] text-[var(--text-ghost)] hover:text-[var(--text-muted)] px-3 py-1.5 border-t border-[var(--border-default)] hover:bg-[var(--surface-overlay)] transition-colors text-center"
        >
          {showHistory ? "Hide history" : `Show ${group.count} values`}
        </button>
      )}

      {showHistory && (
        <div className="px-3 pb-2 bg-[var(--surface-base)]">
          <table className="w-full">
            <tbody>
              {[...group.values]
                .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
                .map((v, i) => (
                  <tr key={i} className="border-t border-[var(--surface-overlay)]">
                    <td className="py-1 text-[10px] text-[var(--text-muted)]">{formatDate(v.date)}</td>
                    <td className="py-1 text-right text-[10px] font-medium text-[var(--text-primary)]">
                      {v.value.toLocaleString(undefined, { maximumFractionDigits: 3 })}
                      {group.unit && <span className="text-[var(--text-ghost)] ml-0.5">{group.unit}</span>}
                    </td>
                    <td className="py-1 text-right w-14">
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

export function PatientLabPanel({ events, patientId }: PatientLabPanelProps) {
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState<Set<string>>(new Set());

  const toggleSelect = (key: string) => {
    setSelected(prev => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

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
          first_id: m.id,
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

  // Split into two columns, distributing evenly
  const leftCol: LabGroup[] = [];
  const rightCol: LabGroup[] = [];
  filtered.forEach((g, i) => {
    if (i % 2 === 0) leftCol.push(g);
    else rightCol.push(g);
  });

  return (
    <div className="space-y-3">
      {/* Header bar */}
      <div className="flex items-center justify-between gap-3">
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

      {filtered.length === 0 ? (
        <div className="flex items-center justify-center h-24 rounded-lg border border-dashed border-[var(--border-default)]">
          <p className="text-sm text-[var(--text-muted)]">No tests match &quot;{search}&quot;</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <div className="flex flex-col gap-3">
            {leftCol.map((g) => (
              <LabCard
                key={g.concept_key}
                group={g}
                patientId={patientId}
                isSelected={selected.has(g.concept_key)}
                onToggleSelect={() => toggleSelect(g.concept_key)}
              />
            ))}
          </div>
          <div className="flex flex-col gap-3">
            {rightCol.map((g) => (
              <LabCard
                key={g.concept_key}
                group={g}
                patientId={patientId}
                isSelected={selected.has(g.concept_key)}
                onToggleSelect={() => toggleSelect(g.concept_key)}
              />
            ))}
          </div>
        </div>
      )}

      <SelectActToolbar
        selectedCount={selected.size}
        selectedRefs={Array.from(selected).map(key => {
          const group = labGroups.find(g => g.concept_key === key);
          return `measurement:${group?.first_id ?? key}`;
        })}
        domain="measurement"
        patientId={patientId}
        onClear={() => setSelected(new Set())}
        onDiscuss={() => {}}
        onFlag={() => {}}
        onExport={() => {}}
      />
    </div>
  );
}
