/**
 * PatientGenomicsTab — Unified clinical decision support genomics view.
 *
 * Composes 4 sections:
 * 1. GenomicBriefing — Abby AI narrative summary
 * 2. ActionableVariantsPanel — Pathogenic variants with therapy matching
 * 3. TreatmentTimeline — Drug exposure timeline with genomic interactions
 * 4. GenomicVariantTable — Full searchable/filterable variant table
 */
import { useMemo } from "react";
import { Dna, Loader2 } from "lucide-react";
import { useGenomicVariants, useRadiogenomicsPanel, useGeneDrugInteractions } from "@/features/genomics/hooks/useGenomics";
import { GenomicBriefing } from "@/features/genomics/components/GenomicBriefing";
import { ActionableVariantsPanel } from "@/features/genomics/components/ActionableVariantsPanel";
import { TreatmentTimeline } from "@/features/genomics/components/TreatmentTimeline";
import { GenomicVariantTable } from "@/features/genomics/components/GenomicVariantTable";
import type { GenomicBriefingRequest, GenomicBriefingVariant, GenomicBriefingDrugExposure, GenomicBriefingInteraction } from "@/features/genomics/types";

interface PatientGenomicsTabProps {
  patientId: number;
}

export default function PatientGenomicsTab({ patientId }: PatientGenomicsTabProps) {
  // Fetch all data sources
  const { data: variantsPage, isLoading: loadingVariants } = useGenomicVariants({
    person_id: patientId,
    per_page: 100, // Get all for briefing assembly
  });
  const { data: panel, isLoading: loadingPanel } = useRadiogenomicsPanel(patientId);
  const { data: interactions, isLoading: loadingInteractions } = useGeneDrugInteractions();

  const variants = variantsPage?.data ?? [];
  const allInteractions = interactions ?? [];
  const correlations = panel?.correlations ?? [];
  const drugExposures = panel?.drug_exposures ?? [];

  // Assemble briefing request data
  const briefingData = useMemo<GenomicBriefingRequest>(() => {
    const actionableVariants: GenomicBriefingVariant[] = variants
      .filter((v) => {
        const sig = (v.clinvar_significance ?? "").toLowerCase();
        return sig.includes("pathogenic") && !sig.includes("benign");
      })
      .map((v) => {
        const gene = v.gene_symbol ?? "Unknown";
        const matchedDrugs = allInteractions
          .filter((i) => i.gene.toUpperCase() === gene.toUpperCase())
          .map((i) => `${i.drug} (${i.evidence_level})`);
        return {
          gene,
          variant: v.hgvs_p ?? v.hgvs_c ?? v.variant_type ?? "",
          classification: v.clinvar_significance?.toLowerCase().includes("likely")
            ? "likely_pathogenic"
            : "pathogenic",
          evidence_level: allInteractions.find(
            (i) => i.gene.toUpperCase() === gene.toUpperCase(),
          )?.evidence_level ?? null,
          therapies: matchedDrugs,
        };
      });

    const briefingDrugExposures: GenomicBriefingDrugExposure[] = drugExposures.map((d) => ({
      drug_name: d.drug_name,
      start_date: d.start_date,
      end_date: d.end_date,
    }));

    const briefingInteractions: GenomicBriefingInteraction[] = correlations
      .filter((c) => c.patient_exposed)
      .map((c) => ({
        gene: c.gene_symbol,
        drug: c.drug_name,
        relationship: c.relationship,
        evidence_level: c.evidence_level,
        mechanism: c.mechanism,
      }));

    return {
      patient_id: patientId,
      variants: actionableVariants,
      drug_exposures: briefingDrugExposures,
      interactions: briefingInteractions,
      total_variant_count: variants.length,
    };
  }, [variants, allInteractions, drugExposures, correlations, patientId]);

  // Loading state
  const isLoading = loadingVariants || loadingPanel || loadingInteractions;
  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16">
        <Loader2 size={24} className="animate-spin text-[#A78BFA]" />
      </div>
    );
  }

  // Empty state
  if (variants.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-[#4A5068]">
        <Dna size={36} className="mb-3 opacity-40" />
        <p className="text-sm font-medium text-[#7A8298]">No genomic data available</p>
        <p className="text-xs mt-1">Upload variant files to see genomic data for this patient</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Section 1: Abby Genomic Briefing */}
      <GenomicBriefing briefingData={briefingData} />

      {/* Section 2: Actionable Variants + Therapy Matching */}
      <ActionableVariantsPanel
        variants={variants}
        interactions={allInteractions}
        correlations={correlations}
        drugExposures={drugExposures}
        patientId={patientId}
      />

      {/* Section 3: Treatment Timeline */}
      <TreatmentTimeline
        drugExposures={drugExposures}
        correlations={correlations}
      />

      {/* Section 4: Full Variant Table */}
      <GenomicVariantTable
        patientId={patientId}
        interactions={allInteractions}
      />
    </div>
  );
}
