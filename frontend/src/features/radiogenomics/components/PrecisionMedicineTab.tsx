import { Fragment, useState } from "react";
import {
  Dna, Pill, ShieldAlert, ShieldCheck, AlertTriangle,
  ChevronDown, ChevronUp, Loader2, TrendingDown, TrendingUp,
  Minus, FlaskConical, Activity,
} from "lucide-react";
import { useRadiogenomicsPanel } from "../hooks/useRadiogenomics";
import type {
  RadiogenomicsPanel,
  VariantDrugCorrelation,
  PrecisionRecommendation,
  VariantSummary,
  DrugExposure,
} from "../types";

// -- Colors & Constants -----------------------------------------------------

const RELATIONSHIP_STYLES: Record<string, { color: string; label: string; icon: typeof ShieldCheck }> = {
  sensitive:         { color: "#2DD4BF", label: "Sensitive",         icon: ShieldCheck },
  resistant:         { color: "#F0607A", label: "Resistant",         icon: ShieldAlert },
  partial_response:  { color: "#9D75F8", label: "Partial Response",  icon: Activity },
};

const CONFIDENCE_COLORS: Record<string, string> = {
  high:   "#2DD4BF",
  medium: "#9D75F8",
  low:    "#7A8298",
};

const SIGNIFICANCE_COLORS: Record<string, string> = {
  Pathogenic:            "#F0607A",
  "Likely pathogenic":   "#F0607A",
  "Uncertain significance": "#9D75F8",
  Benign:                "#2DD4BF",
  "Likely benign":       "#2DD4BF",
};

// -- Main Component ---------------------------------------------------------

interface PrecisionMedicineTabProps {
  personId: number;
  sourceId?: number;
}

export default function PrecisionMedicineTab({ personId, sourceId }: PrecisionMedicineTabProps) {
  const { data: panel, isLoading, error } = useRadiogenomicsPanel(personId, sourceId);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 size={24} className="animate-spin text-[#A78BFA]" />
      </div>
    );
  }

  if (error || !panel) {
    return (
      <div className="flex flex-col items-center justify-center h-48 rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A]">
        <Dna size={24} className="text-[#4A5068] mb-3" />
        <p className="text-sm text-[#7A8298]">
          {error ? "Failed to load precision medicine panel" : "No genomic data available for this patient"}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {/* Summary Header */}
      <PanelSummary panel={panel} />

      {/* Precision Recommendations (most actionable -- show first) */}
      {panel.recommendations.length > 0 && (
        <RecommendationsSection recommendations={panel.recommendations} />
      )}

      {/* Variant-Drug Correlations */}
      {panel.correlations.length > 0 && (
        <CorrelationsTable correlations={panel.correlations} />
      )}

      {/* Genomic Variants */}
      <VariantsSection variants={panel.variants} />

      {/* Treatment History */}
      {panel.drug_exposures.length > 0 && (
        <TreatmentHistory drugs={panel.drug_exposures} />
      )}
    </div>
  );
}

// -- Panel Summary ----------------------------------------------------------

function PanelSummary({ panel }: { panel: RadiogenomicsPanel }) {
  const { variants, correlations, recommendations, drug_exposures } = panel;

  const stats = [
    { label: "Total Variants", value: variants.total, color: "#A78BFA", icon: Dna },
    { label: "Pathogenic", value: variants.pathogenic_count, color: "#F0607A", icon: AlertTriangle },
    { label: "VUS", value: variants.vus_count, color: "#9D75F8", icon: FlaskConical },
    { label: "Drug Correlations", value: correlations.length, color: "#2DD4BF", icon: Pill },
    { label: "Recommendations", value: recommendations.length, color: "#60A5FA", icon: ShieldCheck },
    { label: "Treatments", value: drug_exposures.length, color: "#E8ECF4", icon: Pill },
  ];

  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      {stats.map((s) => {
        const Icon = s.icon;
        return (
          <div
            key={s.label}
            className="rounded-lg border border-[#1C1C48] bg-[#10102A] px-3 py-3 text-center"
          >
            <Icon size={14} className="mx-auto mb-1" style={{ color: s.color }} />
            <p className="text-lg font-bold font-mono" style={{ color: s.color }}>
              {s.value}
            </p>
            <p className="text-[10px] text-[#4A5068] uppercase tracking-wider">{s.label}</p>
          </div>
        );
      })}
    </div>
  );
}

// -- Recommendations Section ------------------------------------------------

function RecommendationsSection({ recommendations }: { recommendations: PrecisionRecommendation[] }) {
  return (
    <div className="space-y-3">
      <h3 className="text-sm font-semibold text-[#E8ECF4] flex items-center gap-2">
        <ShieldCheck size={14} className="text-[#60A5FA]" />
        Precision Medicine Recommendations
      </h3>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {recommendations.map((rec, i) => (
          <RecommendationCard key={i} rec={rec} />
        ))}
      </div>
    </div>
  );
}

function RecommendationCard({ rec }: { rec: PrecisionRecommendation }) {
  const [expanded, setExpanded] = useState(true);

  return (
    <div className="rounded-lg border border-[#60A5FA]/20 bg-[#60A5FA]/5 overflow-hidden">
      <button
        type="button"
        onClick={() => setExpanded(!expanded)}
        className="w-full flex items-center justify-between px-4 py-3"
      >
        <div className="flex items-center gap-2">
          <Dna size={14} className="text-[#A78BFA]" />
          <span className="text-sm font-semibold text-[#E8ECF4]">
            {rec.gene} {rec.variant}
          </span>
        </div>
        {expanded ? <ChevronUp size={14} className="text-[#4A5068]" /> : <ChevronDown size={14} className="text-[#4A5068]" />}
      </button>

      {expanded && (
        <div className="px-4 pb-4 space-y-3">
          {/* Drugs to avoid */}
          {rec.drugs_avoid.length > 0 && (
            <div>
              <p className="text-[10px] text-[#F0607A] uppercase tracking-wider font-semibold mb-1.5">
                Avoid (predicted resistance)
              </p>
              <div className="flex flex-wrap gap-1.5">
                {rec.drugs_avoid.map((d) => (
                  <span
                    key={d}
                    className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-[#F0607A]/10 text-[#F0607A] border border-[#F0607A]/20"
                  >
                    <ShieldAlert size={10} />
                    {d}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Drugs to consider */}
          {rec.drugs_consider.length > 0 && (
            <div>
              <p className="text-[10px] text-[#2DD4BF] uppercase tracking-wider font-semibold mb-1.5">
                Consider (predicted sensitivity)
              </p>
              <div className="flex flex-wrap gap-1.5">
                {rec.drugs_consider.map((d) => (
                  <span
                    key={d}
                    className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-[#2DD4BF]/10 text-[#2DD4BF] border border-[#2DD4BF]/20"
                  >
                    <ShieldCheck size={10} />
                    {d}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Rationale */}
          <p className="text-xs text-[#7A8298] italic leading-relaxed">{rec.rationale}</p>
        </div>
      )}
    </div>
  );
}

// -- Correlations Table -----------------------------------------------------

function CorrelationsTable({ correlations }: { correlations: VariantDrugCorrelation[] }) {
  const [expanded, setExpanded] = useState<number | null>(null);

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] overflow-hidden">
      <div className="px-4 py-3 border-b border-[#1C1C48] flex items-center justify-between">
        <h3 className="text-sm font-semibold text-[#E8ECF4] flex items-center gap-2">
          <Pill size={14} className="text-[#2DD4BF]" />
          Variant-Drug Correlations ({correlations.length})
        </h3>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead>
            <tr className="border-b border-[#16163A] text-[10px] uppercase tracking-wider text-[#4A5068]">
              <th className="px-4 py-2 font-medium">Gene</th>
              <th className="px-4 py-2 font-medium">Variant</th>
              <th className="px-4 py-2 font-medium">Drug</th>
              <th className="px-4 py-2 font-medium">Relationship</th>
              <th className="px-4 py-2 font-medium">Confidence</th>
              <th className="px-4 py-2 font-medium">Received</th>
              <th className="px-4 py-2 font-medium">Response</th>
              <th className="px-4 py-2 font-medium w-6"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#16163A]">
            {correlations.map((c, i) => {
              const relStyle = RELATIONSHIP_STYLES[c.relationship] ?? RELATIONSHIP_STYLES.sensitive;
              const RelIcon = relStyle.icon;
              const isExpanded = expanded === i;

              return (
                <Fragment key={i}>
                  <tr className="group">
                    <td className="px-4 py-2.5">
                      <span className="text-xs font-semibold text-[#A78BFA]">{c.gene_symbol}</span>
                    </td>
                    <td className="px-4 py-2.5">
                      <span className="text-xs font-mono text-[#B4BAC8]">{c.hgvs_p ?? "\u2014"}</span>
                    </td>
                    <td className="px-4 py-2.5">
                      <span className="text-xs text-[#E8ECF4]">{c.drug_name}</span>
                    </td>
                    <td className="px-4 py-2.5">
                      <span
                        className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                        style={{ backgroundColor: `${relStyle.color}18`, color: relStyle.color }}
                      >
                        <RelIcon size={10} />
                        {relStyle.label}
                      </span>
                    </td>
                    <td className="px-4 py-2.5">
                      <span
                        className="text-[10px] font-medium uppercase"
                        style={{ color: CONFIDENCE_COLORS[c.confidence] ?? "#7A8298" }}
                      >
                        {c.confidence}
                      </span>
                    </td>
                    <td className="px-4 py-2.5">
                      {c.patient_received_drug ? (
                        <span className="inline-flex items-center gap-1 text-[10px] text-[#2DD4BF]">
                          <ShieldCheck size={10} />
                          Yes{c.drug_days ? ` (${c.drug_days}d)` : ""}
                        </span>
                      ) : (
                        <span className="text-[10px] text-[#4A5068]">No</span>
                      )}
                    </td>
                    <td className="px-4 py-2.5">
                      {c.response_category ? (
                        <ResponseChip category={c.response_category} />
                      ) : (
                        <span className="text-[10px] text-[#4A5068]">{"\u2014"}</span>
                      )}
                    </td>
                    <td className="px-4 py-2.5">
                      <button
                        type="button"
                        onClick={() => setExpanded(isExpanded ? null : i)}
                        className="text-[#4A5068] hover:text-[#B4BAC8] transition-colors"
                      >
                        {isExpanded ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
                      </button>
                    </td>
                  </tr>
                  {isExpanded && (
                    <tr>
                      <td colSpan={8} className="px-4 py-3 bg-[#0A0A18]">
                        <div className="space-y-2 text-xs text-[#7A8298]">
                          {c.mechanism && <p><span className="text-[#4A5068]">Mechanism:</span> {c.mechanism}</p>}
                          {c.evidence_summary && <p><span className="text-[#4A5068]">Evidence:</span> {c.evidence_summary}</p>}
                          {c.response_rationale && <p><span className="text-[#4A5068]">Rationale:</span> {c.response_rationale}</p>}
                          {c.drug_start && (
                            <p>
                              <span className="text-[#4A5068]">Treatment period:</span>{" "}
                              {c.drug_start} -- {c.drug_end ?? "ongoing"}
                            </p>
                          )}
                          <p>
                            <span className="text-[#4A5068]">Evidence level:</span>{" "}
                            <span className="text-[#9D75F8]">{c.evidence_level}</span>
                            {" -- "}
                            <span className="text-[#4A5068]">ClinVar:</span> {c.clinvar_significance}
                          </p>
                        </div>
                      </td>
                    </tr>
                  )}
                </Fragment>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// -- Variants Section -------------------------------------------------------

function VariantsSection({ variants }: { variants: VariantSummary }) {
  const [showAll, setShowAll] = useState(false);
  const displayVariants = showAll ? variants.all : variants.all.slice(0, 10);

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] overflow-hidden">
      <div className="px-4 py-3 border-b border-[#1C1C48] flex items-center justify-between">
        <h3 className="text-sm font-semibold text-[#E8ECF4] flex items-center gap-2">
          <Dna size={14} className="text-[#A78BFA]" />
          Genomic Variants ({variants.total})
        </h3>
        <div className="flex items-center gap-3 text-[10px]">
          <span className="text-[#F0607A]">{variants.pathogenic_count} pathogenic</span>
          <span className="text-[#9D75F8]">{variants.vus_count} VUS</span>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead>
            <tr className="border-b border-[#16163A] text-[10px] uppercase tracking-wider text-[#4A5068]">
              <th className="px-4 py-2 font-medium">Gene</th>
              <th className="px-4 py-2 font-medium">Variant</th>
              <th className="px-4 py-2 font-medium">Class</th>
              <th className="px-4 py-2 font-medium">Significance</th>
              <th className="px-4 py-2 font-medium">Disease</th>
              <th className="px-4 py-2 font-medium">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-[#16163A]">
            {displayVariants.map((v) => {
              const isActionable = v.id in variants.actionable;
              const isVus = v.id in variants.vus;
              const sigColor = SIGNIFICANCE_COLORS[v.clinvar_significance] ?? "#7A8298";

              return (
                <tr key={v.id} className="group hover:bg-[#16163A] transition-colors">
                  <td className="px-4 py-2.5">
                    <span className="text-xs font-semibold text-[#A78BFA]">{v.gene_symbol}</span>
                  </td>
                  <td className="px-4 py-2.5">
                    <span className="text-xs font-mono text-[#B4BAC8]">{v.hgvs_p ?? "\u2014"}</span>
                  </td>
                  <td className="px-4 py-2.5">
                    <span className="text-xs text-[#7A8298]">{v.variant_class}</span>
                  </td>
                  <td className="px-4 py-2.5">
                    <span className="text-xs font-medium" style={{ color: sigColor }}>
                      {v.clinvar_significance}
                    </span>
                  </td>
                  <td className="px-4 py-2.5">
                    <span className="text-xs text-[#7A8298] max-w-[200px] truncate block">
                      {v.clinvar_disease ?? "\u2014"}
                    </span>
                  </td>
                  <td className="px-4 py-2.5">
                    {isActionable && (
                      <span className="inline-flex items-center gap-1 text-[10px] font-medium text-[#F0607A] bg-[#F0607A]/10 px-2 py-0.5 rounded-full">
                        <AlertTriangle size={9} />
                        Actionable
                      </span>
                    )}
                    {isVus && (
                      <span className="inline-flex items-center gap-1 text-[10px] font-medium text-[#9D75F8] bg-[#9D75F8]/10 px-2 py-0.5 rounded-full">
                        <FlaskConical size={9} />
                        VUS
                      </span>
                    )}
                    {!isActionable && !isVus && (
                      <span className="text-[10px] text-[#4A5068]">Other</span>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {variants.all.length > 10 && (
        <div className="px-4 py-2 border-t border-[#16163A]">
          <button
            type="button"
            onClick={() => setShowAll(!showAll)}
            className="text-xs text-[#A78BFA] hover:text-[#C4B5FD] transition-colors"
          >
            {showAll ? "Show less" : `Show all ${variants.total} variants`}
          </button>
        </div>
      )}
    </div>
  );
}

// -- Treatment History ------------------------------------------------------

function TreatmentHistory({ drugs }: { drugs: DrugExposure[] }) {
  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] overflow-hidden">
      <div className="px-4 py-3 border-b border-[#1C1C48]">
        <h3 className="text-sm font-semibold text-[#E8ECF4] flex items-center gap-2">
          <Pill size={14} className="text-[#9D75F8]" />
          Treatment History ({drugs.length})
        </h3>
      </div>

      <div className="divide-y divide-[#16163A]">
        {drugs.map((d, i) => (
          <div key={i} className="flex items-center justify-between px-4 py-2.5">
            <div className="flex items-center gap-3">
              <div
                className="w-2 h-2 rounded-full"
                style={{ backgroundColor: "#9D75F8" }}
              />
              <div>
                <p className="text-xs font-medium text-[#E8ECF4]">{d.drug_name}</p>
                {d.drug_class && (
                  <p className="text-[10px] text-[#4A5068]">{d.drug_class}</p>
                )}
              </div>
            </div>
            <div className="text-right">
              <p className="text-xs text-[#7A8298]">
                {d.start_date} -- {d.end_date ?? "ongoing"}
              </p>
              <p className="text-[10px] text-[#4A5068]">{d.total_days} days</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

// -- Shared Components ------------------------------------------------------

function ResponseChip({ category }: { category: string }) {
  const styles: Record<string, { color: string; icon: typeof TrendingDown }> = {
    CR: { color: "#2DD4BF", icon: TrendingDown },
    PR: { color: "#60A5FA", icon: TrendingDown },
    SD: { color: "#9D75F8", icon: Minus },
    PD: { color: "#F0607A", icon: TrendingUp },
  };
  const s = styles[category] ?? { color: "#7A8298", icon: Minus };
  const Icon = s.icon;

  return (
    <span
      className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
      style={{ backgroundColor: `${s.color}18`, color: s.color }}
    >
      <Icon size={9} />
      {category}
    </span>
  );
}
