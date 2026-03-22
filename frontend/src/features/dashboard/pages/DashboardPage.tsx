import { Link } from "react-router-dom";
import {
  Users,
  Briefcase,
  Activity,
  Clock,
  ArrowRight,
  Loader2,
  AlertTriangle,
  UserPlus,
  FolderOpen,
  Globe,
} from "lucide-react";
import { MetricCard } from "@/components/ui/MetricCard";
import { Panel } from "@/components/ui/Panel";
import { Badge } from "@/components/ui/Badge";
import { StatusDot } from "@/components/ui/StatusDot";
import { useDashboardStats } from "../hooks/useDashboard";

// ── Priority color mapping ──────────────────────────────────────────────────
const PRIORITY_COLORS: Record<string, string> = {
  critical: "#E85A6B",
  high: "#F59E0B",
  normal: "#2DD4BF",
  low: "#8A857D",
};

const STATUS_BADGE: Record<string, "success" | "warning" | "critical" | "info" | "default"> = {
  open: "info",
  in_progress: "warning",
  review: "accent" as "warning",
  resolved: "success",
  closed: "default",
};

export default function DashboardPage() {
  const { data: stats, isLoading, error } = useDashboardStats();

  return (
    <div className="space-y-8">
      {/* Page header */}
      <div>
        <h1 className="text-2xl font-bold text-[#F0EDE8]">Dashboard</h1>
        <p className="mt-1 text-sm text-[#8A857D]">
          Aurora Clinical Case Intelligence Platform
        </p>
      </div>

      {/* Error banner */}
      {error && (
        <div className="flex items-start gap-3 rounded-lg border border-[#F59E0B]/30 bg-[#F59E0B]/5 px-4 py-3">
          <AlertTriangle size={18} className="mt-0.5 shrink-0 text-[#F59E0B]" />
          <div>
            <p className="text-sm font-medium text-[#F59E0B]">Unable to load dashboard data</p>
            <p className="mt-0.5 text-xs text-[#8A857D]">
              The API may be unavailable. Displaying cached data if available.
            </p>
          </div>
        </div>
      )}

      {/* Metric cards */}
      {isLoading ? (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-28 animate-pulse rounded-lg border border-[#232328] bg-[#1C1C20]" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <MetricCard
            label="Total Patients"
            value={stats?.total_patients ?? 0}
            description="In the system"
            icon={<Users size={18} />}
            to="/patients"
          />
          <MetricCard
            label="Active Cases"
            value={stats?.total_cases ?? 0}
            description="Open cases"
            icon={<Briefcase size={18} />}
            to="/cases"
          />
          <MetricCard
            label="Team Members"
            value={stats?.active_users ?? 0}
            description="Active this week"
            icon={<Activity size={18} />}
          />
          <MetricCard
            label="Pending Decisions"
            value={stats?.pending_decisions ?? 0}
            description="Awaiting review"
            icon={<Clock size={18} />}
            variant={stats?.pending_decisions ? "warning" : "default"}
          />
        </div>
      )}

      {/* Two-column layout: Recent Cases + Quick Actions */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Recent Cases (2/3 width) */}
        <div className="lg:col-span-2">
          <Panel
            header={
              <div className="flex items-center justify-between w-full">
                <span className="text-sm font-semibold text-[#F0EDE8]">Recent Cases</span>
                <Link
                  to="/cases"
                  className="inline-flex items-center gap-1 text-xs font-medium text-[#2DD4BF] transition-colors hover:text-[#25B8A5]"
                >
                  View All <ArrowRight size={12} />
                </Link>
              </div>
            }
          >
            {isLoading ? (
              <div className="flex h-48 items-center justify-center">
                <Loader2 size={24} className="animate-spin text-[#8A857D]" />
              </div>
            ) : stats?.recent_cases.length ? (
              <div className="overflow-hidden rounded-lg border border-[#232328]">
                <table className="w-full">
                  <thead>
                    <tr className="bg-[#1C1C20]">
                      {["Case", "Patient", "Status", "Priority", "Updated"].map((h) => (
                        <th
                          key={h}
                          className="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-[#8A857D]"
                        >
                          {h}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {stats.recent_cases.map((c, i) => (
                      <tr
                        key={c.id}
                        className={`border-t border-[#1C1C20] transition-colors hover:bg-[#1C1C20]/50 ${
                          i % 2 === 0 ? "bg-[#151518]" : "bg-[#1A1A1E]"
                        }`}
                      >
                        <td className="px-4 py-2.5 text-sm font-medium text-[#C5C0B8]">
                          {c.title}
                        </td>
                        <td className="px-4 py-2.5 text-sm text-[#8A857D]">
                          {c.patient_name}
                        </td>
                        <td className="px-4 py-2.5">
                          <Badge variant={STATUS_BADGE[c.status] ?? "default"}>
                            {c.status.replace(/_/g, " ")}
                          </Badge>
                        </td>
                        <td className="px-4 py-2.5">
                          <span
                            className="inline-flex items-center gap-1.5 text-xs font-medium"
                            style={{ color: PRIORITY_COLORS[c.priority] ?? "#8A857D" }}
                          >
                            <span
                              className="h-1.5 w-1.5 rounded-full"
                              style={{ backgroundColor: PRIORITY_COLORS[c.priority] ?? "#8A857D" }}
                            />
                            {c.priority}
                          </span>
                        </td>
                        <td className="px-4 py-2.5 font-['IBM_Plex_Mono',monospace] text-xs text-[#5A5650]">
                          {c.updated_at
                            ? new Date(c.updated_at).toLocaleDateString("en-US", {
                                month: "short",
                                day: "numeric",
                              })
                            : "--"}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="flex flex-col items-center justify-center py-12">
                <Briefcase size={32} className="mb-3 text-[#5A5650]" />
                <p className="text-sm font-medium text-[#C5C0B8]">No cases yet</p>
                <p className="mt-1 text-xs text-[#8A857D]">
                  Cases will appear here as they are created.
                </p>
              </div>
            )}
          </Panel>
        </div>

        {/* Quick Actions + System Health (1/3 width) */}
        <div className="space-y-6">
          {/* Quick Actions */}
          <Panel
            header={
              <span className="text-sm font-semibold text-[#F0EDE8]">Quick Actions</span>
            }
          >
            <div className="flex flex-col gap-2">
              <Link
                to="/cases/new"
                className="flex items-center gap-3 rounded-lg border border-[#232328] bg-[#151518] px-4 py-3 text-sm text-[#C5C0B8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <FolderOpen size={16} />
                New Case
              </Link>
              <Link
                to="/patients"
                className="flex items-center gap-3 rounded-lg border border-[#232328] bg-[#151518] px-4 py-3 text-sm text-[#C5C0B8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <UserPlus size={16} />
                Browse Patients
              </Link>
              <Link
                to="/commons"
                className="flex items-center gap-3 rounded-lg border border-[#232328] bg-[#151518] px-4 py-3 text-sm text-[#C5C0B8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <Globe size={16} />
                Open Commons
              </Link>
            </div>
          </Panel>

          {/* System Health */}
          <Panel
            header={
              <div className="flex items-center justify-between w-full">
                <span className="text-sm font-semibold text-[#F0EDE8]">System Health</span>
                <Link
                  to="/admin/system-health"
                  className="inline-flex items-center gap-1 text-xs font-medium text-[#2DD4BF] transition-colors hover:text-[#25B8A5]"
                >
                  Details <ArrowRight size={12} />
                </Link>
              </div>
            }
          >
            {isLoading ? (
              <div className="flex h-24 items-center justify-center">
                <Loader2 size={18} className="animate-spin text-[#8A857D]" />
              </div>
            ) : stats?.system_health ? (
              <div className="space-y-2">
                <div className="flex items-center gap-2 mb-3">
                  <StatusDot
                    status={
                      stats.system_health.status === "healthy"
                        ? "healthy"
                        : stats.system_health.status === "degraded"
                          ? "degraded"
                          : "critical"
                    }
                  />
                  <span className="text-sm font-medium text-[#F0EDE8]">
                    {stats.system_health.status === "healthy" ? "All Systems Operational" : "Issues Detected"}
                  </span>
                </div>
                {stats.system_health.services.map((svc) => (
                  <div
                    key={svc.name}
                    className="flex items-center justify-between rounded-md border border-[#232328] bg-[#151518] px-3 py-2"
                  >
                    <span className="text-xs text-[#8A857D]">{svc.name}</span>
                    <Badge
                      variant={
                        svc.status === "healthy"
                          ? "success"
                          : svc.status === "degraded"
                            ? "warning"
                            : "critical"
                      }
                    >
                      {svc.status}
                    </Badge>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-[#8A857D]">No health data available.</p>
            )}
          </Panel>
        </div>
      </div>
    </div>
  );
}
