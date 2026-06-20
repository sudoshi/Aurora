import axios from "axios";
import { useAuthStore } from "@/stores/authStore";

const apiClient = axios.create({
  baseURL: "/api",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: true,
});

const isJsdom = () =>
  typeof navigator !== "undefined" && /\bjsdom\b/i.test(navigator.userAgent);

const shouldRedirectOnUnauthorized = () =>
  typeof window !== "undefined" && !isJsdom();

apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      if (shouldRedirectOnUnauthorized()) {
        window.location.href = "/login";
      }
    }
    return Promise.reject(error as Error);
  },
);

export default apiClient;
