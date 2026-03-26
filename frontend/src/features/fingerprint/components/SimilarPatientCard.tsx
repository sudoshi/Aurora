import { AlertTriangle } from "lucide-react";
import { cn } from "@/lib/utils";
import type { SimilarPatientResult } from "../types";
import { DimensionBar } from "./DimensionBar";
import { OutcomeBadge } from "./OutcomeBadge";

interface SimilarPatientCardProps {
  result: SimilarPatientResult;
  className?: string;
}

function formatAge(dob: string): string {
  const diff = Date.now() - new Date(dob).getTime();
  return `${Math.floor(diff / 31557600000)}y`;
}

export function SimilarPatientCard({ result, className }: SimilarPatientCardProps) {
  const { patient, outcome } = result;
  const compositePercent = Math.round(result.composite_score * 100);
  const isPoorOutcome =
    outcome?.clinician_rating === "poor" || outcome?.clinician_rating === "failure";

  return (
    <div
      className={cn(
        "rounded-xl border bg-[#10102A] p-4 transition-colors",
        isPoorOutcome
          ? "border-[#F97316]/30 hover:border-[#F97316]/50"
          : "border-[#1C1C48] hover:border-[#2A2A60]",
        className,
      )}
    >
      {/* Header: Patient info + score */}
      <div className="flex items-start justify-between gap-3 mb-3">
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            {patient ? (
              <span className="text-sm font-semibold text-[#E8ECF4] truncate">
                {patient.last_name}, {patient.first_name}
              </span>
            ) : (
              <span className="text-sm font-semibold text-[#E8ECF4]">
                Patient #{result.patient_id}
              </span>
            )}
            <OutcomeBadge rating={outcome?.clinician_rating ?? null} />
          </div>
          {patient && (
            <div className="flex items-center gap-2 mt-0.5 text-xs text-[#7A8298]">
              <span className="font-mono">{patient.mrn}</span>
              <span>{patient.sex}</span>
              <span>{formatAge(patient.date_of_birth)}</span>
            </div>
          )}
          {patient?.primary_conditions && patient.primary_conditions.length > 0 && (
            <p className="text-xs text-[#B4BAC8] mt-1 truncate">
              {patient.primary_conditions.join(", ")}
            </p>
          )}
        </div>

        {/* Composite score */}
        <div className="text-right shrink-0">
          <div
            className="text-2xl font-bold tabular-nums"
            style={{
              color:
                compositePercent >= 80
                  ? "#22C55E"
                  : compositePercent >= 60
                    ? "#84CC16"
                    : compositePercent >= 40
                      ? "#EAB308"
                      : "#F97316",
            }}
          >
            {compositePercent}%
          </div>
          <div className="text-[10px] text-[#7A8298] uppercase tracking-wider">
            Match
          </div>
        </div>
      </div>

      {/* Dimension bars */}
      <div className="space-y-1.5 mb-3">
        <DimensionBar
          label="Genomic"
          value={result.genomic_similarity}
          color="#A78BFA"
        />
        <DimensionBar
          label="Volumetric"
          value={result.volumetric_similarity}
          color="#60A5FA"
        />
        <DimensionBar
          label="Clinical"
          value={result.clinical_similarity}
          color="#34D399"
        />
      </div>

      {/* Explanation */}
      {result.explanation && (
        <p className="text-xs text-[#B4BAC8] leading-relaxed mb-2">
          {result.explanation}
        </p>
      )}

      {/* Cautionary flag */}
      {isPoorOutcome && outcome?.hindsight_note && (
        <div className="flex items-start gap-2 rounded-lg bg-[#F97316]/8 border border-[#F97316]/20 px-3 py-2 mt-2">
          <AlertTriangle size={14} className="text-[#F97316] shrink-0 mt-0.5" />
          <div>
            <span className="text-xs font-medium text-[#F97316]">Caution: </span>
            <span className="text-xs text-[#B4BAC8]">{outcome.hindsight_note}</span>
          </div>
        </div>
      )}
    </div>
  );
}
