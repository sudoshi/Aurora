/**
 * VariantCard -- individual variant display card with gene, alteration,
 * classification, and actionability details.
 */
import {
  X,
  Dna,
  ShieldAlert,
  ShieldCheck,
  ShieldQuestion,
  MapPin,
  Fingerprint,
  BarChart3,
} from "lucide-react";
import type { GenomicVariant } from "@/features/genomics/types";

const CLINVAR_INFO: Record<string, { color: string; bg: string; icon: typeof ShieldAlert; label: string }> = {
  pathogenic:             { color: "#E85A6B", bg: "bg-[#E85A6B]/10", icon: ShieldAlert, label: "Pathogenic" },
  "likely pathogenic":    { color: "#F97316", bg: "bg-orange-400/10", icon: ShieldAlert, label: "Likely Pathogenic" },
  "uncertain significance": { color: "#F59E0B", bg: "bg-amber-400/10", icon: ShieldQuestion, label: "VUS" },
  "likely benign":        { color: "#60A5FA", bg: "bg-blue-400/10", icon: ShieldCheck, label: "Likely Benign" },
  benign:                 { color: "#2DD4BF", bg: "bg-[#2DD4BF]/10", icon: ShieldCheck, label: "Benign" },
};

function getClinvarInfo(sig: string | null) {
  if (!sig) return null;
  const key = sig.toLowerCase();
  for (const [k, v] of Object.entries(CLINVAR_INFO)) {
    if (key.includes(k)) return v;
  }
  return null;
}

interface VariantCardProps {
  variant: GenomicVariant;
  onClose: () => void;
}

export function VariantCard({ variant, onClose }: VariantCardProps) {
  const info = getClinvarInfo(variant.clinvar_significance);
  const InfoIcon = info?.icon ?? ShieldQuestion;
  const isActionable =
    variant.clinvar_significance?.toLowerCase().includes("pathogenic") &&
    !variant.clinvar_significance?.toLowerCase().includes("benign");

  return (
    <div className="rounded-lg border border-[#A78BFA]/20 bg-[#A78BFA]/5 p-4 space-y-3">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-2">
          <Dna size={16} className="text-[#A78BFA]" />
          <div>
            <span className="text-sm font-semibold text-[#F0EDE8]">
              {variant.gene_symbol ?? "Unknown Gene"}
            </span>
            <span className="ml-2 text-xs font-mono text-[#C5C0B8]">
              {variant.hgvs_p ?? variant.hgvs_c ?? `${variant.chromosome}:${variant.position}`}
            </span>
          </div>
        </div>
        <button
          type="button"
          onClick={onClose}
          className="text-[#5A5650] hover:text-[#C5C0B8] transition-colors"
        >
          <X size={14} />
        </button>
      </div>

      {/* Classification badge */}
      <div className="flex items-center gap-3">
        {info && (
          <span
            className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${info.bg}`}
            style={{ color: info.color }}
          >
            <InfoIcon size={12} />
            {info.label}
          </span>
        )}
        {isActionable && (
          <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-[#E85A6B]/10 text-[#E85A6B] border border-[#E85A6B]/20">
            <ShieldAlert size={10} />
            Actionable
          </span>
        )}
      </div>

      {/* Details grid */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <DetailItem
          icon={MapPin}
          label="Location"
          value={`chr${variant.chromosome}:${variant.position.toLocaleString()}`}
        />
        <DetailItem
          icon={Dna}
          label="Alleles"
          value={`${variant.reference_allele} > ${variant.alternate_allele}`}
        />
        <DetailItem
          icon={Fingerprint}
          label="Zygosity"
          value={variant.zygosity ?? "\u2014"}
        />
        <DetailItem
          icon={BarChart3}
          label="Allele Freq"
          value={
            variant.allele_frequency != null
              ? `${(variant.allele_frequency * 100).toFixed(1)}%`
              : "\u2014"
          }
        />
      </div>

      {/* Additional info row */}
      <div className="flex flex-wrap gap-3 text-xs text-[#8A857D]">
        {variant.variant_type && (
          <span>Type: <span className="text-[#C5C0B8]">{variant.variant_type}</span></span>
        )}
        {variant.variant_class && (
          <span>Class: <span className="text-[#C5C0B8]">{variant.variant_class}</span></span>
        )}
        {variant.clinvar_id && (
          <span>ClinVar: <span className="text-[#C5C0B8]">{variant.clinvar_id}</span></span>
        )}
        {variant.cosmic_id && (
          <span>COSMIC: <span className="text-[#C5C0B8]">{variant.cosmic_id}</span></span>
        )}
        {variant.genome_build && (
          <span>Build: <span className="text-[#C5C0B8]">{variant.genome_build}</span></span>
        )}
      </div>
    </div>
  );
}

function DetailItem({
  icon: Icon,
  label,
  value,
}: {
  icon: typeof Dna;
  label: string;
  value: string;
}) {
  return (
    <div className="flex items-center gap-2">
      <Icon size={12} className="text-[#5A5650] flex-shrink-0" />
      <div>
        <p className="text-[10px] text-[#5A5650] uppercase tracking-wider">{label}</p>
        <p className="text-xs text-[#C5C0B8] font-mono">{value}</p>
      </div>
    </div>
  );
}
