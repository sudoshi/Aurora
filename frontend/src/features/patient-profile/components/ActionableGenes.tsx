/**
 * ActionableGenes -- summary of actionable genes with targeted therapy suggestions.
 *
 * Displays pathogenic/likely-pathogenic genes with known targeted therapies
 * from a curated lookup table of common gene-drug associations.
 */
import { AlertTriangle, Pill, Dna } from "lucide-react";

/**
 * Curated mapping of commonly actionable genes to suggested targeted therapies.
 * This is a frontend-only reference; actual recommendations come from the
 * radiogenomics precision medicine panel.
 */
const GENE_THERAPY_MAP: Record<string, string[]> = {
  EGFR: ["Osimertinib", "Erlotinib", "Gefitinib", "Afatinib"],
  ALK: ["Alectinib", "Crizotinib", "Ceritinib", "Lorlatinib"],
  BRAF: ["Vemurafenib", "Dabrafenib + Trametinib", "Encorafenib"],
  HER2: ["Trastuzumab", "Pertuzumab", "T-DXd"],
  ERBB2: ["Trastuzumab", "Pertuzumab", "T-DXd"],
  BRCA1: ["Olaparib", "Rucaparib", "Niraparib"],
  BRCA2: ["Olaparib", "Rucaparib", "Niraparib"],
  KRAS: ["Sotorasib (G12C)", "Adagrasib (G12C)"],
  ROS1: ["Crizotinib", "Entrectinib"],
  NTRK1: ["Larotrectinib", "Entrectinib"],
  NTRK2: ["Larotrectinib", "Entrectinib"],
  NTRK3: ["Larotrectinib", "Entrectinib"],
  RET: ["Selpercatinib", "Pralsetinib"],
  MET: ["Capmatinib", "Tepotinib"],
  PIK3CA: ["Alpelisib"],
  FGFR2: ["Pemigatinib", "Futibatinib"],
  FGFR3: ["Erdafitinib"],
  IDH1: ["Ivosidenib"],
  IDH2: ["Enasidenib"],
  FLT3: ["Midostaurin", "Gilteritinib"],
  BCR_ABL1: ["Imatinib", "Dasatinib", "Nilotinib"],
  KIT: ["Imatinib", "Avapritinib"],
  PDGFRA: ["Imatinib", "Avapritinib"],
};

interface ActionableGenesProps {
  genes: string[];
}

export function ActionableGenes({ genes }: ActionableGenesProps) {
  if (genes.length === 0) return null;

  return (
    <div className="rounded-lg border border-[#E85A6B]/20 bg-[#E85A6B]/5 p-4 space-y-3">
      <div className="flex items-center gap-2">
        <AlertTriangle size={14} className="text-[#E85A6B]" />
        <h4 className="text-sm font-semibold text-[#F0EDE8]">
          Actionable Genes Detected ({genes.length})
        </h4>
      </div>

      <div className="space-y-2.5">
        {genes.map((gene) => {
          const therapies = GENE_THERAPY_MAP[gene] ?? GENE_THERAPY_MAP[gene.toUpperCase()];

          return (
            <div
              key={gene}
              className="rounded-md border border-[#232328] bg-[#151518] px-3 py-2.5"
            >
              <div className="flex items-center gap-2 mb-1.5">
                <Dna size={12} className="text-[#E85A6B]" />
                <span className="text-xs font-semibold text-[#E85A6B]">{gene}</span>
                <span className="text-[10px] text-[#8A857D]">-- Pathogenic / Likely Pathogenic</span>
              </div>

              {therapies && therapies.length > 0 ? (
                <div className="flex flex-wrap gap-1.5">
                  {therapies.map((drug) => (
                    <span
                      key={drug}
                      className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#2DD4BF]/10 text-[#2DD4BF] border border-[#2DD4BF]/20"
                    >
                      <Pill size={9} />
                      {drug}
                    </span>
                  ))}
                </div>
              ) : (
                <p className="text-[10px] text-[#5A5650]">
                  No standard targeted therapies mapped. Consult molecular tumor board for recommendations.
                </p>
              )}
            </div>
          );
        })}
      </div>

      <p className="text-[10px] text-[#5A5650] italic">
        Therapy suggestions are for reference only. Consult the Precision Medicine tab or Molecular Tumor Board for
        evidence-based recommendations with confidence levels.
      </p>
    </div>
  );
}
