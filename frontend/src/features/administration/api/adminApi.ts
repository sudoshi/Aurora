import apiClient from "@/lib/api-client";

// ── Types ────────────────────────────────────────────────────────────────────

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  must_change_password: boolean;
  is_active: boolean;
  last_active_at: string | null;
  roles: Array<string | { id: number; name: string }>;
  permissions?: Array<{ id: number; name: string }>;
  created_at: string;
  updated_at: string;
}

export interface Role {
  id: number;
  name: string;
  guard_name: string;
  users_count?: number;
  permissions?: Array<{ id: number; name: string; guard_name: string }>;
  created_at: string;
  updated_at: string;
}

export interface AuthProviderSetting {
  provider_type: AuthProviderType;
  display_name: string;
  is_enabled: boolean;
  priority: number;
  settings: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export type AuthProviderType = "ldap" | "oauth2" | "saml2" | "oidc";

export interface AiProviderSetting {
  provider_type: string;
  display_name: string;
  model: string;
  is_enabled: boolean;
  is_active: boolean;
  settings: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export interface SystemHealth {
  status: string;
  services: SystemHealthService[];
  checked_at: string;
}

export interface SystemHealthService {
  key: string;
  name: string;
  status: "healthy" | "degraded" | "down";
  message: string;
  details?: Record<string, unknown>;
  checked_at: string;
}

// ── Users ─────────────────────────────────────────────────────────────────────

export interface PaginatedUsers {
  data: User[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface UserFilters {
  search?: string;
  role?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_dir?: "asc" | "desc";
}

export const fetchUsers = (filters: UserFilters = {}) =>
  apiClient
    .get<PaginatedUsers>("/admin/users", { params: filters })
    .then((r) => r.data);

export const fetchUser = (id: number) =>
  apiClient.get<User>(`/admin/users/${id}`).then((r) => r.data);

export const createUser = (data: {
  name: string;
  email: string;
  password: string;
  roles: string[];
}) => apiClient.post<User>("/admin/users", data).then((r) => r.data);

export const updateUser = (
  id: number,
  data: Partial<{ name: string; email: string; password: string; roles: string[] }>,
) => apiClient.put<User>(`/admin/users/${id}`, data).then((r) => r.data);

export const deleteUser = (id: number) =>
  apiClient.delete(`/admin/users/${id}`);

export const syncUserRoles = (id: number, roles: string[]) =>
  apiClient.put<User>(`/admin/users/${id}/roles`, { roles }).then((r) => r.data);

export const fetchAvailableRoles = () =>
  apiClient.get<Role[]>("/admin/users/roles").then((r) => r.data);

// ── Roles & Permissions ───────────────────────────────────────────────────────

export type PermissionsByDomain = Record<string, Array<{ id: number; name: string; guard_name: string }>>;

export const fetchRoles = () =>
  apiClient.get<Role[]>("/admin/roles").then((r) => r.data);

export const fetchRole = (id: number) =>
  apiClient.get<Role>(`/admin/roles/${id}`).then((r) => r.data);

export const fetchPermissions = () =>
  apiClient.get<PermissionsByDomain>("/admin/roles/permissions").then((r) => r.data);

export const createRole = (data: { name: string; permissions: string[] }) =>
  apiClient.post<Role>("/admin/roles", data).then((r) => r.data);

export const updateRole = (
  id: number,
  data: Partial<{ name: string; permissions: string[] }>,
) => apiClient.put<Role>(`/admin/roles/${id}`, data).then((r) => r.data);

export const deleteRole = (id: number) =>
  apiClient.delete(`/admin/roles/${id}`);

// ── Auth Providers ────────────────────────────────────────────────────────────

export interface TestResult {
  success: boolean;
  message: string;
  details?: Record<string, unknown>;
}

export const fetchAuthProviders = () =>
  apiClient.get<AuthProviderSetting[]>("/admin/auth-providers").then((r) => r.data);

export const fetchAuthProvider = (type: string) =>
  apiClient.get<AuthProviderSetting>(`/admin/auth-providers/${type}`).then((r) => r.data);

export const updateAuthProvider = (type: string, data: Partial<AuthProviderSetting>) =>
  apiClient.put<AuthProviderSetting>(`/admin/auth-providers/${type}`, data).then((r) => r.data);

export const enableAuthProvider = (type: string) =>
  apiClient.post<AuthProviderSetting>(`/admin/auth-providers/${type}/enable`).then((r) => r.data);

export const disableAuthProvider = (type: string) =>
  apiClient.post<AuthProviderSetting>(`/admin/auth-providers/${type}/disable`).then((r) => r.data);

export const testAuthProvider = (type: string) =>
  apiClient.post<TestResult>(`/admin/auth-providers/${type}/test`).then((r) => r.data);

// ── AI Providers ──────────────────────────────────────────────────────────────

export const fetchAiProviders = () =>
  apiClient.get<AiProviderSetting[]>("/admin/ai-providers").then((r) => r.data);

export const fetchAiProvider = (type: string) =>
  apiClient.get<AiProviderSetting>(`/admin/ai-providers/${type}`).then((r) => r.data);

export const updateAiProvider = (
  type: string,
  data: Partial<Pick<AiProviderSetting, "display_name" | "model"> & { settings: Record<string, string> }>,
) => apiClient.put<AiProviderSetting>(`/admin/ai-providers/${type}`, data).then((r) => r.data);

export const activateAiProvider = (type: string) =>
  apiClient.post<AiProviderSetting>(`/admin/ai-providers/${type}/activate`).then((r) => r.data);

export const enableAiProvider = (type: string) =>
  apiClient.post<AiProviderSetting>(`/admin/ai-providers/${type}/enable`).then((r) => r.data);

export const disableAiProvider = (type: string) =>
  apiClient.post<AiProviderSetting>(`/admin/ai-providers/${type}/disable`).then((r) => r.data);

export const testAiProvider = (type: string) =>
  apiClient.post<TestResult>(`/admin/ai-providers/${type}/test`).then((r) => r.data);

// ── System Health ─────────────────────────────────────────────────────────────

export const fetchSystemHealth = () =>
  apiClient.get<SystemHealth>("/admin/system-health").then((r) => r.data);

export interface ServiceLogEntry {
  timestamp: string;
  level: string;
  message: string;
}

export interface ServiceDetail {
  service: SystemHealthService;
  logs: ServiceLogEntry[];
  metrics: Record<string, unknown>;
  checked_at: string;
}

export const fetchServiceDetail = (key: string) =>
  apiClient.get<ServiceDetail>(`/admin/system-health/${key}`).then((r) => r.data);

// ── User Audit Log ─────────────────────────────────────────────────────────────

export interface UserAuditEntry {
  id: number;
  user_id: number | null;
  user_name: string | null;
  user_email: string | null;
  action: string;
  feature: string | null;
  ip_address: string | null;
  user_agent: string | null;
  metadata: Record<string, string> | null;
  occurred_at: string;
}

export interface PaginatedAuditLog {
  data: UserAuditEntry[];
  meta: { total: number; per_page: number; current_page: number; last_page: number };
}

export interface AuditSummary {
  logins_today: number;
  active_users_week: number;
  top_features: Array<{ feature: string; count: number }>;
  recent_logins: UserAuditEntry[];
}

export interface AuditFilters {
  user_id?: number;
  action?: string;
  feature?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}

export const fetchAuditLog = (filters: AuditFilters = {}) =>
  apiClient.get<PaginatedAuditLog>("/admin/user-audit", { params: filters }).then((r) => r.data);

export const fetchAuditSummary = () =>
  apiClient.get<AuditSummary>("/admin/user-audit/summary").then((r) => r.data);

export const fetchUserAuditLog = (userId: number, params?: { per_page?: number; page?: number }) =>
  apiClient.get<PaginatedAuditLog>(`/admin/users/${userId}/audit`, { params }).then((r) => r.data);
