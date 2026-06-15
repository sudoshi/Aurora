import apiClient from "@/lib/api-client";
import type { User } from "@/stores/authStore";

export interface AuthResponse {
  token?: string;
  access_token?: string;
  user: User;
}

export interface AuthProviderDiscovery {
  oidc_enabled: boolean;
  oidc_label: string;
  oidc_redirect_path: string;
}

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<AuthResponse>("/auth/login", { email, password }),

  register: (name: string, email: string, phone?: string) =>
    apiClient.post("/auth/register", { name, email, phone }),

  changePassword: (
    currentPassword: string,
    password: string,
    passwordConfirmation: string,
  ) =>
    apiClient.post("/auth/change-password", {
      current_password: currentPassword,
      password,
      password_confirmation: passwordConfirmation,
    }),

  logout: () => apiClient.post("/auth/logout"),

  getUser: () => apiClient.get("/auth/user"),

  getProviders: () =>
    apiClient.get<AuthProviderDiscovery>("/auth/providers"),

  exchangeOidcCode: (code: string) =>
    apiClient.post<AuthResponse>("/auth/oidc/exchange", { code }),
};
