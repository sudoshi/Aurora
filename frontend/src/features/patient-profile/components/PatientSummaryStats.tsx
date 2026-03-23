import { useMemo } from "react";
import { cn } from "@/lib/utils";
import {
  Activity,
  Pill,
  Stethoscope,
  FlaskConical,
  Eye,
  TrendingUp,
  Clock,
} from "lucide-react";
import type { PatientProfile, PatientStats } from "../types/profile";

interface PatientSummaryStatsProps {
  profile: PatientProfile;
  stats?: PatientStats;
  onDrillDown?: (view: string, domain?: string) => void;
}

interface StatPillProps {
  icon: React.ReactNode;
  label: string;
  value: string | number;
  sub?: string;
  color: string;
  onClick?: () => void;
}

function StatPill({ icon, label, value, sub, color, onClick }: StatPillProps) {
  return (
    <div
      className={cn(
        "flex items-center gap-3 rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] px-4 py-3 shrink-0 transition-colors",
        onClick && "cursor-pointer hover:border-[var(--surface-highlight)] hover:bg-[var(--surface-overlay)]",
      )}
      onClick={onClick}
    >
      <div
        className="flex items-center justify-center w-8 h-8 rounded-md"
        style={{ backgroundColor: `${color}18` }}
      >
        <span style={{ color }}>{icon}</span>
      </div>
      <div>
        <p className="text-[10px] font-semibold uppercase tracking-wider text-[var(--text-ghost)]">
          {label}
        </p>
        <p className="text-lg font-bold leading-tight" style={{ color }}>
          {value}
        </p>
        {sub && <p className="text-[10px] text-[var(--text-ghost)] leading-tight">{sub}</p>}
      </div>
    </div>
  );
}

export function PatientSummaryStats({ profile, stats: domainStats, onDrillDown }: PatientSummaryStatsProps) {
  const stats = useMemo(() => {
    const { conditions, medications, procedures, measurements, observations, visits } = profile;

    const conditionTotal = domainStats?.conditions ?? conditions.length;
    const medicationTotal = domainStats?.medications ?? medications.length;
    const measurementTotal = domainStats?.measurements ?? measurements.length;
    const visitTotal = domainStats?.visits ?? visits.length;
    const observationTotal = domainStats?.observations ?? observations.length;

    const totalEvents =
      conditionTotal +
      medicationTotal +
      (domainStats?.procedures ?? procedures.length) +
      measurementTotal +
      observationTotal +
      visitTotal;

    const uniqueConditions = new Set(conditions.map((e) => e.concept_code ?? e.concept_name)).size;
    const uniqueMedications = new Set(medications.map((e) => e.concept_code ?? e.concept_name)).size;

    // Last event date
    const allDates = [
      ...conditions,
      ...medications,
      ...procedures,
      ...measurements,
      ...observations,
      ...visits,
    ]
      .map((e) => e.start_date)
      .filter(Boolean)
      .sort();
    const lastEventDate = allDates.length > 0 ? allDates[allDates.length - 1] : null;

    return {
      totalEvents,
      uniqueConditions,
      uniqueMedications,
      visitCount: visitTotal,
      measurementCount: measurementTotal,
      observationCount: observationTotal,
      lastEventDate,
    };
  }, [profile, domainStats]);

  return (
    <div className="flex gap-3 overflow-x-auto pb-1">
      <StatPill
        icon={<Activity size={16} />}
        label="Total Events"
        value={(stats.totalEvents ?? 0).toLocaleString()}
        color="var(--accent)"
        onClick={onDrillDown ? () => onDrillDown("list", "all") : undefined}
      />
      <StatPill
        icon={<Stethoscope size={16} />}
        label="Conditions"
        value={stats.uniqueConditions}
        sub={`${profile.conditions.length} occurrences`}
        color="var(--domain-condition)"
        onClick={onDrillDown ? () => onDrillDown("list", "condition") : undefined}
      />
      <StatPill
        icon={<Pill size={16} />}
        label="Medications"
        value={stats.uniqueMedications}
        sub={`${profile.medications.length} exposures`}
        color="var(--domain-drug)"
        onClick={onDrillDown ? () => onDrillDown("list", "medication") : undefined}
      />
      <StatPill
        icon={<TrendingUp size={16} />}
        label="Visits"
        value={stats.visitCount}
        color="var(--domain-visit)"
        onClick={onDrillDown ? () => onDrillDown("visits") : undefined}
      />
      <StatPill
        icon={<FlaskConical size={16} />}
        label="Labs"
        value={(stats.measurementCount ?? 0).toLocaleString()}
        color="var(--domain-measurement)"
        onClick={onDrillDown ? () => onDrillDown("labs") : undefined}
      />
      <StatPill
        icon={<Eye size={16} />}
        label="Observations"
        value={(stats.observationCount ?? 0).toLocaleString()}
        color="var(--domain-observation)"
        onClick={onDrillDown ? () => onDrillDown("list", "observation") : undefined}
      />
      {stats.lastEventDate && (
        <StatPill
          icon={<Clock size={16} />}
          label="Last Activity"
          value={new Date(stats.lastEventDate).toLocaleDateString("en-US", {
            month: "short",
            year: "numeric",
          })}
          color="var(--warning)"
        />
      )}
    </div>
  );
}
