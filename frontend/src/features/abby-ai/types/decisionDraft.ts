export interface DraftSource {
  type: string;
  id: string;
  title: string;
  url: string;
}

export interface DecisionDraft {
  decision_type: string;
  recommendation: string;
  rationale: string;
  confidence: number;
  guideline_references: string[];
  sources: DraftSource[];
  model: string;
  evidence_counts: { articles: number; trials: number; variants: number };
}
