export type DecisionStatus = "proposed" | "under_review" | "approved" | "rejected" | "deferred";
export type DecisionType = "treatment_recommendation" | "diagnostic_workup" | "referral" | "monitoring_plan" | "palliative" | "other";
export type VoteType = "agree" | "disagree" | "abstain";

export interface Decision {
  id: number;
  case_id: number;
  session_id: number | null;
  proposed_by: number;
  decision_type: DecisionType;
  recommendation: string;
  rationale: string | null;
  guideline_reference: string | null;
  status: DecisionStatus;
  finalized_at: string | null;
  urgency: string;
  created_at: string;
  proposer?: { id: number; name: string };
  votes?: DecisionVote[];
  follow_ups?: FollowUp[];
  votes_summary?: { agree: number; disagree: number; abstain: number };
}

export interface DecisionVote {
  id: number;
  decision_id: number;
  user_id: number;
  vote: VoteType;
  comment: string | null;
  created_at: string;
  user?: { id: number; name: string };
}

export interface FollowUp {
  id: number;
  decision_id: number;
  assigned_to: number | null;
  title: string;
  description: string | null;
  due_date: string | null;
  status: string;
  completed_at: string | null;
  assignee?: { id: number; name: string };
}

export interface PaginatedDecisions {
  data: Decision[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface DecisionFilters {
  case_id?: number;
  status?: DecisionStatus;
  decision_type?: DecisionType;
  page?: number;
  per_page?: number;
}

export interface CreateDecisionData {
  case_id: number;
  session_id?: number;
  decision_type: DecisionType;
  recommendation: string;
  rationale?: string;
  guideline_reference?: string;
  urgency?: string;
}

export interface CastVoteData {
  vote: VoteType;
  comment?: string;
}

export interface CreateFollowUpData {
  title: string;
  description?: string;
  assigned_to?: number;
  due_date?: string;
}
