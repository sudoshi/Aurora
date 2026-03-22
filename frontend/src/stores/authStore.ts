import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  phone_number: string | null;
  job_title: string | null;
  department: string | null;
  organization: string | null;
  bio: string | null;
  must_change_password: boolean;
  is_active: boolean;
  last_login_at: string | null;
  roles: string[];
  permissions: string[];
  created_at: string;
  updated_at: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  setAuth: (token: string, user: User) => void;
  updateUser: (user: Partial<User>) => void;
  logout: () => void;
  hasRole: (role: string) => boolean;
  hasPermission: (permission: string) => boolean;
  isAdmin: () => boolean;
  isSuperAdmin: () => boolean;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      isAuthenticated: false,

      setAuth: (token, user) => set({ token, user, isAuthenticated: true }),

      updateUser: (partial) => {
        const current = get().user;
        if (!current) return;
        set({ user: { ...current, ...partial } });
      },

      logout: () => set({ token: null, user: null, isAuthenticated: false }),

      hasRole: (role) => {
        const roles = get().user?.roles ?? [];
        return roles.includes(role);
      },

      hasPermission: (permission) => {
        const permissions = get().user?.permissions ?? [];
        return permissions.includes(permission);
      },

      isAdmin: () =>
        ["super-admin", "admin"].some((r) =>
          (get().user?.roles ?? []).includes(r),
        ),

      isSuperAdmin: () =>
        (get().user?.roles ?? []).includes("super-admin"),
    }),
    { name: "aurora-auth" },
  ),
);
