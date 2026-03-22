import {
  LayoutDashboard,
  Briefcase,
  Calendar,
  Users,
  CheckCircle2,
  ScanLine,
  Dna,
  Cpu,
  MessageSquare,
  Shield,
  Settings,
  Activity,
  UsersRound,
  ScrollText,
  ShieldCheck,
  Bell,
  type LucideIcon,
} from "lucide-react";

export interface NavItem {
  path: string;
  label: string;
  icon: LucideIcon;
  adminOnly?: boolean;
  superAdminOnly?: boolean;
}

export interface NavGroup {
  label: string;
  path?: string;
  items?: NavItem[];
  adminOnly?: boolean;
}

export const navGroups: NavGroup[] = [
  {
    label: "Dashboard",
    path: "/",
  },
  {
    label: "Clinical",
    items: [
      { path: "/cases", label: "Cases", icon: Briefcase },
      { path: "/sessions", label: "Sessions", icon: Calendar },
      { path: "/profiles", label: "Patient Profiles", icon: Users },
      { path: "/decisions", label: "Decisions", icon: CheckCircle2 },
    ],
  },
  {
    label: "Intelligence",
    items: [
      { path: "/imaging", label: "Imaging", icon: ScanLine },
      { path: "/genomics", label: "Genomics", icon: Dna },
      { path: "/copilot", label: "AI Copilot", icon: Cpu },
    ],
  },
  {
    label: "Commons",
    path: "/commons",
  },
  {
    label: "Admin",
    adminOnly: true,
    items: [
      { path: "/admin", label: "Admin Dashboard", icon: Settings },
      { path: "/admin/system-health", label: "System Health", icon: Activity },
      { path: "/admin/users", label: "Users", icon: UsersRound },
      { path: "/admin/user-audit", label: "Audit Log", icon: ScrollText },
      { path: "/admin/roles", label: "Roles & Permissions", icon: ShieldCheck, superAdminOnly: true },
      { path: "/admin/ai-providers", label: "AI Providers", icon: Cpu },
      { path: "/admin/notifications", label: "Notifications", icon: Bell },
    ],
  },
];

export function getSectionForPath(pathname: string): { group: string; items: NavItem[] } {
  if (pathname === "/") {
    return { group: "Dashboard", items: [{ path: "/", label: "Dashboard", icon: LayoutDashboard }] };
  }

  if (pathname.startsWith("/commons")) {
    return { group: "Commons", items: [{ path: "/commons", label: "Commons", icon: MessageSquare }] };
  }

  for (const group of navGroups) {
    if (!group.items) continue;
    for (const item of group.items) {
      if (pathname === item.path || pathname.startsWith(item.path + "/")) {
        if (group.label === "Admin") {
          return { group: group.label, items: group.items };
        }
        return { group: group.label, items: [item] };
      }
    }
  }

  return { group: "Dashboard", items: [{ path: "/", label: "Dashboard", icon: LayoutDashboard }] };
}
