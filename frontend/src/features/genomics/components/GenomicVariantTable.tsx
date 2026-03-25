import { useState, Fragment } from "react";
import {
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  Search,
  Dna,
  ShieldAlert,
  ShieldCheck,
  ShieldQuestion,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useGenomicVariants } from "../hooks/useGenomics";
import { VariantExpandedRow } from "./VariantExpandedRow";
import type { GeneDrugInteraction } from "../types";

interface GenomicVariantTableProps {
  patientId: number;
  interactions: GeneDrugInteraction[];
}

const SIGNIFICANCE_OPTIONS = [
  { value: "", label: "All" },
  { value: "pathogenic", label: "Pathogenic" },
  { value: "likely pathogenic", label: "Likely Pathogenic" },
  { value: "uncertain significance", label: "VUS" },
  { value: "benign", label: "Benign" },
  { value: "likely benign", label: "Likely Benign" },
];

const SIG_BADGE: Record<string, { cls: string; icon: typeof ShieldAlert }> = {
  pathogenic: { cls: "bg-[#F0607A]/15 text-[#F0607A]", icon: ShieldAlert },
  "likely pathogenic": { cls: "bg-orange-400/15 text-orange-400", icon: ShieldAlert },
  "uncertain significance": { cls: "bg-[#F59E0B]/15 text-[#F59E0B]", icon: ShieldQuestion },
  "likely benign": { cls: "bg-blue-400/15 text-blue-400", icon: ShieldCheck },
  benign: { cls: "bg-[#2DD4BF]/15 text-[#2DD4BF]", icon: ShieldCheck },
};

function getSigBadge(sig: string | null) {
  if (!sig) return null;
  const key = sig.toLowerCase();
  for (const [k, v] of Object.entries(SIG_BADGE)) {
    if (key.includes(k)) return { ...v, label: k };
  }
  return null;
}

export function GenomicVariantTable({ patientId, interactions }: GenomicVariantTableProps) {
  const [page, setPage] = useState(1);
  const [sigFilter, setSigFilter] = useState("");
  const [geneSearch, setGeneSearch] = useState("");
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const { data: variantsPage, isLoading } = useGenomicVariants({
    person_id: patientId,
    per_page: 25,
    page,
    ...(sigFilter ? { clinvar_significance: sigFilter } : {}),
    ...(geneSearch.trim() ? { gene: geneSearch.trim() } : {}),
  });

  const variants = variantsPage?.data ?? [];
  const totalPages = variantsPage?.last_page ?? 1;
  const totalVariants = variantsPage?.total ?? 0;

  return (
    <div className="space-y-3">
      {/* Header + filters */}
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div className="flex items-center gap-2">
          <Dna size={16} className="text-[#A78BFA]" />
          <h3 className="text-sm font-semibold text-[#E8ECF4]">
            All Variants
            <span className="ml-2 text-xs text-[#4A5068] font-normal">
              ({totalVariants})
            </span>
          </h3>
        </div>
        <div className="flex items-center gap-2">
          {/* Significance filter */}
          <select
            value={sigFilter}
            onChange={(e) => { setSigFilter(e.target.value); setPage(1); }}
            className="rounded-md border border-[#1C1C48] bg-[#10102A] px-2 py-1.5 text-xs text-[#B4BAC8] focus:border-[#2A2A60] focus:outline-none"
          >
            {SIGNIFICANCE_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
          {/* Gene search */}
          <div className="relative">
            <Search size={12} className="absolute left-2 top-1/2 -translate-y-1/2 text-[#4A5068]" />
            <input
              type="text"
              value={geneSearch}
              onChange={(e) => { setGeneSearch(e.target.value); setPage(1); }}
              placeholder="Gene..."
              className="w-24 rounded-md border border-[#1C1C48] bg-[#10102A] pl-6 pr-2 py-1.5 text-xs text-[#B4BAC8] placeholder:text-[#4A5068] focus:border-[#2A2A60] focus:outline-none"
            />
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <span className="text-sm text-[#7A8298]">Loading variants...</span>
          </div>
        ) : variants.length === 0 ? (
          <div className="flex items-center justify-center py-12">
            <span className="text-sm text-[#7A8298]">No variants match filters</span>
          </div>
        ) : (
          <table className="w-full text-xs">
            <thead>
              <tr className="border-b border-[#1C1C48]">
                {["Gene", "Alteration", "Type", "AF", "ClinVar", ""].map((h) => (
                  <th key={h} className="px-4 py-2.5 text-left text-[10px] font-medium text-[#4A5068] uppercase tracking-wider">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[#16163A]">
              {variants.map((v) => {
                const badge = getSigBadge(v.clinvar_significance);
                const BadgeIcon = badge?.icon ?? ShieldQuestion;
                const isExpanded = expandedId === v.id;

                return (
                  <Fragment key={v.id}>
                    <tr
                      className="hover:bg-[#16163A] cursor-pointer transition-colors"
                      onClick={() => setExpandedId(isExpanded ? null : v.id)}
                    >
                      <td className="px-4 py-2.5">
                        <span className="font-semibold text-[#A78BFA]">{v.gene_symbol ?? "—"}</span>
                      </td>
                      <td className="px-4 py-2.5 font-mono text-[#B4BAC8]">
                        {v.hgvs_p ?? v.hgvs_c ?? `${v.chromosome}:${v.position}`}
                      </td>
                      <td className="px-4 py-2.5 text-[#7A8298]">{v.variant_type ?? "—"}</td>
                      <td className="px-4 py-2.5 text-[#7A8298]">
                        {v.allele_frequency != null ? `${(Number(v.allele_frequency) * 100).toFixed(1)}%` : "—"}
                      </td>
                      <td className="px-4 py-2.5">
                        {badge ? (
                          <span className={cn("inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium", badge.cls)}>
                            <BadgeIcon size={10} />
                            {badge.label}
                          </span>
                        ) : (
                          <span className="text-[10px] text-[#4A5068]">—</span>
                        )}
                      </td>
                      <td className="px-4 py-2.5 text-[#4A5068]">
                        <ChevronDown
                          size={12}
                          className={cn("transition-transform", isExpanded && "rotate-180")}
                        />
                      </td>
                    </tr>
                    {isExpanded && (
                      <tr>
                        <td colSpan={6} className="p-0">
                          <VariantExpandedRow
                            variant={v}
                            interactions={interactions}
                            patientId={patientId}
                          />
                        </td>
                      </tr>
                    )}
                  </Fragment>
                );
              })}
            </tbody>
          </table>
        )}

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-[#1C1C48]">
            <span className="text-xs text-[#4A5068]">Page {page} of {totalPages}</span>
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
    </div>
  );
}
