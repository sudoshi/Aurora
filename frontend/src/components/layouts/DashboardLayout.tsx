import { Outlet } from "react-router-dom";
import { Header } from "@/components/layout/Header";
import { CommandPalette } from "@/components/layout/CommandPalette";
import { AbbyPanel } from "@/components/layout/AbbyPanel";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal";
import { useNotificationListener } from "@/features/commons/hooks/useNotificationListener";
import { useRealtimeConnection } from "@/lib/useRealtime";
import { useAuthStore } from "@/stores/authStore";

export default function DashboardLayout() {
  const user = useAuthStore((s) => s.user);

  // Bring the realtime connection up for the whole authenticated session and
  // listen for the current user's notifications app-wide.
  const realtimeStatus = useRealtimeConnection();
  useNotificationListener();

  const degraded =
    realtimeStatus === "disconnected" || realtimeStatus === "unavailable";

  return (
    <div className="app-shell">
      {user?.must_change_password && <ChangePasswordModal />}
      <Header />
      <div className="app-body">
        <div className="app-content">
          <main className="content-main">
            <Outlet />
          </main>
        </div>
      </div>
      {degraded && (
        <div
          role="status"
          className="fixed bottom-3 right-3 z-50 rounded-full bg-amber-500/15 px-3 py-1 text-xs font-medium text-amber-300 ring-1 ring-amber-500/30"
        >
          Reconnecting… live updates paused
        </div>
      )}
      <CommandPalette />
      <AbbyPanel />
    </div>
  );
}
