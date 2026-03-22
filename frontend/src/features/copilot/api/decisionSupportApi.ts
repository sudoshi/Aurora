import apiClient from "@/lib/api-client";
import type {
  TrialMatchResponse,
  ConcordanceResult,
  DrugInteractionResponse,
  VariantInterpretation,
  PrognosticResponse,
  PatientContext,
} from "../types/decision-support";

// ---------------------------------------------------------------------------
// Trial matching
// ---------------------------------------------------------------------------

export async function matchTrials(
  patientId: number,
  conditionFocus?: string,
): Promise<TrialMatchResponse> {
  const { data } = await apiClient.post("/ai/decision-support/trials", {
    patient_id: patientId,
    condition_focus: conditionFocus ?? null,
  });
  return data.data ?? data;
}

// ---------------------------------------------------------------------------
// Guideline concordance
// ---------------------------------------------------------------------------

export async function checkGuidelines(
  recommendation: string,
  patientContext: PatientContext,
): Promise<ConcordanceResult> {
  const { data } = await apiClient.post("/ai/decision-support/guidelines", {
    recommendation,
    patient_context: patientContext,
  });
  return data.data ?? data;
}

// ---------------------------------------------------------------------------
// Drug interactions
// ---------------------------------------------------------------------------

export async function checkDrugInteractions(
  medications: string[],
  proposedMedication?: string,
): Promise<DrugInteractionResponse> {
  const { data } = await apiClient.post("/ai/decision-support/drug-interactions", {
    medications,
    proposed_medication: proposedMedication ?? null,
  });
  return data.data ?? data;
}

// ---------------------------------------------------------------------------
// Genomic variant interpretation
// ---------------------------------------------------------------------------

export async function interpretVariant(
  gene: string,
  variant: string,
  cancerType?: string,
): Promise<VariantInterpretation> {
  const { data } = await apiClient.post("/ai/decision-support/variant", {
    gene,
    variant,
    cancer_type: cancerType ?? null,
  });
  return data.data ?? data;
}

// ---------------------------------------------------------------------------
// Prognostic scores
// ---------------------------------------------------------------------------

export async function calculatePrognosticScores(
  patientData: PatientContext,
): Promise<PrognosticResponse> {
  const { data } = await apiClient.post("/ai/decision-support/prognosis", {
    patient_context: patientData,
  });
  return data.data ?? data;
}
