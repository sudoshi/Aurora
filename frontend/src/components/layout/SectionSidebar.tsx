import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/stores/authStore";
import { getSectionForPath } from "@/config/navigation";

export function SectionSidebar() {
  const location = useLocation();
  const { isSuperAdmin } = useAuthStore();
  const section = getSectionForPath(location.pathname);

  const isActive = (path: string) =>
    path === "/" ? location.pathname === "/" : location.pathname === path;

  const visibleItems = section.items.filter((item) => {
    if (item.superAdminOnly) return isSuperAdmin();
    return true;
  });

  return (
    <aside className="section-sidebar" aria-label={`${section.group} navigation`}>
      <div className="section-sidebar-title">{section.group}</div>
      <nav className="section-sidebar-nav">
        {visibleItems.map((item) => (
          <Link
            key={item.path}
            to={item.path}
            className={cn("section-sidebar-item", isActive(item.path) && "active")}
            aria-current={isActive(item.path) ? "page" : undefined}
          >
            <item.icon size={16} />
            <span>{item.label}</span>
          </Link>
        ))}
      </nav>
    </aside>
  );
}
