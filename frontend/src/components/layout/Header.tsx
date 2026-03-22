import { useState, useRef, useEffect, useCallback } from "react";
import { useNavigate, Link, useLocation } from "react-router-dom";
import { useAuthStore } from "@/stores/authStore";
import { useUiStore } from "@/stores/uiStore";
import { useAbbyStore } from "@/stores/abbyStore";
import { LogOut, User, Search, Sparkles, Bell, Settings, ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";
import { navGroups, type NavGroup } from "@/config/navigation";
import { AboutAbbyModal } from "./AboutAbbyModal";

function UserDropdown() {
  const { user, logout } = useAuthStore();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const avatarUrl = user?.avatar ? `/storage/${user.avatar}` : null;

  return (
    <div ref={ref} className="relative">
      <button
        onClick={() => setOpen((prev) => !prev)}
        className="btn btn-ghost btn-sm"
        style={{ gap: "var(--space-1)" }}
      >
        {avatarUrl ? (
          <img
            src={avatarUrl}
            alt={user?.name ?? ""}
            className="w-6 h-6 rounded-full object-cover"
          />
        ) : (
          <User size={16} />
        )}
        <span style={{ color: "var(--text-muted)", fontSize: "var(--text-sm)" }}>
          {user?.name}
        </span>
        <ChevronDown size={14} style={{ color: "var(--text-ghost)" }} />
      </button>

      {open && (
        <div
          className="absolute right-0 mt-1 w-48 rounded-lg shadow-xl z-50 py-1"
          style={{ border: "1px solid var(--border-default)", background: "var(--surface-raised)" }}
        >
          <button
            onClick={() => {
              setOpen(false);
              navigate("/settings");
            }}
            className="flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors"
            style={{ color: "var(--text-secondary)" }}
            onMouseEnter={(e) => { e.currentTarget.style.background = "var(--surface-overlay)"; }}
            onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}
          >
            <Settings size={14} />
            Settings
          </button>
          <div className="my-1" style={{ borderTop: "1px solid var(--border-default)" }} />
          <button
            onClick={() => {
              setOpen(false);
              logout();
            }}
            className="flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors"
            style={{ color: "var(--critical)" }}
            onMouseEnter={(e) => { e.currentTarget.style.background = "var(--surface-overlay)"; }}
            onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}
          >
            <LogOut size={14} />
            Logout
          </button>
        </div>
      )}
    </div>
  );
}

function NavDropdown({ group, isActive }: { group: NavGroup; isActive: boolean }) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
  const location = useLocation();

  useEffect(() => { setOpen(false); }, [location.pathname]);

  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, [open]);

  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => { if (e.key === "Escape") setOpen(false); };
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, [open]);

  const handleEnter = useCallback(() => {
    clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => setOpen(true), 100);
  }, []);

  const handleLeave = useCallback(() => {
    clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => setOpen(false), 150);
  }, []);

  const isItemActive = (path: string) =>
    path === "/" ? location.pathname === "/" : location.pathname.startsWith(path);

  return (
    <div
      ref={ref}
      className="topnav-group"
      onPointerEnter={(e) => { if (e.pointerType !== "touch") handleEnter(); }}
      onPointerLeave={(e) => { if (e.pointerType !== "touch") handleLeave(); }}
    >
      <button
        className={cn("topnav-label", isActive && "active")}
        onClick={() => setOpen(!open)}
        aria-expanded={open}
        aria-haspopup="menu"
      >
        {group.label}
        <ChevronDown size={14} className={cn("topnav-chevron", open && "open")} />
      </button>

      {open && group.items && (
        <div className="topnav-dropdown" role="menu" aria-label={group.label}>
          {group.items.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={cn("topnav-dropdown-item", isItemActive(item.path) && "active")}
              role="menuitem"
              onClick={() => setOpen(false)}
            >
              <item.icon size={16} />
              {item.label}
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

export function Header() {
  const { user, isAuthenticated, isAdmin } = useAuthStore();
  const { setCommandPaletteOpen } = useUiStore();
  const togglePanel = useAbbyStore((s) => s.togglePanel);
  const [aboutAbbyOpen, setAboutAbbyOpen] = useState(false);
  const location = useLocation();

  const isGroupActive = (group: NavGroup): boolean => {
    if (group.path) {
      return group.path === "/"
        ? location.pathname === "/"
        : location.pathname.startsWith(group.path);
    }
    return group.items?.some((item) =>
      item.path === "/" ? location.pathname === "/" : location.pathname.startsWith(item.path)
    ) ?? false;
  };

  const visibleGroups = navGroups.filter((g) => {
    if (g.adminOnly) return isAdmin();
    return true;
  });

  return (
    <header className="app-topbar">
      {/* Left: Brand */}
      <Link to="/" className="topbar-brand">
        <img src="/image/aurora.svg" alt="Aurora" className="w-8 h-8 shrink-0" />
        <span className="topbar-brand-name">Aurora</span>
      </Link>

      {/* Center: Navigation */}
      {isAuthenticated && (
        <nav className="topnav-inline" aria-label="Main navigation">
          {visibleGroups.map((group) =>
            group.path ? (
              <Link
                key={group.label}
                to={group.path}
                className={cn("topnav-label", isGroupActive(group) && "active")}
              >
                {group.label}
              </Link>
            ) : (
              <NavDropdown
                key={group.label}
                group={group}
                isActive={isGroupActive(group)}
              />
            )
          )}
        </nav>
      )}

      {/* Right: Search + actions */}
      <div className="topbar-actions">
        {isAuthenticated && user ? (
          <>
            <button
              className="search-bar"
              onClick={() => setCommandPaletteOpen(true)}
              style={{ maxWidth: 220, cursor: "pointer" }}
            >
              <Search size={16} className="search-icon" />
              <span style={{ color: "var(--text-ghost)", fontSize: "var(--text-sm)" }}>
                Search...
              </span>
              <span className="search-shortcut">Ctrl K</span>
            </button>

            <button
              className="btn btn-ghost btn-sm"
              onClick={() => setAboutAbbyOpen(true)}
              style={{
                color: "var(--primary)",
                fontWeight: 600,
                fontSize: "var(--text-sm)",
                gap: "var(--space-1)",
              }}
              aria-label="About Abby"
              title="About Abby"
            >
              About Abby
            </button>

            <button
              className="btn btn-ghost btn-icon btn-sm"
              onClick={togglePanel}
              aria-label="AI Assistant"
              title="AI Assistant"
            >
              <Sparkles size={18} />
            </button>

            <AboutAbbyModal
              open={aboutAbbyOpen}
              onClose={() => setAboutAbbyOpen(false)}
            />

            <button
              className="btn btn-ghost btn-icon btn-sm"
              aria-label="Notifications"
              title="Notifications"
            >
              <Bell size={18} />
            </button>

            <UserDropdown />
          </>
        ) : (
          <a href="/login" className="btn btn-primary btn-sm">
            Login
          </a>
        )}
      </div>
    </header>
  );
}
