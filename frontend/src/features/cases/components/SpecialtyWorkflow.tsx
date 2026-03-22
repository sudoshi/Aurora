import { useState, useEffect } from "react";
import {
  Layout,
  BookOpen,
  Users,
  CheckSquare,
  Loader2,
  AlertCircle,
} from "lucide-react";
import { cn } from "@/lib/utils";

// ── Types ───────────────────────────────────────────────────────────────────

interface CaseTemplate {
  id: number;
  name: string;
  slug: string;
  specialty: string;
  case_type: string;
  description: string;
  clinical_question_prompt: string;
  recommended_tabs: string[];
  decision_types: string[];
  guideline_sets: string[];
  default_team_roles: string[];
}

interface SpecialtyWorkflowProps {
  templateSlug: string;
  onTabSelect?: (tab: string) => void;
  onDecisionTypeSelect?: (type: string) => void;
  className?: string;
}

// ── Tab display config ──────────────────────────────────────────────────────

const TAB_LABELS: Record<string, string> = {
  imaging: "Imaging",
  imaging_3d: "3D Imaging",
  anatomy: "Anatomy",
  pathology: "Pathology",
  genomics: "Genomics",
  medications: "Medications",
  labs: "Labs",
  vitals: "Vitals",
  clinical_notes: "Clinical Notes",
  treatment_history: "Treatment History",
  clinical_trials: "Clinical Trials",
  biomarkers: "Biomarkers",
  phenotype: "Phenotype",
  family_history: "Family History",
  prior_workup: "Prior Workup",
  similar_patients: "Similar Patients",
  conditions: "Conditions",
  comorbidities: "Comorbidities",
  risk_scores: "Risk Scores",
  anesthesia_notes: "Anesthesia",
  social_history: "Social History",
  drug_interactions: "Drug Interactions",
};

const DECISION_LABELS: Record<string, string> = {
  treatment_recommendation: "Treatment Recommendation",
  staging_consensus: "Staging Consensus",
  surgical_candidacy: "Surgical Candidacy",
  radiation_planning: "Radiation Planning",
  systemic_therapy: "Systemic Therapy",
  clinical_trial_eligibility: "Clinical Trial Eligibility",
  variant_interpretation: "Variant Interpretation",
  targeted_therapy_recommendation: "Targeted Therapy",
  clinical_trial_matching: "Trial Matching",
  biomarker_assessment: "Biomarker Assessment",
  germline_referral: "Germline Referral",
  companion_diagnostic: "Companion Diagnostic",
  differential_diagnosis: "Differential Diagnosis",
  additional_testing: "Additional Testing",
  phenotype_matching: "Phenotype Matching",
  variant_reclassification: "Variant Reclassification",
  specialist_referral: "Specialist Referral",
  research_enrollment: "Research Enrollment",
  surgical_approach: "Surgical Approach",
  risk_assessment: "Risk Assessment",
  perioperative_planning: "Perioperative Planning",
  anesthesia_strategy: "Anesthesia Strategy",
  neoadjuvant_therapy: "Neoadjuvant Therapy",
  reconstruction_plan: "Reconstruction Plan",
  complication_contingency: "Complication Contingency",
  treatment_optimization: "Treatment Optimization",
  medication_reconciliation: "Medication Reconciliation",
  care_coordination: "Care Coordination",
  goals_of_care: "Goals of Care",
  risk_stratification: "Risk Stratification",
  discharge_planning: "Discharge Planning",
};

const ROLE_LABELS: Record<string, string> = {
  medical_oncologist: "Medical Oncologist",
  surgical_oncologist: "Surgical Oncologist",
  radiation_oncologist: "Radiation Oncologist",
  pathologist: "Pathologist",
  radiologist: "Radiologist",
  oncology_nurse: "Oncology Nurse",
  tumor_board_coordinator: "TB Coordinator",
  molecular_pathologist: "Molecular Pathologist",
  genetic_counselor: "Genetic Counselor",
  bioinformatician: "Bioinformatician",
  clinical_trial_coordinator: "Trial Coordinator",
  pharmacologist: "Pharmacologist",
  clinical_geneticist: "Clinical Geneticist",
  referring_specialist: "Referring Specialist",
  metabolic_specialist: "Metabolic Specialist",
  care_coordinator: "Care Coordinator",
  primary_surgeon: "Primary Surgeon",
  assisting_surgeon: "Assisting Surgeon",
  anesthesiologist: "Anesthesiologist",
  interventional_radiologist: "IR",
  surgical_nurse: "Surgical Nurse",
  patient_navigator: "Patient Navigator",
  internist: "Internist",
  hospitalist: "Hospitalist",
  clinical_pharmacist: "Clinical Pharmacist",
  social_worker: "Social Worker",
  consulting_specialist: "Consulting Specialist",
  primary_nurse: "Primary Nurse",
};

const SPECIALTY_ACCENT: Record<string, string> = {
  Oncology: "#E85A6B",
  "Genomic Medicine": "#A78BFA",
  "Rare Disease": "#F59E0B",
  Surgery: "#60A5FA",
  "Internal Medicine": "#2DD4BF",
};

// ── Component ───────────────────────────────────────────────────────────────

export default function SpecialtyWorkflow({
  templateSlug,
  onTabSelect,
  onDecisionTypeSelect,
  className,
}: SpecialtyWorkflowProps) {
  const [template, setTemplate] = useState<CaseTemplate | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function fetchTemplate() {
      setLoading(true);
      setError(null);

      try {
        const token = localStorage.getItem("auth_token");
        const response = await fetch("/api/case-templates", {
          headers: {
            Accept: "application/json",
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
        });

        if (!response.ok) {
          throw new Error(`Failed to fetch templates: ${response.status}`);
        }

        const json = await response.json();
        const templates: CaseTemplate[] = json.data ?? json;
        const found = templates.find(
          (t: CaseTemplate) => t.slug === templateSlug,
        );

        if (!cancelled) {
          if (found) {
            setTemplate(found);
            setActiveTab(found.recommended_tabs[0] ?? null);
          } else {
            setError(`Template "${templateSlug}" not found`);
          }
          setLoading(false);
        }
      } catch (err) {
        if (!cancelled) {
          setError(
            err instanceof Error ? err.message : "Failed to load template",
          );
          setLoading(false);
        }
      }
    }

    fetchTemplate();
    return () => {
      cancelled = true;
    };
  }, [templateSlug]);

  if (loading) {
    return (
      <div className={cn("flex items-center justify-center p-8", className)}>
        <Loader2 className="h-5 w-5 animate-spin text-[var(--color-text-secondary)]" />
        <span className="ml-2 text-sm text-[var(--color-text-secondary)]">
          Loading workflow template...
        </span>
      </div>
    );
  }

  if (error || !template) {
    return (
      <div
        className={cn(
          "flex items-center gap-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface-elevated)] p-4",
          className,
        )}
      >
        <AlertCircle className="h-4 w-4 text-[#E85A6B]" />
        <span className="text-sm text-[var(--color-text-secondary)]">
          {error ?? "Template not found"}
        </span>
      </div>
    );
  }

  const accent = SPECIALTY_ACCENT[template.specialty] ?? "#2DD4BF";

  function handleTabClick(tab: string) {
    setActiveTab(tab);
    onTabSelect?.(tab);
  }

  function handleDecisionClick(type: string) {
    onDecisionTypeSelect?.(type);
  }

  return (
    <div
      className={cn(
        "rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] overflow-hidden",
        className,
      )}
    >
      {/* Header */}
      <div
        className="px-5 py-4 border-b border-[var(--color-border)]"
        style={{ borderLeftWidth: 3, borderLeftColor: accent }}
      >
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-base font-semibold text-[var(--color-text-primary)]">
              {template.name}
            </h3>
            <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">
              {template.specialty} &middot; {template.case_type.replace(/_/g, " ")}
            </p>
          </div>
          <span
            className="rounded-full px-2.5 py-0.5 text-xs font-medium"
            style={{ backgroundColor: `${accent}15`, color: accent }}
          >
            {template.specialty}
          </span>
        </div>
        <p className="mt-2 text-sm text-[var(--color-text-secondary)] leading-relaxed">
          {template.description}
        </p>
      </div>

      {/* Clinical Question */}
      <div className="px-5 py-3 border-b border-[var(--color-border)] bg-[var(--color-surface-elevated)]">
        <p className="text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider mb-1">
          Clinical Question
        </p>
        <p className="text-sm text-[var(--color-text-primary)] italic">
          &ldquo;{template.clinical_question_prompt}&rdquo;
        </p>
      </div>

      {/* Recommended Data Tabs */}
      <div className="px-5 py-4 border-b border-[var(--color-border)]">
        <div className="flex items-center gap-1.5 mb-3">
          <Layout className="h-3.5 w-3.5 text-[var(--color-text-secondary)]" />
          <span className="text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">
            Recommended Data Tabs
          </span>
        </div>
        <div className="flex flex-wrap gap-1.5">
          {template.recommended_tabs.map((tab) => {
            const isActive = activeTab === tab;
            return (
              <button
                key={tab}
                onClick={() => handleTabClick(tab)}
                className={cn(
                  "rounded-md px-2.5 py-1 text-xs font-medium transition-colors cursor-pointer",
                  isActive
                    ? "text-white"
                    : "bg-[var(--color-surface-elevated)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]",
                )}
                style={
                  isActive
                    ? { backgroundColor: accent }
                    : undefined
                }
              >
                {TAB_LABELS[tab] ?? tab.replace(/_/g, " ")}
              </button>
            );
          })}
        </div>
      </div>

      {/* Decision Types */}
      <div className="px-5 py-4 border-b border-[var(--color-border)]">
        <div className="flex items-center gap-1.5 mb-3">
          <CheckSquare className="h-3.5 w-3.5 text-[var(--color-text-secondary)]" />
          <span className="text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">
            Decision Types
          </span>
        </div>
        <div className="flex flex-wrap gap-1.5">
          {template.decision_types.map((dt) => (
            <button
              key={dt}
              onClick={() => handleDecisionClick(dt)}
              className="rounded-md bg-[var(--color-surface-elevated)] px-2.5 py-1 text-xs font-medium text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors cursor-pointer"
            >
              {DECISION_LABELS[dt] ?? dt.replace(/_/g, " ")}
            </button>
          ))}
        </div>
      </div>

      {/* Guidelines */}
      <div className="px-5 py-4 border-b border-[var(--color-border)]">
        <div className="flex items-center gap-1.5 mb-3">
          <BookOpen className="h-3.5 w-3.5 text-[var(--color-text-secondary)]" />
          <span className="text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">
            Applicable Guidelines
          </span>
        </div>
        <div className="flex flex-wrap gap-1.5">
          {template.guideline_sets.map((g) => (
            <span
              key={g}
              className="rounded-md bg-[var(--color-surface-elevated)] px-2.5 py-1 text-xs font-medium text-[var(--color-text-secondary)]"
            >
              {g.replace(/_/g, " ")}
            </span>
          ))}
        </div>
      </div>

      {/* Team Roles */}
      <div className="px-5 py-4">
        <div className="flex items-center gap-1.5 mb-3">
          <Users className="h-3.5 w-3.5 text-[var(--color-text-secondary)]" />
          <span className="text-xs font-medium text-[var(--color-text-secondary)] uppercase tracking-wider">
            Suggested Team Composition
          </span>
        </div>
        <div className="flex flex-wrap gap-1.5">
          {template.default_team_roles.map((role) => (
            <span
              key={role}
              className="rounded-md border border-[var(--color-border)] px-2.5 py-1 text-xs text-[var(--color-text-secondary)]"
            >
              {ROLE_LABELS[role] ?? role.replace(/_/g, " ")}
            </span>
          ))}
        </div>
      </div>
    </div>
  );
}
