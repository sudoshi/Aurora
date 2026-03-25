import { useEffect } from "react";
import { Loader2 } from "lucide-react";
import { EvidenceBadge } from "./EvidenceBadge";
import { useVariantInterpretation } from "../hooks/useGenomics";
import type { GenomicVariant, GeneDrugInteraction } from "../types";
import { InlineActionMenu } from "@/features/patient-profile/components/InlineActionMenu";

interface VariantExpandedRowProps {
  variant: GenomicVariant;
  interactions: GeneDrugInteraction[];
  patientId: number;
}

// The API may return additional ClinVar fields not yet in the TS type
interface VariantExtended extends GenomicVariant {
  clinvar_disease?: string | null;
  clinvar_review_status?: string | null;
}

export function VariantExpandedRow({ variant, interactions, patientId }: VariantExpandedRowProps) {
  const interpretMutation = useVariantInterpretation();
  const gene = variant.gene_symbol ?? "Unknown";
  const v = variant as VariantExtended;

  // Auto-fetch AI interpretation on mount
  useEffect(() => {
    interpretMutation.mutate({
      gene,
      variant: variant.hgvs_p ?? variant.hgvs_c ?? variant.variant_type ?? "",
    });
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const geneInteractions = interactions.filter(
    (i) => i.gene.toUpperCase() === gene.toUpperCase(),
  );

  return (
    <div className="bg-[#10102A] border-t border-b border-[#1C1C48] px-6 py-4 space-y-4">
      {/* Variant details */}
      <div className="grid grid-cols-2 gap-4 text-xs">
        <div>
          <span className="text-[#4A5068]">Gene:</span>{" "}
          <span className="text-[#E8ECF4] font-semibold">{gene}</span>
        </div>
        <div>
          <span className="text-[#4A5068]">Alteration:</span>{" "}
          <span className="text-[#B4BAC8] font-mono">{variant.hgvs_p ?? variant.hgvs_c ?? "—"}</span>
        </div>
        <div>
          <span className="text-[#4A5068]">Coordinates:</span>{" "}
          <span className="text-[#7A8298] font-mono">
            {variant.chromosome ? `Chr${variant.chromosome}:${variant.position}` : "—"}
          </span>
        </div>
        <div>
          <span className="text-[#4A5068]">Alleles:</span>{" "}
          <span className="text-[#7A8298] font-mono">
            {variant.reference_allele ?? "?"} → {variant.alternate_allele ?? "?"}
          </span>
        </div>
        <div>
          <span className="text-[#4A5068]">AF:</span>{" "}
          <span className="text-[#7A8298]">
            {variant.allele_frequency != null ? `${(Number(variant.allele_frequency) * 100).toFixed(1)}%` : "—"}
          </span>
        </div>
        <div>
          <span className="text-[#4A5068]">Quality:</span>{" "}
          <span className="text-[#7A8298]">
            {variant.quality ?? "—"} {variant.filter_status ? `(${variant.filter_status})` : ""}
          </span>
        </div>
        {v.clinvar_disease && (
          <div className="col-span-2">
            <span className="text-[#4A5068]">ClinVar Disease:</span>{" "}
            <span className="text-[#B4BAC8]">{v.clinvar_disease}</span>
            {v.clinvar_review_status && (
              <span className="ml-2 text-[10px] text-[#4A5068]">({v.clinvar_review_status})</span>
            )}
          </div>
        )}
      </div>

      {/* Therapy matches */}
      {geneInteractions.length > 0 && (
        <div>
          <h4 className="text-[10px] font-semibold uppercase tracking-wider text-[#4A5068] mb-2">
            Matched Therapies
          </h4>
          <div className="space-y-1">
            {geneInteractions.map((inter) => (
              <div key={inter.id} className="flex items-center justify-between rounded-md bg-[#16163A] px-3 py-1.5">
                <span className="text-xs text-[#E8ECF4]">{inter.drug}</span>
                <EvidenceBadge evidenceLevel={inter.evidence_level} source={inter.source} lastVerifiedAt={inter.last_verified_at} />
              </div>
            ))}
          </div>
        </div>
      )}

      {/* AI Interpretation */}
      <div>
        <h4 className="text-[10px] font-semibold uppercase tracking-wider text-[#A78BFA] mb-2">
          AI Interpretation
        </h4>
        {interpretMutation.isPending && (
          <div className="flex items-center gap-2">
            <Loader2 size={12} className="animate-spin text-[#A78BFA]" />
            <span className="text-xs text-[#7A8298]">Interpreting...</span>
          </div>
        )}
        {interpretMutation.data?.interpretation && (
          <div className="text-xs text-[#B4BAC8] space-y-1">
            <p>{interpretMutation.data.interpretation.clinical_significance}</p>
            {interpretMutation.data.interpretation.targeted_therapies.length > 0 && (
              <p className="text-[#2DD4BF]">
                Therapies: {interpretMutation.data.interpretation.targeted_therapies.join(", ")}
              </p>
            )}
          </div>
        )}
        {interpretMutation.data?.error && (
          <p className="text-xs text-[#F0607A]">{interpretMutation.data.error}</p>
        )}
      </div>

      {/* Actions */}
      <div className="flex items-center gap-2 pt-1">
        <InlineActionMenu
          recordRef={`genomic:${variant.id}`}
          domain="genomic"
          patientId={patientId}
          onDiscuss={() => {}}
        />
      </div>
    </div>
  );
}
