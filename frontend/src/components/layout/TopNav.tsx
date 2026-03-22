import { useState, useRef, useEffect, useCallback } from "react";
import { Link, useLocation } from "react-router-dom";
import { ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/stores/authStore";
import { navGroups, type NavGroup } from "@/config/navigation";

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

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === "Enter" || e.key === " " || e.key === "ArrowDown") {
      e.preventDefault();
      setOpen(true);
    }
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
        onKeyDown={handleKeyDown}
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

export function TopNav() {
  const location = useLocation();
  const { isAdmin } = useAuthStore();

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
    <nav className="topnav-bar" aria-label="Main navigation">
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
  );
}
