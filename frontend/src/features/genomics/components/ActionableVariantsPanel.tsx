import { useState } from "react";
import { ChevronDown, ChevronRight, ShieldQuestion } from "lucide-react";
import { ActionableVariantCard } from "./ActionableVariantCard";
import type { GenomicVariant, GeneDrugInteraction, VariantDrugCorrelation, DrugExposure } from "../types";

interface ActionableVariantsPanelProps {
  variants: GenomicVariant[];
  interactions: GeneDrugInteraction[];
  correlations: VariantDrugCorrelation[];
  drugExposures: DrugExposure[];
  patientId: number;
}

function isActionable(v: GenomicVariant): boolean {
  const sig = (v.clinvar_significance ?? "").toLowerCase();
  return sig.includes("pathogenic") && !sig.includes("benign");
}

export function ActionableVariantsPanel({
  variants,
  interactions,
  correlations,
  drugExposures: _drugExposures,
  patientId,
}: ActionableVariantsPanelProps) {
  const [vusExpanded, setVusExpanded] = useState(false);

  const actionable = variants.filter(isActionable);
  const vus = variants.filter((v) => {
    const sig = (v.clinvar_significance ?? "").toLowerCase();
    return sig.includes("uncertain") || sig === "vus" || sig === "";
  });

  if (actionable.length === 0 && vus.length === 0) return null;

  return (
    <div className="space-y-4">
      {/* Actionable variants */}
      {actionable.length > 0 && (
        <div className="space-y-3">
          <h3 className="text-sm font-semibold text-[#E8ECF4]">
            Actionable Variants
            <span className="ml-2 text-xs text-[#F0607A] font-normal">
              ({actionable.length})
            </span>
          </h3>
          {actionable.map((v) => (
            <ActionableVariantCard
              key={v.id}
              variant={v}
              interactions={interactions}
              correlations={correlations}
              patientId={patientId}
            />
          ))}
        </div>
      )}

      {/* VUS accordion */}
      {vus.length > 0 && (
        <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
          <button
            type="button"
            onClick={() => setVusExpanded((p) => !p)}
            className="flex w-full items-center justify-between px-4 py-3 text-left"
          >
            <div className="flex items-center gap-2">
              <ShieldQuestion size={14} className="text-[#F59E0B]" />
              <span className="text-sm font-medium text-[#7A8298]">
                Variants of Uncertain Significance
              </span>
              <span className="text-xs text-[#4A5068]">({vus.length})</span>
            </div>
            {vusExpanded ? (
              <ChevronDown size={14} className="text-[#4A5068]" />
            ) : (
              <ChevronRight size={14} className="text-[#4A5068]" />
            )}
          </button>
          {vusExpanded && (
            <div className="border-t border-[#1C1C48] px-4 py-3 space-y-2">
              {vus.map((v) => (
                <div
                  key={v.id}
                  className="flex items-center justify-between rounded-md bg-[#16163A] px-3 py-2"
                >
                  <div className="flex items-center gap-2 text-xs">
                    <span className="font-semibold text-[#A78BFA]">
                      {v.gene_symbol ?? "—"}
                    </span>
                    <span className="font-mono text-[#B4BAC8]">
                      {v.hgvs_p ?? v.hgvs_c ?? `${v.chromosome}:${v.position}`}
                    </span>
                    <span className="text-[#4A5068]">{v.variant_type ?? "SNV"}</span>
                  </div>
                  <span className="inline-flex items-center gap-1 rounded-full bg-[#F59E0B]/10 border border-[#F59E0B]/20 px-2 py-0.5 text-[10px] font-medium text-[#F59E0B]">
                    VUS
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
