import { useState } from "react";
import { ChevronDown, ChevronRight, Pill } from "lucide-react";
import { cn } from "@/lib/utils";
import type { DrugExposure, VariantDrugCorrelation } from "../types";

interface TreatmentTimelineProps {
  drugExposures: DrugExposure[];
  correlations: VariantDrugCorrelation[];
}

function getRelationship(
  drug: DrugExposure,
  correlations: VariantDrugCorrelation[],
): "sensitive" | "resistant" | "neutral" {
  const match = correlations.find(
    (c) => c.drug_name.toLowerCase() === drug.drug_name.toLowerCase() && c.patient_exposed,
  );
  if (!match) return "neutral";
  return match.relationship === "resistant" ? "resistant" : "sensitive";
}

const REL_COLORS = {
  sensitive: { bar: "bg-[#2DD4BF]", text: "text-[#2DD4BF]" },
  resistant: { bar: "bg-[#F0607A]", text: "text-[#F0607A]" },
  neutral: { bar: "bg-[#4A5068]", text: "text-[#7A8298]" },
};

export function TreatmentTimeline({ drugExposures, correlations }: TreatmentTimelineProps) {
  const [expanded, setExpanded] = useState(false);

  if (drugExposures.length === 0) return null;

  const interactionCount = drugExposures.filter(
    (d) => getRelationship(d, correlations) !== "neutral",
  ).length;

  // Calculate timeline span for proportional bars
  const dates = drugExposures.flatMap((d) => {
    const starts = d.start_date ? [new Date(d.start_date).getTime()] : [];
    const ends = d.end_date ? [new Date(d.end_date).getTime()] : [Date.now()];
    return [...starts, ...ends];
  });
  const minDate = Math.min(...dates);
  const maxDate = Math.max(...dates);
  const span = maxDate - minDate || 1;

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
      <button
        type="button"
        onClick={() => setExpanded((p) => !p)}
        className="flex w-full items-center justify-between px-4 py-3 text-left"
      >
        <div className="flex items-center gap-2">
          <Pill size={14} className="text-[#7A8298]" />
          <span className="text-sm font-medium text-[#7A8298]">Treatment History</span>
          <span className="text-xs text-[#4A5068]">
            {drugExposures.length} drugs, {interactionCount} with genomic interactions
          </span>
        </div>
        {expanded ? (
          <ChevronDown size={14} className="text-[#4A5068]" />
        ) : (
          <ChevronRight size={14} className="text-[#4A5068]" />
        )}
      </button>

      {expanded && (
        <div className="border-t border-[#1C1C48] px-4 py-3 space-y-2">
          {drugExposures.map((drug, i) => {
            const rel = getRelationship(drug, correlations);
            const colors = REL_COLORS[rel];
            const startMs = drug.start_date ? new Date(drug.start_date).getTime() : minDate;
            const endMs = drug.end_date ? new Date(drug.end_date).getTime() : Date.now();
            const left = ((startMs - minDate) / span) * 100;
            const width = Math.max(((endMs - startMs) / span) * 100, 2);

            return (
              <div key={i} className="space-y-1">
                <div className="flex items-center justify-between text-xs">
                  <div className="flex items-center gap-2">
                    <span className={cn("font-medium", colors.text)}>{drug.drug_name}</span>
                    {drug.drug_class && (
                      <span className="text-[10px] text-[#4A5068]">({drug.drug_class})</span>
                    )}
                  </div>
                  <span className="text-[10px] text-[#4A5068] font-mono">
                    {drug.start_date ?? "?"} → {drug.end_date ?? "present"}
                    {drug.total_days != null && ` (${drug.total_days}d)`}
                  </span>
                </div>
                {/* Proportional bar */}
                <div className="relative h-2 rounded-full bg-[#16163A]">
                  <div
                    className={cn("absolute h-2 rounded-full opacity-80", colors.bar)}
                    style={{ left: `${left}%`, width: `${width}%` }}
                  />
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
