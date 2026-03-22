export type CaseSpecialty = "oncology" | "surgical" | "rare_disease" | "complex_medical";
export type CaseUrgency = "routine" | "urgent" | "emergent";
export type CaseStatus = "draft" | "active" | "in_review" | "closed" | "archived";
export type CaseType = "tumor_board" | "surgical_review" | "rare_disease" | "medical_complex";
export type TeamMemberRole = "presenter" | "reviewer" | "observer" | "coordinator";

export interface ClinicalCase {
  id: number;
  title: string;
  specialty: CaseSpecialty;
  urgency: CaseUrgency;
  status: CaseStatus;
  patient_id: number | null;
  case_type: CaseType;
  clinical_question: string | null;
  summary: string | null;
  created_by: number;
  scheduled_at: string | null;
  closed_at: string | null;
  created_at: string;
  updated_at: string;
  creator?: { id: number; name: string; email: string };
  team_members?: CaseTeamMember[];
  annotations_count?: number;
  discussions_count?: number;
  documents_count?: number;
  decisions_count?: number;
}

export interface CaseTeamMember {
  id: number;
  case_id: number;
  user_id: number;
  role: TeamMemberRole;
  invited_at: string;
  accepted_at: string | null;
  user?: { id: number; name: string; email: string; avatar: string | null };
}

export interface CaseAnnotation {
  id: number;
  case_id: number;
  user_id: number;
  domain: string;
  record_ref: string | null;
  content: string;
  anchored_to: Record<string, unknown> | null;
  created_at: string;
  user?: { id: number; name: string };
}

export interface CaseDiscussion {
  id: number;
  case_id: number;
  user_id: number;
  parent_id: number | null;
  content: string;
  created_at: string;
  user?: { id: number; name: string; avatar: string | null };
  replies?: CaseDiscussion[];
}

export interface CaseDocument {
  id: number;
  case_id: number;
  filename: string;
  filepath: string;
  mime_type: string;
  size: number;
  document_type: string;
  description: string | null;
  created_at: string;
  uploader?: { id: number; name: string };
}

export interface PaginatedCases {
  data: ClinicalCase[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface CaseFilters {
  status?: CaseStatus;
  specialty?: CaseSpecialty;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface CreateCaseData {
  title: string;
  specialty: CaseSpecialty;
  case_type: CaseType;
  urgency: CaseUrgency;
  clinical_question?: string;
  summary?: string;
  patient_id?: number;
}

export interface UpdateCaseData extends Partial<CreateCaseData> {
  status?: CaseStatus;
}

export interface CreateAnnotationData {
  domain: string;
  content: string;
  record_ref?: string;
  anchored_to?: Record<string, unknown>;
}
