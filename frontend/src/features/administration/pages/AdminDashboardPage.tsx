import { Link } from "react-router-dom";
import {
  Activity, Bot, KeyRound, ShieldCheck, Users, ArrowRight,
  type LucideIcon,
} from "lucide-react";
import { MetricCard } from "@/components/ui/MetricCard";
import { Panel } from "@/components/ui/Panel";
import { useUsers } from "../hooks/useAdminUsers";
import { useRoles } from "../hooks/useAdminRoles";
import { useAiProviders, useSystemHealth } from "../hooks/useAiProviders";
import { useAuthStore } from "@/stores/authStore";

interface NavCard {
  title: string;
  description: string;
  icon: LucideIcon;
  href: string;
  color: string;
  bg: string;
  adminOnly?: boolean;
  superAdminOnly?: boolean;
}

const NAV_CARDS: NavCard[] = [
  {
    title: "User Management",
    description: "Create, edit, and deactivate user accounts. Assign roles to control access.",
    icon: Users,
    href: "/admin/users",
    color: "text-blue-500",
    bg: "bg-blue-500/10",
  },
  {
    title: "Roles & Permissions",
    description: "Define custom roles and fine-tune permission assignments across all domains.",
    icon: ShieldCheck,
    href: "/admin/roles",
    color: "text-purple-500",
    bg: "bg-purple-500/10",
    adminOnly: true,
  },
  {
    title: "Authentication Providers",
    description: "Enable and configure LDAP, OAuth 2.0, SAML 2.0, or OIDC for SSO.",
    icon: KeyRound,
    href: "/admin/auth-providers",
    color: "text-amber-500",
    bg: "bg-amber-500/10",
    superAdminOnly: true,
  },
  {
    title: "AI Provider Configuration",
    description: "Switch Abby's backend between local Ollama, Anthropic, OpenAI, Gemini, and more.",
    icon: Bot,
    href: "/admin/ai-providers",
    color: "text-orange-500",
    bg: "bg-orange-500/10",
    adminOnly: true,
  },
  {
    title: "System Health",
    description: "Live status of Aurora services: database, cache, queue, and AI backend.",
    icon: Activity,
    href: "/admin/system-health",
    color: "text-emerald-500",
    bg: "bg-emerald-500/10",
  },
];

export default function AdminDashboardPage() {
  const { isAdmin, isSuperAdmin } = useAuthStore();
  const { data: usersPage } = useUsers({ per_page: 1 });
  const { data: roles } = useRoles();
  const { data: aiProviders } = useAiProviders();
  const { data: health } = useSystemHealth();

  const activeAiProvider = aiProviders?.find((p) => p.is_active);
  const overallHealth = health?.services.every((s) => s.status === "healthy")
    ? "Healthy"
    : health?.services.some((s) => s.status === "down")
      ? "Degraded"
      : health
        ? "Warning"
        : "--";

  const visibleCards = NAV_CARDS.filter((card) => {
    if (card.superAdminOnly) return isSuperAdmin();
    if (card.adminOnly) return isAdmin();
    return true;
  });

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-[#E8ECF4]">Administration</h1>
        <p className="mt-1 text-sm text-[#7A8298]">
          Manage users, roles, permissions, and platform configuration.
        </p>
      </div>

      {/* Quick stats */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <MetricCard
          label="Total Users"
          value={usersPage?.total ?? "--"}
          icon={<Users size={16} />}
          to="/admin/users"
        />
        <MetricCard
          label="Roles Defined"
          value={roles?.length ?? "--"}
          icon={<ShieldCheck size={16} />}
          to="/admin/roles"
        />
        <MetricCard
          label="System Health"
          value={overallHealth}
          icon={<Activity size={16} />}
          to="/admin/system-health"
        />
        <MetricCard
          label="Active AI"
          value={activeAiProvider?.display_name ?? "--"}
          description={activeAiProvider?.model}
          icon={<Bot size={16} />}
          to="/admin/ai-providers"
        />
      </div>

      {/* Navigation cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {visibleCards.map((card) => (
          <Link key={card.href} to={card.href} className="block">
            <Panel className="group h-full cursor-pointer transition-colors hover:border-[#2DD4BF]/50">
              <div className="flex h-full flex-col justify-between">
                <div>
                  <div className={`inline-flex rounded-md p-2 ${card.bg}`}>
                    <card.icon className={`h-5 w-5 ${card.color}`} />
                  </div>
                  <h3 className="mt-4 text-base font-semibold text-[#E8ECF4]">{card.title}</h3>
                  <p className="mt-1 text-sm text-[#7A8298]">{card.description}</p>
                </div>
                <div className="mt-4 flex items-center gap-1 text-sm font-medium text-[#2DD4BF] opacity-0 transition-opacity group-hover:opacity-100">
                  Open <ArrowRight className="h-4 w-4" />
                </div>
              </div>
            </Panel>
          </Link>
        ))}
      </div>
    </div>
  );
}
