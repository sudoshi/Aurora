import { useState, useRef, useEffect, useCallback } from "react";
import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/stores/authStore";
import {
  LayoutDashboard,
  Users,
  MessageSquare,
  Shield,
  Settings,
  UsersRound,
  ShieldCheck,
  ScrollText,
  Activity,
  Bell,
  Briefcase,
  Calendar,
  CheckCircle2,
  Cpu,
  ScanLine,
  Dna,
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
  { path: "/imaging", label: "Imaging", icon: ScanLine },
  { path: "/genomics", label: "Genomics", icon: Dna },
  { path: "/copilot", label: "AI Copilot", icon: Cpu },
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
  const { isAdmin, isSuperAdmin } = useAuthStore();
  const [activeGroup, setActiveGroup] = useState<string | null>(null);
  const railRef = useRef<HTMLElement>(null);
  const flyoutRefs = useRef<Map<string, HTMLDivElement>>(new Map());
  const railItemRefs = useRef<Map<string, HTMLButtonElement>>(new Map());

  const isActive = (path: string) =>
    path === "/" ? location.pathname === "/" : location.pathname.startsWith(path);

  // Close flyout on route change
  useEffect(() => {
    setActiveGroup(null);
  }, [location.pathname]);

  // Close flyout on click outside
  useEffect(() => {
    if (!activeGroup) return;

    const handleClickOutside = (e: MouseEvent) => {
      const rail = railRef.current;
      if (!rail) return;
      const target = e.target as Node;
      if (!rail.contains(target)) {
        setActiveGroup(null);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [activeGroup]);

  // Close flyout on Escape key, return focus to rail icon
  useEffect(() => {
    if (!activeGroup) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        const btn = railItemRefs.current.get(activeGroup);
        setActiveGroup(null);
        btn?.focus();
      }
    };

    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [activeGroup]);

  const handleRailHover = useCallback((path: string, pointerType: string) => {
    if (pointerType !== "touch") {
      setActiveGroup(path);
    }
  }, []);

  const handleRailLeave = useCallback((e: React.PointerEvent, path: string) => {
    if (e.pointerType === "touch") return;
    // Only close if pointer moved outside both rail item and flyout
    const flyout = flyoutRefs.current.get(path);
    const related = e.relatedTarget as Node | null;
    if (flyout && related && flyout.contains(related)) return;
    setActiveGroup((prev) => (prev === path ? null : prev));
  }, []);

  const handleFlyoutLeave = useCallback((e: React.PointerEvent, path: string) => {
    if (e.pointerType === "touch") return;
    const rail = railRef.current;
    const related = e.relatedTarget as Node | null;
    if (rail && related && rail.contains(related)) return;
    setActiveGroup((prev) => (prev === path ? null : prev));
  }, []);

  const handleRailClick = useCallback((path: string) => {
    setActiveGroup((prev) => (prev === path ? null : path));
  }, []);

  // Arrow key navigation within flyout
  const handleFlyoutKeyDown = useCallback((e: React.KeyboardEvent<HTMLDivElement>, path: string) => {
    const flyout = flyoutRefs.current.get(path);
    if (!flyout) return;
    const items = Array.from(flyout.querySelectorAll<HTMLElement>('[role="menuitem"]'));
    const focused = document.activeElement as HTMLElement;
    const idx = items.indexOf(focused);

    if (e.key === "ArrowDown") {
      e.preventDefault();
      const next = items[(idx + 1) % items.length];
      next?.focus();
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      const prev = items[(idx - 1 + items.length) % items.length];
      prev?.focus();
    }
  }, []);

  const visibleItems = navItems.filter((item) => {
    if (item.superAdminOnly) return isSuperAdmin();
    if (item.adminOnly) return isAdmin();
    return true;
  });

  return (
    <aside className="app-sidebar" ref={railRef}>
      {/* Header */}
      <div className="sidebar-header">
        <img src="/aurora_icon.png" alt="Aurora" className="w-8 h-8 shrink-0" />
      </div>

      {/* Navigation rail */}
      <nav className="sidebar-nav" style={{ flex: 1 }}>
        {visibleItems.map((item) => {
          const hasChildren = Boolean(item.children && item.children.length > 0);
          const groupActive = hasChildren
            ? item.children!.some((child) => isActive(child.path))
            : isActive(item.path);

          if (!hasChildren) {
            return (
              <Link
                key={item.path}
                to={item.path}
                className={cn("nav-item", groupActive && "active")}
                title={item.label}
              >
                <item.icon size={20} className="nav-icon" />
                <span style={{ fontSize: "9px", lineHeight: 1, marginTop: 2 }}>{item.label}</span>
              </Link>
            );
          }

          // Item with children — rail button + flyout
          const isOpen = activeGroup === item.path;
          const visibleChildren = item.children!.filter(
            (child) => !child.superAdminOnly || isSuperAdmin(),
          );

          return (
            <div key={item.path} style={{ position: "relative" }}>
              <button
                ref={(el) => {
                  if (el) railItemRefs.current.set(item.path, el);
                  else railItemRefs.current.delete(item.path);
                }}
                type="button"
                className={cn("nav-item", groupActive && "active")}
                title={item.label}
                aria-haspopup="menu"
                aria-expanded={isOpen}
                onPointerEnter={(e) => handleRailHover(item.path, e.pointerType)}
                onPointerLeave={(e) => handleRailLeave(e, item.path)}
                onClick={() => handleRailClick(item.path)}
              >
                <item.icon size={20} className="nav-icon" />
                <span style={{ fontSize: "9px", lineHeight: 1, marginTop: 2 }}>{item.label}</span>
              </button>

              {/* Flyout panel */}
              <div
                ref={(el) => {
                  if (el) flyoutRefs.current.set(item.path, el);
                  else flyoutRefs.current.delete(item.path);
                }}
                className={cn("sidebar-flyout", isOpen && "open")}
                role="menu"
                aria-expanded={isOpen}
                aria-label={item.label}
                onPointerLeave={(e) => handleFlyoutLeave(e, item.path)}
                onKeyDown={(e) => handleFlyoutKeyDown(e, item.path)}
              >
                <div className="flyout-title">{item.label}</div>
                {visibleChildren.map((child) => {
                  const childActive = location.pathname === child.path;
                  return (
                    <Link
                      key={child.path}
                      to={child.path}
                      className={cn("flyout-item", childActive && "active")}
                      role="menuitem"
                      tabIndex={isOpen ? 0 : -1}
                    >
                      <child.icon size={14} />
                      {child.label}
                    </Link>
                  );
                })}
              </div>
            </div>
          );
        })}
      </nav>

      {/* Acumenus branding */}
      <div
        style={{
          padding: "var(--space-4) 0",
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
            fontSize: "9px",
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
          ADS
        </a>
      </div>
    </aside>
  );
}
