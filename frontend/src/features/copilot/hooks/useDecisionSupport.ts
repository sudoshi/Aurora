import { useQuery, useMutation } from "@tanstack/react-query";
import {
  matchTrials,
  checkGuidelines,
  checkDrugInteractions,
  interpretVariant,
  calculatePrognosticScores,
} from "../api/decisionSupportApi";
import type { PatientContext } from "../types/decision-support";

// ---------------------------------------------------------------------------
// Trial matching
// ---------------------------------------------------------------------------

export function useTrialMatch(patientId: number | null, conditionFocus?: string) {
  return useQuery({
    queryKey: ["trial-match", patientId, conditionFocus],
    queryFn: () => matchTrials(patientId!, conditionFocus),
    enabled: patientId != null,
    staleTime: 10 * 60_000,
  });
}

// ---------------------------------------------------------------------------
// Guideline concordance (mutation — on demand)
// ---------------------------------------------------------------------------

export function useGuidelineCheck() {
  return useMutation({
    mutationFn: ({
      recommendation,
      patientContext,
    }: {
      recommendation: string;
      patientContext: PatientContext;
    }) => checkGuidelines(recommendation, patientContext),
  });
}

// ---------------------------------------------------------------------------
// Drug interactions
// ---------------------------------------------------------------------------

export function useDrugInteractions(
  medications: string[],
  proposedMedication?: string,
  enabled = true,
) {
  return useQuery({
    queryKey: ["drug-interactions", medications, proposedMedication],
    queryFn: () => checkDrugInteractions(medications, proposedMedication),
    enabled: enabled && medications.length > 0,
    staleTime: 10 * 60_000,
  });
}

export function useDrugInteractionCheck() {
  return useMutation({
    mutationFn: ({
      medications,
      proposedMedication,
    }: {
      medications: string[];
      proposedMedication?: string;
    }) => checkDrugInteractions(medications, proposedMedication),
  });
}

// ---------------------------------------------------------------------------
// Variant interpretation (mutation — on demand)
// ---------------------------------------------------------------------------

export function useVariantInterpretation() {
  return useMutation({
    mutationFn: ({
      gene,
      variant,
      cancerType,
    }: {
      gene: string;
      variant: string;
      cancerType?: string;
    }) => interpretVariant(gene, variant, cancerType),
  });
}

// ---------------------------------------------------------------------------
// Prognostic scores
// ---------------------------------------------------------------------------

export function usePrognosticScores(patientData: PatientContext | null) {
  return useQuery({
    queryKey: ["prognostic-scores", patientData?.patient_id],
    queryFn: () => calculatePrognosticScores(patientData!),
    enabled: patientData != null,
    staleTime: 10 * 60_000,
  });
}
