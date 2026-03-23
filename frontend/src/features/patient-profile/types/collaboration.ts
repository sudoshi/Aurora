export type ClinicalDomain =
  | 'condition'
  | 'medication'
  | 'procedure'
  | 'measurement'
  | 'observation'
  | 'genomic'
  | 'imaging'
  | 'general';

export type FlagSeverity = 'critical' | 'attention' | 'informational';
export type TaskPriority = 'low' | 'normal' | 'high' | 'urgent';
export type TaskStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';

export interface UserRef {
  id: number;
  name: string;
  avatar?: string;
}

export interface PatientFlag {
  id: number;
  patient_id: number;
  flagged_by: number;
  flagger?: UserRef;
  domain: ClinicalDomain;
  record_ref: string;
  severity: FlagSeverity;
  title: string;
  description: string | null;
  resolved_at: string | null;
  resolved_by: number | null;
  resolver?: UserRef | null;
  created_at: string;
  updated_at: string;
}

export interface PatientTask {
  id: number;
  patient_id: number;
  created_by: number;
  creator?: UserRef;
  assigned_to: number | null;
  assignee?: UserRef | null;
  domain: ClinicalDomain | null;
  record_ref: string | null;
  title: string;
  description: string | null;
  due_date: string | null;
  priority: TaskPriority;
  status: TaskStatus;
  completed_at: string | null;
  completed_by: number | null;
  created_at: string;
  updated_at: string;
}

export interface FollowUp {
  id: number;
  decision_id: number;
  decision?: { id: number; recommendation: string };
  assigned_to: number | null;
  assignee?: UserRef | null;
  title: string;
  description: string | null;
  due_date: string | null;
  status: TaskStatus;
  completed_at: string | null;
  patient_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface DecisionVote {
  id: number;
  decision_id: number;
  user_id: number;
  vote: 'agree' | 'disagree' | 'abstain';
}

export interface PatientDecision {
  id: number;
  case_id: number;
  clinical_case?: { id: number; title: string };
  session_id: number | null;
  session?: { id: number; title: string } | null;
  proposed_by: number;
  proposer?: UserRef;
  patient_id: number | null;
  decision_type: string;
  recommendation: string;
  rationale: string | null;
  status: string;
  urgency: string;
  votes?: DecisionVote[];
  follow_ups?: FollowUp[];
  record_refs: string[] | null;
  finalized_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface AnchoredDiscussion {
  id: number;
  case_id: number;
  user_id: number;
  user?: UserRef;
  parent_id: number | null;
  content: string;
  domain: ClinicalDomain | null;
  record_ref: string | null;
  patient_id: number | null;
  created_at: string;
  replies?: AnchoredDiscussion[];
}

export interface CollaborationData {
  discussions: AnchoredDiscussion[];
  tasks: PatientTask[];
  follow_ups: FollowUp[];
  flags: PatientFlag[];
  decisions: PatientDecision[];
}

export const VIEW_TAB_TO_DOMAIN: Record<string, ClinicalDomain | undefined> = {
  briefing: undefined,
  timeline: undefined,
  labs: 'measurement',
  imaging: 'imaging',
  genomics: 'genomic',
  notes: undefined,
  visits: undefined,
  similar: undefined,
};
