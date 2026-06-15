import { Navigate } from "react-router-dom";
import { useAuthStore } from "@/stores/authStore";

interface RequireSuperAdminProps {
  children: React.ReactNode;
}

/**
 * Route guard for super-admin-only surfaces (e.g. Authentication Providers).
 * Assumes the parent route already enforced authentication; this only adds the
 * super-admin authorization check, redirecting everyone else to the dashboard.
 * The backend independently enforces `role:super-admin`, so this is UX only.
 */
export default function RequireSuperAdmin({ children }: RequireSuperAdminProps) {
  const isSuperAdmin = useAuthStore((s) => s.isSuperAdmin());

  if (!isSuperAdmin) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}
