/**
 * PatientGenomicsTab -- simplified genomics view for the patient profile page.
 *
 * Shows:
 * 1. Patient's genomic variants in a table
 * 2. ClinVar significance with color-coded badges
 * 3. Actionable genes highlighted
 * 4. Link to full tumor board page
 */
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  Dna,
  Loader2,
  ExternalLink,
  ChevronLeft,
  ChevronRight,
  ShieldAlert,
  ShieldCheck,
  ShieldQuestion,
  AlertTriangle,
} from "lucide-react";
import { useGenomicVariants } from "@/features/genomics/hooks/useGenomics";
import type { GenomicVariant } from "@/features/genomics/types";
import { VariantCard } from "./VariantCard";
import { ActionableGenes } from "./ActionableGenes";
import { InlineActionMenu } from "./InlineActionMenu";
import { SelectActToolbar } from "./SelectActToolbar";

const CLINVAR_BADGE: Record<string, { cls: string; label: string; icon: typeof ShieldAlert }> = {
  pathogenic:             { cls: "bg-[#F0607A]/15 text-[#F0607A]", label: "Pathogenic", icon: ShieldAlert },
  "likely pathogenic":    { cls: "bg-orange-400/15 text-orange-400", label: "Likely Pathogenic", icon: ShieldAlert },
  "uncertain significance": { cls: "bg-amber-400/15 text-amber-400", label: "VUS", icon: ShieldQuestion },
  "likely benign":        { cls: "bg-blue-400/15 text-blue-400", label: "Likely Benign", icon: ShieldCheck },
  benign:                 { cls: "bg-[#2DD4BF]/15 text-[#2DD4BF]", label: "Benign", icon: ShieldCheck },
};

function getClinvarBadge(sig: string | null) {
  if (!sig) return null;
  const key = sig.toLowerCase();
  for (const [k, v] of Object.entries(CLINVAR_BADGE)) {
    if (key.includes(k)) return v;
  }
  return null;
}

interface PatientGenomicsTabProps {
  patientId: number;
}

export default function PatientGenomicsTab({ patientId }: PatientGenomicsTabProps) {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [selectedVariant, setSelectedVariant] = useState<GenomicVariant | null>(null);
  const [selected, setSelected] = useState<Set<number>>(new Set());

  const toggleSelect = (id: number) => {
    setSelected(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const { data: variantsPage, isLoading } = useGenomicVariants({
    person_id: patientId,
    per_page: 25,
    page,
  });

  const variants = variantsPage?.data ?? [];
  const totalPages = variantsPage?.last_page ?? 1;
  const totalVariants = variantsPage?.total ?? 0;

  // Identify actionable genes (pathogenic / likely pathogenic variants)
  const actionableGenes = [
    ...new Set(
      variants
        .filter((v) => {
          const sig = v.clinvar_significance?.toLowerCase() ?? "";
          return sig.includes("pathogenic") && !sig.includes("benign");
        })
        .map((v) => v.gene_symbol)
        .filter((g): g is string => g !== null)
    ),
  ];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16">
        <Loader2 size={24} className="animate-spin text-[#2DD4BF]" />
      </div>
    );
  }

  if (variants.length === 0 && page === 1) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-[#4A5068]">
        <Dna size={36} className="mb-3 opacity-40" />
        <p className="text-sm font-medium text-[#7A8298]">No genomic data available</p>
        <p className="text-xs mt-1">Upload variant files to see genomic data for this patient</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Header with link to tumor board */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Dna size={16} className="text-[#A78BFA]" />
          <h3 className="text-sm font-semibold text-[#E8ECF4]">
            Genomic Variants
            <span className="ml-2 text-xs text-[#4A5068] font-normal">
              ({totalVariants} total)
            </span>
          </h3>
        </div>
        <button
          type="button"
          onClick={() => navigate(`/genomics/tumor-board`)}
          className="inline-flex items-center gap-1.5 text-xs text-[#A78BFA] hover:text-[#C4B5FD] transition-colors"
        >
          <ExternalLink size={12} />
          Full Tumor Board
        </button>
      </div>

      {/* Actionable genes summary */}
      {actionableGenes.length > 0 && (
        <ActionableGenes genes={actionableGenes} />
      )}

      {/* Selected variant detail card */}
      {selectedVariant && (
        <VariantCard
          variant={selectedVariant}
          onClose={() => setSelectedVariant(null)}
        />
      )}

      {/* Variants table */}
      <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-[#1C1C48]">
                <th className="px-2 py-2.5 w-8" />
                {["Gene", "Alteration", "Type", "AF", "ClinVar", ""].map((h) => (
                  <th
                    key={h}
                    className="px-4 py-2.5 text-left text-[10px] font-medium text-[#4A5068] uppercase tracking-wider"
                  >
                    {h}
                  </th>
                ))}
                <th className="px-2 py-2.5 w-8" />
              </tr>
            </thead>
            <tbody className="divide-y divide-[#16163A]">
              {variants.map((v) => {
                const badge = getClinvarBadge(v.clinvar_significance);
                const BadgeIcon = badge?.icon ?? ShieldQuestion;
                const isActionable = actionableGenes.includes(v.gene_symbol ?? "");

                return (
                  <tr
                    key={v.id}
                    className="hover:bg-[#16163A] cursor-pointer transition-colors"
                    onClick={() => setSelectedVariant(v)}
                  >
                    <td className="px-2 py-2 w-8" onClick={(e) => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={selected.has(v.id)}
                        onChange={() => toggleSelect(v.id)}
                        className="rounded border-gray-600 bg-transparent"
                      />
                    </td>
                    <td className="px-4 py-2.5">
                      <span className={`font-semibold ${isActionable ? "text-[#F0607A]" : "text-[#A78BFA]"}`}>
                        {v.gene_symbol ?? "\u2014"}
                      </span>
                      {isActionable && (
                        <AlertTriangle size={10} className="inline ml-1 text-[#F0607A]" />
                      )}
                    </td>
                    <td className="px-4 py-2.5 font-mono text-[#B4BAC8]">
                      {v.hgvs_p ?? v.hgvs_c ?? `${v.chromosome}:${v.position}`}
                    </td>
                    <td className="px-4 py-2.5 text-[#7A8298]">
                      {v.variant_type ?? "\u2014"}
                    </td>
                    <td className="px-4 py-2.5 text-[#7A8298]">
                      {v.allele_frequency != null
                        ? (v.allele_frequency * 100).toFixed(1) + "%"
                        : "\u2014"}
                    </td>
                    <td className="px-4 py-2.5">
                      {badge ? (
                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium ${badge.cls}`}>
                          <BadgeIcon size={10} />
                          {badge.label}
                        </span>
                      ) : (
                        <span className="text-[#2A2A60] text-[10px]">{"\u2014"}</span>
                      )}
                    </td>
                    <td className="px-4 py-2.5 text-[#4A5068]">
                      <ChevronRight size={12} />
                    </td>
                    <td className="px-2 py-2 w-8" onClick={(e) => e.stopPropagation()}>
                      <InlineActionMenu
                        recordRef={`genomic:${v.id}`}
                        domain="genomic"
                        patientId={patientId}
                        onDiscuss={() => {/* will be wired in Task 18 */}}
                      />
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-[#1C1C48]">
            <p className="text-xs text-[#4A5068]">
              Page {page} of {totalPages}
            </p>
            <div className="flex items-center gap-1">
              <button
                type="button"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="p-1.5 rounded text-[#4A5068] hover:text-[#B4BAC8] hover:bg-[#1C1C48] disabled:opacity-30 transition-colors"
              >
                <ChevronLeft size={14} />
              </button>
              <button
                type="button"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={page === totalPages}
                className="p-1.5 rounded text-[#4A5068] hover:text-[#B4BAC8] hover:bg-[#1C1C48] disabled:opacity-30 transition-colors"
              >
                <ChevronRight size={14} />
              </button>
            </div>
          </div>
        )}
      </div>

      <SelectActToolbar
        selectedCount={selected.size}
        selectedRefs={Array.from(selected).map(id => `genomic:${id}`)}
        domain="genomic"
        patientId={patientId}
        onClear={() => setSelected(new Set())}
        onDiscuss={() => {}}
        onFlag={() => {}}
        onExport={() => {}}
      />
    </div>
  );
}
