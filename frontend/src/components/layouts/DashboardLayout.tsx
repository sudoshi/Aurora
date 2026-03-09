import { Outlet, useNavigate } from "react-router-dom";
import { useAuthStore } from "@/stores/authStore";
import { authApi } from "@/features/auth/api/authApi";
import TopNavigation from "@/components/navigation/TopNavigation";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal";

export default function DashboardLayout() {
  const navigate = useNavigate();
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  const handleLogout = async () => {
    try {
      await authApi.logout();
    } catch {
      // Even if logout API fails, clear local state
    }
    logout();
    navigate("/login");
  };

  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        flexDirection: "column",
        background: "var(--surface-base)",
      }}
    >
      <TopNavigation onLogout={handleLogout} />

      <div style={{ display: "flex", flex: 1 }}>
        {/* Sidebar */}
        <aside
          style={{
            width: "var(--sidebar-width)",
            background: "var(--sidebar-bg)",
            borderRight: "1px solid var(--border-default)",
            padding: "var(--space-4)",
            flexShrink: 0,
          }}
        >
          <nav>
            <p
              style={{
                fontSize: "var(--text-xs)",
                textTransform: "uppercase",
                letterSpacing: "0.8px",
                color: "var(--text-ghost)",
                marginBottom: "var(--space-3)",
              }}
            >
              Navigation
            </p>
            <ul style={{ listStyle: "none", padding: 0, margin: 0 }}>
              <li>
                <span
                  style={{
                    display: "block",
                    padding: "var(--space-2) var(--space-3)",
                    borderRadius: "var(--radius-sm)",
                    color: "var(--text-secondary)",
                    fontSize: "var(--text-sm)",
                  }}
                >
                  Dashboard
                </span>
              </li>
            </ul>
          </nav>
        </aside>

        {/* Main content */}
        <main
          style={{
            flex: 1,
            padding: "var(--content-padding)",
            maxWidth: "var(--content-max-width)",
          }}
        >
          <Outlet />
        </main>
      </div>

      {user?.must_change_password && <ChangePasswordModal />}
    </div>
  );
}
