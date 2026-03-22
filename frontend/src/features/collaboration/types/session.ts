export type SessionStatus = "scheduled" | "live" | "completed" | "cancelled";
export type SessionType = "tumor_board" | "mdc" | "surgical_planning" | "grand_rounds" | "ad_hoc";
export type ParticipantRole = "moderator" | "presenter" | "reviewer" | "observer";

export interface Session {
  id: number;
  title: string;
  description: string | null;
  scheduled_at: string;
  duration_minutes: number;
  status: SessionStatus;
  session_type: SessionType;
  created_by: number;
  started_at: string | null;
  ended_at: string | null;
  notes: string | null;
  created_at: string;
  creator?: { id: number; name: string };
  session_cases?: SessionCase[];
  participants?: SessionParticipant[];
}

export interface SessionCase {
  id: number;
  session_id: number;
  case_id: number;
  order: number;
  presenter_id: number | null;
  time_allotted_minutes: number;
  status: string;
  clinical_case?: { id: number; title: string; specialty: string; urgency: string };
  presenter?: { id: number; name: string };
}

export interface SessionParticipant {
  id: number;
  session_id: number;
  user_id: number;
  role: ParticipantRole;
  joined_at: string | null;
  left_at: string | null;
  user?: { id: number; name: string; avatar: string | null };
}

export interface PaginatedSessions {
  data: Session[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface SessionFilters {
  status?: SessionStatus;
  session_type?: SessionType;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface CreateSessionData {
  title: string;
  description?: string;
  session_type: SessionType;
  scheduled_at: string;
  duration_minutes: number;
}

export interface UpdateSessionData extends Partial<CreateSessionData> {
  status?: SessionStatus;
  notes?: string;
}

export interface AddSessionCaseData {
  case_id: number;
  order: number;
  presenter_id?: number;
  time_allotted_minutes: number;
}

export interface AddParticipantData {
  user_id: number;
  role: ParticipantRole;
}
