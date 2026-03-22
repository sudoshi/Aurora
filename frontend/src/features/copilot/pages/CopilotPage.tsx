import { useState, useMemo } from "react";
import {
  FlaskConical,
  BookOpen,
  Pill,
  Dna,
  TrendingUp,
  Search,
  Sparkles,
} from "lucide-react";
// cn imported if needed by sub-components
import { TabBar, TabPanel } from "@/components/ui/Tabs";
import { Skeleton } from "@/components/ui/Skeleton";
import { EmptyState } from "@/components/ui/EmptyState";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { usePatientProfile } from "@/features/patient-profile/hooks/useProfiles";
import { TrialMatchResults } from "../components/TrialMatchResults";
import { GuidelineConcordance } from "../components/GuidelineConcordance";
import { DrugInteractionPanel } from "../components/DrugInteractionPanel";
import { useTrialMatch, useVariantInterpretation, usePrognosticScores } from "../hooks/useDecisionSupport";
import type { PatientContext, VariantInterpretation, PrognosticScore } from "../types/decision-support";

// ---------------------------------------------------------------------------
// Tabs config
// ---------------------------------------------------------------------------

const COPILOT_TABS = [
  { id: "trials", label: "Trial Matching", icon: <FlaskConical size={14} /> },
  { id: "guidelines", label: "Guidelines", icon: <BookOpen size={14} /> },
  { id: "drugs", label: "Drug Interactions", icon: <Pill size={14} /> },
  { id: "genomics", label: "Genomics", icon: <Dna size={14} /> },
  { id: "prognosis", label: "Prognosis", icon: <TrendingUp size={14} /> },
];

// ---------------------------------------------------------------------------
// Genomics sub-component
// ---------------------------------------------------------------------------

function GenomicsPanel({ patientId: _patientId }: { patientId: number | null }) {
  const [gene, setGene] = useState("");
  const [variant, setVariant] = useState("");
  const [cancerType, setCancerType] = useState("");
  const mutation = useVariantInterpretation();

  const handleInterpret = () => {
    if (!gene.trim() || !variant.trim()) return;
    mutation.mutate({
      gene: gene.trim(),
      variant: variant.trim(),
      cancerType: cancerType.trim() || undefined,
    });
  };

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Gene
          </label>
          <input
            type="text"
            value={gene}
            onChange={(e) => setGene(e.target.value)}
            placeholder="e.g., BRCA1"
            className="mt-1 w-full rounded-lg border border-[var(--border-default)] bg-[var(--surface-base)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:outline-none focus:border-[#2DD4BF]/50"
          />
        </div>
        <div>
          <label className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Variant
          </label>
          <input
            type="text"
            value={variant}
            onChange={(e) => setVariant(e.target.value)}
            placeholder="e.g., p.R1699W"
            className="mt-1 w-full rounded-lg border border-[var(--border-default)] bg-[var(--surface-base)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:outline-none focus:border-[#2DD4BF]/50"
          />
        </div>
        <div>
          <label className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Cancer Type (optional)
          </label>
          <input
            type="text"
            value={cancerType}
            onChange={(e) => setCancerType(e.target.value)}
            placeholder="e.g., Breast"
            className="mt-1 w-full rounded-lg border border-[var(--border-default)] bg-[var(--surface-base)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:outline-none focus:border-[#2DD4BF]/50"
          />
        </div>
      </div>
      <Button
        variant="primary"
        size="sm"
        onClick={handleInterpret}
        disabled={!gene.trim() || !variant.trim() || mutation.isPending}
      >
        <Dna size={12} className="mr-1.5" />
        {mutation.isPending ? "Interpreting..." : "Interpret Variant"}
      </Button>

      {mutation.isError && (
        <div className="rounded-lg border border-[#E85A6B]/20 bg-[#E85A6B]/5 p-4 text-center">
          <p className="text-sm text-[#E85A6B]">Failed to interpret variant</p>
        </div>
      )}

      {mutation.data && <VariantResult data={mutation.data} />}

      {!mutation.data && !mutation.isPending && !mutation.isError && (
        <EmptyState
          icon={<Dna size={32} className="text-[var(--text-ghost)]" />}
          title="Genomic Variant Interpretation"
          message="Enter a gene and variant above to get AI-powered interpretation including clinical significance and targeted therapies."
        />
      )}
    </div>
  );
}

function VariantResult({ data }: { data: VariantInterpretation }) {
  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 space-y-3">
      <div className="flex items-center justify-between">
        <p className="text-sm font-semibold text-[var(--text-primary)]">
          {data.gene} {data.variant}
        </p>
        <Badge variant={data.actionable ? "success" : "inactive"} className="text-[10px]">
          {data.actionable ? "Actionable" : "Not Actionable"}
        </Badge>
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Classification
          </p>
          <p className="text-xs text-[var(--text-secondary)]">{data.classification}</p>
        </div>
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider">
            Clinical Significance
          </p>
          <p className="text-xs text-[var(--text-secondary)]">{data.clinical_significance}</p>
        </div>
      </div>

      {data.targeted_therapies.length > 0 && (
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1">
            Targeted Therapies
          </p>
          <div className="flex flex-wrap gap-1">
            {data.targeted_therapies.map((t) => (
              <Badge key={t} variant="primary" className="text-[10px]">{t}</Badge>
            ))}
          </div>
        </div>
      )}

      {data.clinical_trials.length > 0 && (
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1">
            Related Clinical Trials
          </p>
          <div className="flex flex-wrap gap-1">
            {data.clinical_trials.map((t) => (
              <Badge key={t} variant="accent" className="text-[10px]">{t}</Badge>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Prognosis sub-component
// ---------------------------------------------------------------------------

const RISK_STYLES: Record<string, { color: string; bg: string }> = {
  low_risk:     { color: "#2DD4BF", bg: "#2DD4BF15" },
  intermediate: { color: "#F59E0B", bg: "#F59E0B15" },
  high_risk:    { color: "#E85A6B", bg: "#E85A6B15" },
};

function PrognosisPanel({ patientContext }: { patientContext: PatientContext | null }) {
  const { data, isLoading, isError } = usePrognosticScores(patientContext);

  if (isLoading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 space-y-2">
            <Skeleton variant="text" width="50%" />
            <Skeleton variant="text" width="30%" height="24px" />
            <Skeleton variant="text" width="80%" />
          </div>
        ))}
      </div>
    );
  }

  if (isError) {
    return (
      <div className="rounded-lg border border-[#E85A6B]/20 bg-[#E85A6B]/5 p-4 text-center">
        <p className="text-sm text-[#E85A6B]">Failed to compute prognostic scores</p>
      </div>
    );
  }

  if (!data || data.scores.length === 0) {
    return (
      <EmptyState
        icon={<TrendingUp size={32} className="text-[var(--text-ghost)]" />}
        title="No Prognostic Scores"
        message={
          patientContext
            ? "No prognostic scores could be computed for this patient."
            : "Select a patient to compute prognostic scores."
        }
      />
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
      {data.scores.map((score) => (
        <PrognosticCard key={score.score_name} score={score} />
      ))}
    </div>
  );
}

function PrognosticCard({ score }: { score: PrognosticScore }) {
  const style = RISK_STYLES[score.category] ?? RISK_STYLES.intermediate;
  const categoryLabel = score.category.replace("_", " ");

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 space-y-2">
      <div className="flex items-center justify-between">
        <p className="text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">
          {score.score_name}
        </p>
        <span
          className="text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase"
          style={{ color: style.color, backgroundColor: style.bg }}
        >
          {categoryLabel}
        </span>
      </div>
      <p
        className="text-2xl font-bold font-['IBM_Plex_Mono',monospace]"
        style={{ color: style.color }}
      >
        {score.value}
      </p>
      <p className="text-xs text-[var(--text-secondary)]">{score.interpretation}</p>
    </div>
  );
}

// ---------------------------------------------------------------------------
// CopilotPage
// ---------------------------------------------------------------------------

export default function CopilotPage() {
  const [activeTab, setActiveTab] = useState("trials");
  const [patientIdInput, setPatientIdInput] = useState("");
  const [selectedPatientId, setSelectedPatientId] = useState<number | null>(null);

  const { data: profile } = usePatientProfile(selectedPatientId);

  const handleSearch = () => {
    const parsed = Number(patientIdInput.trim());
    if (!Number.isNaN(parsed) && parsed > 0) {
      setSelectedPatientId(parsed);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") handleSearch();
  };

  // Derive patient context from profile
  const patientContext: PatientContext | null = useMemo(() => {
    if (!selectedPatientId || !profile) return null;
    return {
      patient_id: selectedPatientId,
      conditions: (profile.conditions ?? []).map((c) => c.concept_name).filter(Boolean),
      medications: (profile.medications ?? []).map((m) => m.concept_name).filter(Boolean),
      sex: profile.patient.sex ?? undefined,
    };
  }, [selectedPatientId, profile]);

  const currentMedications = useMemo(() => {
    if (!profile) return [];
    return [...new Set((profile.medications ?? []).map((m) => m.concept_name).filter(Boolean))];
  }, [profile]);

  // Trial match query
  const trialQuery = useTrialMatch(selectedPatientId);

  return (
    <div className="space-y-6">
      {/* Page header */}
      <div>
        <div className="flex items-center gap-2 mb-1">
          <Sparkles size={20} className="text-[#2DD4BF]" />
          <h1 className="text-2xl font-bold text-[var(--text-primary)]">
            Abby Copilot
          </h1>
        </div>
        <p className="text-sm text-[var(--text-muted)]">
          AI-powered clinical decision support
        </p>
      </div>

      {/* Patient selector */}
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-2 flex-1 max-w-md">
          <div className="relative flex-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-ghost)]" />
            <input
              type="text"
              value={patientIdInput}
              onChange={(e) => setPatientIdInput(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Enter patient ID..."
              className="w-full rounded-lg border border-[var(--border-default)] bg-[var(--surface-base)] pl-9 pr-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:outline-none focus:border-[#2DD4BF]/50"
            />
          </div>
          <Button variant="primary" size="sm" onClick={handleSearch}>
            Load Patient
          </Button>
        </div>

        {selectedPatientId && profile && (
          <div className="flex items-center gap-2">
            <Badge variant="primary" className="text-xs">
              Patient #{selectedPatientId}
            </Badge>
            <span className="text-xs text-[var(--text-muted)]">
              {profile.patient.first_name} {profile.patient.last_name}
            </span>
          </div>
        )}
      </div>

      {/* No patient selected */}
      {!selectedPatientId && (
        <EmptyState
          icon={<Sparkles size={40} className="text-[var(--text-ghost)]" />}
          title="Select a Patient"
          message="Enter a patient ID above to access clinical decision support tools."
        />
      )}

      {/* Tabs + content */}
      {selectedPatientId && (
        <>
          <TabBar
            tabs={COPILOT_TABS}
            activeTab={activeTab}
            onTabChange={setActiveTab}
          />

          <TabPanel id="trials" active={activeTab === "trials"}>
            <TrialMatchResults
              trials={trialQuery.data?.trials}
              isLoading={trialQuery.isLoading}
              isError={trialQuery.isError}
            />
          </TabPanel>

          <TabPanel id="guidelines" active={activeTab === "guidelines"}>
            <GuidelineConcordance patientContext={patientContext} />
          </TabPanel>

          <TabPanel id="drugs" active={activeTab === "drugs"}>
            <DrugInteractionPanel currentMedications={currentMedications} />
          </TabPanel>

          <TabPanel id="genomics" active={activeTab === "genomics"}>
            <GenomicsPanel patientId={selectedPatientId} />
          </TabPanel>

          <TabPanel id="prognosis" active={activeTab === "prognosis"}>
            <PrognosisPanel patientContext={patientContext} />
          </TabPanel>
        </>
      )}
    </div>
  );
}
