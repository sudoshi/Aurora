import { Outlet } from "react-router-dom";
import { Sidebar } from "@/components/layout/Sidebar";
import { Header } from "@/components/layout/Header";
import { CommandPalette } from "@/components/layout/CommandPalette";
import { AbbyPanel } from "@/components/layout/AbbyPanel";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal";
import { useAuthStore } from "@/stores/authStore";

export default function DashboardLayout() {
  const user = useAuthStore((s) => s.user);
  return (
    <div className="app-shell">
      {user?.must_change_password && <ChangePasswordModal />}
      <Sidebar />
      <div className="app-content">
        <Header />
        <main className="content-main">
          <Outlet />
        </main>
      </div>
      <CommandPalette />
      <AbbyPanel />
    </div>
  );
}
