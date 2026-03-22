import { Outlet } from "react-router-dom";
import { Header } from "@/components/layout/Header";
import { TopNav } from "@/components/layout/TopNav";
import { SectionSidebar } from "@/components/layout/SectionSidebar";
import { CommandPalette } from "@/components/layout/CommandPalette";
import { AbbyPanel } from "@/components/layout/AbbyPanel";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal";
import { useAuthStore } from "@/stores/authStore";

export default function DashboardLayout() {
  const user = useAuthStore((s) => s.user);

  return (
    <div className="app-shell">
      {user?.must_change_password && <ChangePasswordModal />}
      <Header />
      <TopNav />
      <div className="app-body">
        <SectionSidebar />
        <div className="app-content">
          <main className="content-main">
            <Outlet />
          </main>
        </div>
      </div>
      <CommandPalette />
      <AbbyPanel />
    </div>
  );
}
