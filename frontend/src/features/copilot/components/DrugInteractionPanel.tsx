import { useState } from "react";
import { Pill, AlertTriangle, AlertCircle, Info, Search } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Skeleton } from "@/components/ui/Skeleton";
import { EmptyState } from "@/components/ui/EmptyState";
import { useDrugInteractionCheck } from "../hooks/useDecisionSupport";
import type { DrugInteraction } from "../types/decision-support";

// ---------------------------------------------------------------------------
// Severity helpers
// ---------------------------------------------------------------------------

const SEVERITY_CONFIG: Record<
  string,
  { color: string; bg: string; border: string; icon: React.ReactNode; label: string }
> = {
  major: {
    color: "#F0607A",
    bg: "#F0607A10",
    border: "#F0607A30",
    icon: <AlertCircle size={14} />,
    label: "Major",
  },
  moderate: {
    color: "#F59E0B",
    bg: "#F59E0B10",
    border: "#F59E0B30",
    icon: <AlertTriangle size={14} />,
    label: "Moderate",
  },
  minor: {
    color: "#2DD4BF",
    bg: "#2DD4BF10",
    border: "#2DD4BF30",
    icon: <Info size={14} />,
    label: "Minor",
  },
};

// ---------------------------------------------------------------------------
// InteractionCard
// ---------------------------------------------------------------------------

function InteractionCard({ interaction }: { interaction: DrugInteraction }) {
  const config = SEVERITY_CONFIG[interaction.severity] ?? SEVERITY_CONFIG.minor;

  return (
    <div
      className="rounded-lg p-4 space-y-2"
      style={{ backgroundColor: config.bg, border: `1px solid ${config.border}` }}
    >
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-2 min-w-0">
          <span style={{ color: config.color }}>{config.icon}</span>
          <p className="text-sm font-medium text-[var(--text-primary)] truncate">
            {interaction.drug_a} + {interaction.drug_b}
          </p>
        </div>
        <span
          className="text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase shrink-0"
          style={{ color: config.color, backgroundColor: `${config.color}20`, border: `1px solid ${config.border}` }}
        >
          {config.label}
        </span>
      </div>

      <div className="space-y-1.5">
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Mechanism
          </p>
          <p className="text-xs text-[var(--text-secondary)]">{interaction.mechanism}</p>
        </div>
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Clinical Significance
          </p>
          <p className="text-xs text-[var(--text-secondary)]">
            {interaction.clinical_significance}
          </p>
        </div>
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Recommendation
          </p>
          <p className="text-xs text-[var(--text-primary)] font-medium">
            {interaction.recommendation}
          </p>
        </div>
      </div>
    </div>
  );
}

// ---------------------------------------------------------------------------
// DrugInteractionPanel
// ---------------------------------------------------------------------------

interface DrugInteractionPanelProps {
  currentMedications: string[];
}

export function DrugInteractionPanel({ currentMedications }: DrugInteractionPanelProps) {
  const [proposedMed, setProposedMed] = useState("");
  const mutation = useDrugInteractionCheck();

  const handleCheck = () => {
    mutation.mutate({
      medications: currentMedications,
      proposedMedication: proposedMed.trim() || undefined,
    });
  };

  return (
    <div className="space-y-4">
      {/* Current medications */}
      <div>
        <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1.5">
          Current Medications ({currentMedications.length})
        </p>
        {currentMedications.length > 0 ? (
          <div className="flex flex-wrap gap-1">
            {currentMedications.map((med) => (
              <Badge key={med} variant="info" className="text-[10px]">
                {med}
              </Badge>
            ))}
          </div>
        ) : (
          <p className="text-xs text-[var(--text-ghost)]">No medications loaded</p>
        )}
      </div>

      {/* Proposed medication */}
      <div className="space-y-2">
        <label className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
          Proposed Medication (optional)
        </label>
        <div className="flex gap-2">
          <input
            type="text"
            value={proposedMed}
            onChange={(e) => setProposedMed(e.target.value)}
            placeholder="Enter a medication to check interactions..."
            className="flex-1 rounded-lg border border-[var(--border-default)] bg-[var(--surface-base)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:outline-none focus:border-[#2DD4BF]/50"
          />
          <Button
            variant="primary"
            size="sm"
            onClick={handleCheck}
            disabled={currentMedications.length === 0 || mutation.isPending}
          >
            <Search size={12} className="mr-1.5" />
            {mutation.isPending ? "Checking..." : "Check"}
          </Button>
        </div>
      </div>

      {/* Loading */}
      {mutation.isPending && (
        <div className="space-y-3">
          {Array.from({ length: 2 }).map((_, i) => (
            <div key={i} className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 space-y-2">
              <Skeleton variant="text" width="60%" />
              <Skeleton variant="text" count={3} />
            </div>
          ))}
        </div>
      )}

      {/* Error */}
      {mutation.isError && (
        <div className="rounded-lg border border-[#F0607A]/20 bg-[#F0607A]/5 p-4 text-center">
          <p className="text-sm text-[#F0607A]">Failed to check drug interactions</p>
          <p className="text-xs text-[var(--text-muted)] mt-1">Please try again.</p>
        </div>
      )}

      {/* Results */}
      {mutation.data && (
        <div className="space-y-3">
          {mutation.data.interactions.length === 0 ? (
            <div className="rounded-lg border border-[#2DD4BF]/20 bg-[#2DD4BF]/5 p-4 text-center">
              <p className="text-sm text-[#2DD4BF] font-medium">No interactions detected</p>
              <p className="text-xs text-[var(--text-muted)] mt-1">
                No known drug interactions found for this combination.
              </p>
            </div>
          ) : (
            mutation.data.interactions.map((interaction, i) => (
              <InteractionCard key={`${interaction.drug_a}-${interaction.drug_b}-${i}`} interaction={interaction} />
            ))
          )}
        </div>
      )}

      {/* Empty prompt */}
      {!mutation.data && !mutation.isPending && !mutation.isError && (
        <EmptyState
          icon={<Pill size={32} className="text-[var(--text-ghost)]" />}
          title="Drug Interaction Checker"
          message="Click 'Check' to analyze interactions between current medications, or add a proposed medication to check."
        />
      )}
    </div>
  );
}
