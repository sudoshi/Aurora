import { useState } from "react";
import {
  ShieldAlert,
  Pill,
  AlertTriangle,
  Loader2,
  ChevronUp,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { EvidenceBadge } from "./EvidenceBadge";
import { useVariantInterpretation } from "../hooks/useGenomics";
import type { GenomicVariant, GeneDrugInteraction, VariantDrugCorrelation } from "../types";
import { InlineActionMenu } from "@/features/patient-profile/components/InlineActionMenu";

interface ActionableVariantCardProps {
  variant: GenomicVariant;
  interactions: GeneDrugInteraction[];
  correlations: VariantDrugCorrelation[];
  patientId: number;
}

const SIGNIFICANCE_COLORS: Record<string, string> = {
  pathogenic: "bg-[#F0607A]/15 text-[#F0607A] border-[#F0607A]/25",
  likely_pathogenic: "bg-orange-400/15 text-orange-400 border-orange-400/25",
};

export function ActionableVariantCard({
  variant,
  interactions,
  correlations,
  patientId,
}: ActionableVariantCardProps) {
  const [showAiDetail, setShowAiDetail] = useState(false);
  const interpretMutation = useVariantInterpretation();

  const significance = variant.clinvar_significance?.toLowerCase() ?? "unknown";
  const sigClass = SIGNIFICANCE_COLORS[significance.includes("likely") ? "likely_pathogenic" : "pathogenic"]
    ?? "bg-[#7A8298]/15 text-[#7A8298] border-[#7A8298]/25";

  const gene = variant.gene_symbol ?? "Unknown";
  const alteration = variant.hgvs_p ?? variant.hgvs_c ?? `${variant.chromosome}:${variant.position}`;

  // Filter interactions and correlations for this variant's gene
  const geneInteractions = interactions.filter(
    (i) => i.gene.toUpperCase() === gene.toUpperCase(),
  );
  const geneCorrelations = correlations.filter(
    (c) => c.gene_symbol?.toUpperCase() === gene.toUpperCase(),
  );

  const handleAiInterpret = () => {
    setShowAiDetail(true);
    interpretMutation.mutate({
      gene,
      variant: variant.hgvs_p ?? variant.hgvs_c ?? variant.variant_type ?? "",
    });
  };

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-4 space-y-3">
      {/* Header: gene + alteration + significance */}
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-center gap-2 flex-wrap">
          <ShieldAlert size={14} className="text-[#F0607A] shrink-0" />
          <span className="text-sm font-bold text-[#E8ECF4]">{gene}</span>
          <span className="text-sm font-mono text-[#B4BAC8]">{alteration}</span>
          <span className={cn("inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium capitalize", sigClass)}>
            {significance.replace(/_/g, " ")}
          </span>
        </div>
        <InlineActionMenu
          recordRef={`genomic:${variant.id}`}
          domain="genomic"
          patientId={patientId}
          onDiscuss={() => {}}
        />
      </div>

      {/* Variant details */}
      <div className="flex flex-wrap gap-3 text-[10px] text-[#7A8298]">
        <span>{variant.variant_type ?? "SNV"}</span>
        {variant.chromosome && variant.position && (
          <span className="font-mono">Chr{variant.chromosome}:{variant.position}</span>
        )}
        {variant.allele_frequency != null && (
          <span>AF: {(Number(variant.allele_frequency) * 100).toFixed(1)}%</span>
        )}
        {variant.clinvar_significance && (
          <span className="text-[#B4BAC8]">{variant.clinvar_significance}</span>
        )}
      </div>

      {/* Matched therapies */}
      {geneInteractions.length > 0 && (
        <div className="space-y-1.5">
          <h4 className="text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
            Targeted Therapies
          </h4>
          <div className="space-y-1">
            {geneInteractions.map((inter) => (
              <div
                key={inter.id}
                className="flex items-center justify-between gap-2 rounded-md bg-[#10102A] px-3 py-1.5"
              >
                <div className="flex items-center gap-2">
                  <Pill size={12} className={inter.relationship === "resistant" ? "text-[#F0607A]" : "text-[#2DD4BF]"} />
                  <span className="text-xs text-[#E8ECF4]">{inter.drug}</span>
                  {inter.drug_class && (
                    <span className="text-[10px] text-[#4A5068]">({inter.drug_class})</span>
                  )}
                </div>
                <EvidenceBadge
                  evidenceLevel={inter.evidence_level}
                  source={inter.source}
                  lastVerifiedAt={inter.last_verified_at}
                />
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Current drug interactions */}
      {geneCorrelations.filter((c) => c.patient_exposed).length > 0 && (
        <div className="space-y-1.5">
          <h4 className="text-[10px] font-semibold uppercase tracking-wider text-[#F59E0B]">
            <AlertTriangle size={10} className="inline mr-1" />
            Current Drug Interactions
          </h4>
          {geneCorrelations.filter((c) => c.patient_exposed).map((corr, i) => (
            <div key={i} className="flex items-center gap-2 text-xs text-[#B4BAC8] rounded-md bg-[#F59E0B]/5 px-3 py-1.5">
              <span className="font-medium">{corr.drug_name}</span>
              <span className="text-[10px] text-[#7A8298]">
                {corr.exposure_start ? `${corr.exposure_start} → ${corr.exposure_end ?? "present"}` : ""}
              </span>
              <span className={cn(
                "text-[10px] font-medium",
                corr.relationship === "resistant" ? "text-[#F0607A]" : "text-[#2DD4BF]",
              )}>
                {corr.relationship}
              </span>
            </div>
          ))}
        </div>
      )}

      {/* AI Interpret button + detail */}
      <div>
        {!showAiDetail ? (
          <button
            type="button"
            onClick={handleAiInterpret}
            className="text-xs text-[#A78BFA] hover:text-[#C4B5FD] transition-colors"
          >
            AI Interpret →
          </button>
        ) : (
          <div className="rounded-md bg-[#A78BFA]/5 border border-[#A78BFA]/20 p-3 space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-semibold text-[#A78BFA] uppercase tracking-wider">AI Interpretation</span>
              <button type="button" onClick={() => setShowAiDetail(false)} className="text-[#4A5068] hover:text-[#7A8298]">
                <ChevronUp size={12} />
              </button>
            </div>
            {interpretMutation.isPending && (
              <div className="flex items-center gap-2">
                <Loader2 size={12} className="animate-spin text-[#A78BFA]" />
                <span className="text-xs text-[#7A8298]">Interpreting variant...</span>
              </div>
            )}
            {interpretMutation.data?.interpretation && (
              <div className="space-y-1 text-xs text-[#B4BAC8]">
                <p>{interpretMutation.data.interpretation.clinical_significance}</p>
                {interpretMutation.data.interpretation.targeted_therapies.length > 0 && (
                  <p className="text-[#2DD4BF]">
                    Therapies: {interpretMutation.data.interpretation.targeted_therapies.join(", ")}
                  </p>
                )}
                {interpretMutation.data.interpretation.references.length > 0 && (
                  <p className="text-[10px] text-[#4A5068]">
                    Sources: {interpretMutation.data.interpretation.references.join("; ")}
                  </p>
                )}
              </div>
            )}
            {interpretMutation.data?.error && (
              <p className="text-xs text-[#F0607A]">{interpretMutation.data.error}</p>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
