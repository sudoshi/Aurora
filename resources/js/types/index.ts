export interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  is_active?: boolean;
  must_change_password?: boolean;
  roles?: string[];
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  loading: boolean;
}

export interface Event {
  id: number;
  title: string;
  description?: string;
  start_time: string;
  end_time: string;
  category?: string;
  location?: string;
  team_members?: User[];
  patients?: Patient[];
}

export interface Patient {
  id: number;
  name: string;
  mrn?: string;
  diagnosis?: string;
}

export interface ClinicalCase {
  id: number;
  title: string;
  description?: string;
  patient_id?: number;
  status?: string;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  current_page: number;
  per_page: number;
}
