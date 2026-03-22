import { useState } from "react";
import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";
import { useUiStore } from "@/stores/uiStore";
import { useAuthStore } from "@/stores/authStore";
import {
  LayoutDashboard,
  Users,
  MessageSquare,
  Shield,
  Settings,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  UsersRound,
  ShieldCheck,
  ScrollText,
  Activity,
  Bell,
  Briefcase,
  Calendar,
  CheckCircle2,
  Cpu,
  type LucideIcon,
} from "lucide-react";

interface NavChild {
  path: string;
  label: string;
  icon: LucideIcon;
  superAdminOnly?: boolean;
}

interface NavItem {
  path: string;
  label: string;
  icon: LucideIcon;
  adminOnly?: boolean;
  superAdminOnly?: boolean;
  children?: NavChild[];
}

const navItems: NavItem[] = [
  { path: "/", label: "Dashboard", icon: LayoutDashboard },
  { path: "/cases", label: "Cases", icon: Briefcase },
  { path: "/sessions", label: "Sessions", icon: Calendar },
  { path: "/profiles", label: "Patient Profiles", icon: Users },
  { path: "/decisions", label: "Decisions", icon: CheckCircle2 },
  { path: "/commons", label: "Commons", icon: MessageSquare },
  {
    path: "/admin",
    label: "Admin",
    icon: Shield,
    adminOnly: true,
    children: [
      { path: "/admin", label: "Admin Dashboard", icon: Settings },
      { path: "/admin/system-health", label: "System Health", icon: Activity },
      { path: "/admin/users", label: "Users", icon: UsersRound },
      { path: "/admin/user-audit", label: "Audit Log", icon: ScrollText },
      { path: "/admin/roles", label: "Roles & Permissions", icon: ShieldCheck, superAdminOnly: true },
      { path: "/admin/ai-providers", label: "AI Providers", icon: Cpu },
      { path: "/admin/notifications", label: "Notifications", icon: Bell },
    ],
  },
  { path: "/settings", label: "Settings", icon: Settings },
];

export function Sidebar() {
  const location = useLocation();
  const { sidebarOpen, toggleSidebar } = useUiStore();
  const { isAdmin, isSuperAdmin } = useAuthStore();
  const [manualOpen, setManualOpen] = useState<Set<string>>(new Set());
  const [manualClosed, setManualClosed] = useState<Set<string>>(new Set());

  const isActive = (path: string) =>
    path === "/" ? location.pathname === "/" : location.pathname.startsWith(path);

  const isGroupActive = (item: NavItem) => {
    if (!item.children) return isActive(item.path);
    return item.children.some((child) => isActive(child.path));
  };

  const toggleGroup = (path: string) => {
    const groupActive = navItems.find((i) => i.path === path)?.children?.some((c) => isActive(c.path)) ?? false;

    if (groupActive) {
      setManualClosed((prev) => {
        const next = new Set(prev);
        if (next.has(path)) next.delete(path);
        else next.add(path);
        return next;
      });
    } else {
      setManualOpen((prev) => {
        const next = new Set(prev);
        if (next.has(path)) next.delete(path);
        else next.add(path);
        return next;
      });
    }
  };

  const isExpanded = (item: NavItem) => {
    if (manualClosed.has(item.path)) return false;
    return manualOpen.has(item.path) || isGroupActive(item);
  };

  const visibleItems = navItems.filter((item) => {
    if (item.superAdminOnly) return isSuperAdmin();
    if (item.adminOnly) return isAdmin();
    return true;
  });

  return (
    <aside className={cn("app-sidebar", !sidebarOpen && "collapsed")}>
      {/* Header */}
      <div className="sidebar-header">
        <img src="/aurora_icon.png" alt="Aurora" className={cn("shrink-0", sidebarOpen ? "w-8 h-8" : "w-6 h-6")} />
        {sidebarOpen && <span className="sidebar-logo">Aurora</span>}
        <button
          onClick={toggleSidebar}
          className="sidebar-toggle"
          aria-label={sidebarOpen ? "Collapse sidebar" : "Expand sidebar"}
        >
          {sidebarOpen ? <ChevronLeft size={18} /> : <ChevronRight size={18} />}
        </button>
      </div>

      {/* Navigation */}
      <nav className="sidebar-nav" style={{ flex: 1 }}>
        {visibleItems.map((item) => {
          const hasChildren = item.children && item.children.length > 0;
          const groupActive = isGroupActive(item);
          const expanded = hasChildren && isExpanded(item);

          if (!hasChildren) {
            return (
              <div key={item.path}>
                <Link
                  to={item.path}
                  className={cn("nav-item", isActive(item.path) && "active")}
                  title={!sidebarOpen ? item.label : undefined}
                >
                  <item.icon size={18} className="nav-icon" />
                  {sidebarOpen && <span className="nav-label">{item.label}</span>}
                </Link>
              </div>
            );
          }

          return (
            <div key={item.path}>
              <button
                type="button"
                onClick={() => toggleGroup(item.path)}
                className={cn("nav-item", groupActive && "active")}
                title={!sidebarOpen ? item.label : undefined}
              >
                <item.icon size={18} className="nav-icon" />
                {sidebarOpen && (
                  <>
                    <span className="nav-label">{item.label}</span>
                    <ChevronDown
                      size={14}
                      className={cn(
                        "ml-auto shrink-0 transition-transform duration-200",
                        expanded && "rotate-180",
                      )}
                    />
                  </>
                )}
              </button>

              {sidebarOpen && expanded && (
                <div>
                  {item.children!
                    .filter((child) => !child.superAdminOnly || isSuperAdmin())
                    .map((child) => (
                      <Link
                        key={child.path}
                        to={child.path}
                        className={cn(
                          "nav-sub-item",
                          location.pathname === child.path && "active",
                        )}
                      >
                        <child.icon size={14} />
                        {child.label}
                      </Link>
                    ))}
                </div>
              )}
            </div>
          );
        })}
      </nav>

      {/* Acumenus branding */}
      <div
        style={{
          padding: sidebarOpen ? "var(--space-4) var(--space-5)" : "var(--space-4) 0",
          borderTop: "1px solid var(--border-subtle)",
          textAlign: "center",
        }}
      >
        <a
          href="https://www.acumenus.io"
          target="_blank"
          rel="noopener noreferrer"
          style={{
            fontFamily: "var(--font-body)",
            fontSize: sidebarOpen ? "var(--text-xs)" : "9px",
            color: "var(--text-ghost)",
            textDecoration: "none",
            letterSpacing: "0.3px",
            transition: "color 200ms",
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.color = "var(--text-muted)";
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.color = "var(--text-ghost)";
          }}
        >
          {sidebarOpen ? "Acumenus Data Sciences" : "ADS"}
        </a>
      </div>
    </aside>
  );
}
