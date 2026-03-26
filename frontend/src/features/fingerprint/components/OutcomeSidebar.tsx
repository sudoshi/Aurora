import { cn } from "@/lib/utils";
import type { ClinicianRating, SimilarPatientResult } from "../types";

interface OutcomeSidebarProps {
  results: SimilarPatientResult[];
  className?: string;
}

const OUTCOME_TIERS: {
  rating: ClinicianRating;
  label: string;
  color: string;
}[] = [
  { rating: "excellent", label: "Excellent", color: "#22C55E" },
  { rating: "good", label: "Good", color: "#84CC16" },
  { rating: "mixed", label: "Mixed", color: "#EAB308" },
  { rating: "poor", label: "Poor", color: "#F97316" },
  { rating: "failure", label: "Failure", color: "#EF4444" },
];

interface TreatmentInsight {
  treatment: string;
  total: number;
  positive: number;
  rate: number;
}

function computeOutcomeDistribution(
  results: SimilarPatientResult[],
): Record<ClinicianRating, number> {
  const dist: Record<ClinicianRating, number> = {
    excellent: 0,
    good: 0,
    mixed: 0,
    poor: 0,
    failure: 0,
  };

  for (const r of results) {
    const rating = r.outcome?.clinician_rating;
    if (rating && rating in dist) {
      dist[rating]++;
    }
  }

  return dist;
}

function computeTreatmentInsights(results: SimilarPatientResult[]): TreatmentInsight[] {
  const treatments: Record<string, { total: number; positive: number }> = {};

  for (const r of results) {
    const tags = r.outcome?.decision_tags ?? [];
    const rating = r.outcome?.clinician_rating;
    const isPositive = rating === "excellent" || rating === "good";

    for (const tag of tags) {
      if (!treatments[tag]) {
        treatments[tag] = { total: 0, positive: 0 };
      }
      treatments[tag].total++;
      if (isPositive) {
        treatments[tag].positive++;
      }
    }
  }

  return Object.entries(treatments)
    .map(([treatment, stats]) => ({
      treatment,
      total: stats.total,
      positive: stats.positive,
      rate: stats.total > 0 ? stats.positive / stats.total : 0,
    }))
    .sort((a, b) => b.rate - a.rate)
    .slice(0, 5);
}

function generateAbbyInsight(
  results: SimilarPatientResult[],
  distribution: Record<ClinicianRating, number>,
): string {
  const total = results.length;
  if (total === 0) return "No similar patients found to analyze.";

  const positiveCount = distribution.excellent + distribution.good;
  const negativeCount = distribution.poor + distribution.failure;
  const positiveRate = Math.round((positiveCount / total) * 100);

  const parts: string[] = [];

  if (positiveRate >= 70) {
    parts.push(
      `${positiveRate}% of similar patients had good or excellent outcomes.`,
    );
  } else if (positiveRate <= 30) {
    parts.push(
      `Only ${positiveRate}% of similar patients had positive outcomes. Careful treatment selection is critical.`,
    );
  } else {
    parts.push(
      `Outcomes are mixed: ${positiveCount} positive, ${distribution.mixed} mixed, ${negativeCount} negative.`,
    );
  }

  // Find patterns in positive outcomes
  const positiveResults = results.filter(
    (r) =>
      r.outcome?.clinician_rating === "excellent" ||
      r.outcome?.clinician_rating === "good",
  );
  const positiveTags = positiveResults.flatMap((r) => r.outcome?.decision_tags ?? []);
  const tagCounts: Record<string, number> = {};
  for (const tag of positiveTags) {
    tagCounts[tag] = (tagCounts[tag] ?? 0) + 1;
  }
  const topTag = Object.entries(tagCounts).sort((a, b) => b[1] - a[1])[0];
  if (topTag && topTag[1] >= 2) {
    const formatted = topTag[0].replace(/-/g, " ");
    parts.push(
      `Among positive outcomes, "${formatted}" was the most common pattern (${topTag[1]} patients).`,
    );
  }

  return parts.join(" ");
}

function formatTagLabel(tag: string): string {
  return tag
    .split("-")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
}

export function OutcomeSidebar({ results, className }: OutcomeSidebarProps) {
  const distribution = computeOutcomeDistribution(results);
  const totalRated = Object.values(distribution).reduce((s, n) => s + n, 0);
  const treatmentInsights = computeTreatmentInsights(results);
  const abbyInsight = generateAbbyInsight(results, distribution);
  const hindsightNotes = results
    .filter((r) => r.outcome?.hindsight_note)
    .map((r) => ({
      patientId: r.patient_id,
      note: r.outcome!.hindsight_note!,
      rating: r.outcome!.clinician_rating,
    }));

  return (
    <div className={cn("space-y-4", className)}>
      {/* Outcome Distribution */}
      <div className="rounded-xl border border-[#1C1C48] bg-[#10102A] p-4">
        <h4 className="text-xs font-semibold text-[#E8ECF4] uppercase tracking-wider mb-3">
          Outcome Distribution
        </h4>

        {totalRated > 0 ? (
          <>
            {/* Stacked bar */}
            <div className="flex h-4 rounded-full overflow-hidden bg-[#1C1C48] mb-3">
              {OUTCOME_TIERS.map(({ rating, color }) => {
                const count = distribution[rating];
                if (count === 0) return null;
                const widthPct = (count / totalRated) * 100;
                return (
                  <div
                    key={rating}
                    className="h-full transition-all duration-300"
                    style={{ width: `${widthPct}%`, backgroundColor: color }}
                    title={`${rating}: ${count}`}
                  />
                );
              })}
            </div>

            {/* Legend */}
            <div className="flex flex-wrap gap-x-3 gap-y-1">
              {OUTCOME_TIERS.map(({ rating, label, color }) => {
                const count = distribution[rating];
                if (count === 0) return null;
                return (
                  <div key={rating} className="flex items-center gap-1.5">
                    <div
                      className="w-2 h-2 rounded-full"
                      style={{ backgroundColor: color }}
                    />
                    <span className="text-[10px] text-[#7A8298]">
                      {label} ({count})
                    </span>
                  </div>
                );
              })}
            </div>
          </>
        ) : (
          <p className="text-xs text-[#7A8298]">No outcomes assessed yet.</p>
        )}
      </div>

      {/* Abby's Insight */}
      <div className="rounded-xl border border-[#A78BFA]/20 bg-[#A78BFA]/5 p-4">
        <div className="flex items-center gap-2 mb-2">
          <span className="text-sm">Abby</span>
          <h4 className="text-xs font-semibold text-[#A78BFA] uppercase tracking-wider">
            Insight
          </h4>
        </div>
        <p className="text-xs text-[#B4BAC8] leading-relaxed">{abbyInsight}</p>
      </div>

      {/* Treatment Response Rates */}
      {treatmentInsights.length > 0 && (
        <div className="rounded-xl border border-[#1C1C48] bg-[#10102A] p-4">
          <h4 className="text-xs font-semibold text-[#E8ECF4] uppercase tracking-wider mb-3">
            What Worked
          </h4>
          <div className="space-y-2">
            {treatmentInsights.map((t) => (
              <div key={t.treatment} className="flex items-center justify-between">
                <span className="text-xs text-[#B4BAC8] truncate mr-2">
                  {formatTagLabel(t.treatment)}
                </span>
                <div className="flex items-center gap-2 shrink-0">
                  <div className="w-16 h-1.5 rounded-full bg-[#1C1C48] overflow-hidden">
                    <div
                      className="h-full rounded-full"
                      style={{
                        width: `${Math.round(t.rate * 100)}%`,
                        backgroundColor:
                          t.rate >= 0.7
                            ? "#22C55E"
                            : t.rate >= 0.4
                              ? "#EAB308"
                              : "#EF4444",
                      }}
                    />
                  </div>
                  <span className="text-[10px] font-mono text-[#7A8298] w-8 text-right">
                    {Math.round(t.rate * 100)}%
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Hindsight Notes */}
      {hindsightNotes.length > 0 && (
        <div className="rounded-xl border border-[#1C1C48] bg-[#10102A] p-4">
          <h4 className="text-xs font-semibold text-[#E8ECF4] uppercase tracking-wider mb-3">
            Clinician Hindsight
          </h4>
          <div className="space-y-3">
            {hindsightNotes.slice(0, 5).map((h) => (
              <div
                key={h.patientId}
                className="text-xs text-[#B4BAC8] leading-relaxed border-l-2 pl-3"
                style={{
                  borderColor:
                    h.rating === "excellent" || h.rating === "good"
                      ? "#22C55E"
                      : h.rating === "mixed"
                        ? "#EAB308"
                        : "#F97316",
                }}
              >
                <span className="text-[#7A8298]">Patient #{h.patientId}: </span>
                {h.note}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
