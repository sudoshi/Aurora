export interface AbbyAnalyzeRequest {
  prompt: string;
  case_id?: number;
}

export interface AbbyAnalyzeResponse {
  expression: Record<string, unknown>;
  explanation: string;
  clinical_findings: ClinicalFinding[];
  warnings: string[];
}

export interface ClinicalFinding {
  name: string;
  details: {
    finding_id: number;
    finding_name: string;
    category: string;
    significance: string;
  }[];
}

export interface AbbySuggestRequest {
  category: string;
  description: string;
}

export interface AbbySuggestResponse {
  suggestions: {
    finding_id: number;
    finding_name: string;
    category: string;
    source: string;
    score: number;
  }[];
}

export interface AbbyExplainResponse {
  explanation: string;
}

export interface AbbyRefineRequest {
  expression: Record<string, unknown>;
  prompt: string;
}
