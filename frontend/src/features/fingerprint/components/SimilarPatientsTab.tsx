import { useState, useCallback } from "react";
import { Loader2, Search } from "lucide-react";
import { cn } from "@/lib/utils";
import { useSimilarPatients } from "../hooks/useFingerprint";
import type { DimensionWeights } from "../types";
import { FingerprintBanner } from "./FingerprintBanner";
import { WeightControls } from "./WeightControls";
import { SimilarPatientCard } from "./SimilarPatientCard";
import { OutcomeSidebar } from "./OutcomeSidebar";

interface SimilarPatientsTabProps {
  patientId: number;
  className?: string;
}

const DEFAULT_WEIGHTS: DimensionWeights = {
  genomic: 0.34,
  volumetric: 0.33,
  clinical: 0.33,
};

export function SimilarPatientsTab({ patientId, className }: SimilarPatientsTabProps) {
  const [weights, setWeights] = useState<DimensionWeights>(DEFAULT_WEIGHTS);

  const { data, isLoading, isError } = useSimilarPatients({
    patient_id: patientId,
    weights,
    limit: 10,
    context: "point_of_care",
  });

  const handleWeightsChange = useCallback((w: DimensionWeights) => {
    setWeights(w);
  }, []);

  const results = data?.results ?? [];

  return (
    <div className={cn("space-y-4", className)}>
      {/* Fingerprint status banner */}
      <FingerprintBanner patientId={patientId} />

      {/* Weight controls */}
      <WeightControls weights={weights} onChange={handleWeightsChange} />

      {/* Results area */}
      {isLoading && (
        <div className="flex items-center justify-center h-48">
          <div className="flex items-center gap-2 text-[#7A8298]">
            <Loader2 size={16} className="animate-spin" />
            <span className="text-sm">Finding similar patients...</span>
          </div>
        </div>
      )}

      {isError && (
        <div className="flex items-center justify-center h-48 rounded-xl border border-[#F0607A]/20 bg-[#F0607A]/5">
          <div className="text-center">
            <p className="text-sm text-[#F0607A]">Failed to load similar patients</p>
            <p className="text-xs text-[#7A8298] mt-1">
              The patient may not have an encoded fingerprint yet. Try encoding first.
            </p>
          </div>
        </div>
      )}

      {!isLoading && !isError && results.length === 0 && (
        <div className="flex flex-col items-center justify-center h-48 rounded-xl border border-dashed border-[#1C1C48] bg-[#10102A]">
          <Search size={24} className="text-[#7A8298] mb-2" />
          <p className="text-sm text-[#7A8298]">No similar patients found</p>
          <p className="text-xs text-[#7A8298] mt-1">
            This may improve as more patients are fingerprinted.
          </p>
        </div>
      )}

      {!isLoading && !isError && results.length > 0 && (
        <div className="flex gap-4">
          {/* Left column: result cards (70%) */}
          <div className="flex-[7] min-w-0 space-y-3">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-semibold text-[#E8ECF4]">
                Similar Patients ({results.length})
              </h3>
              {data?.meta && (
                <span className="text-[10px] text-[#7A8298]">
                  Matched on {data.meta.dimensions_available.filter(Boolean).length}/3 dimensions
                </span>
              )}
            </div>
            {results.map((result) => (
              <SimilarPatientCard key={result.patient_id} result={result} />
            ))}
          </div>

          {/* Right column: outcome sidebar (30%) */}
          <div className="flex-[3] min-w-0">
            <OutcomeSidebar results={results} />
          </div>
        </div>
      )}
    </div>
  );
}
