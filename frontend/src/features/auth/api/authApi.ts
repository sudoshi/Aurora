import apiClient from "@/lib/api-client";

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post("/auth/login", { email, password }),

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
};
