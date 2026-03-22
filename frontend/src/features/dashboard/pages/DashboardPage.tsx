import { Link } from "react-router-dom";
import {
  Users,
  Briefcase,
  Activity,
  Clock,
  ArrowRight,
  Loader2,
  AlertTriangle,
  FolderOpen,
  MessageSquare,
  Calendar,
} from "lucide-react";
import { MetricCard } from "@/components/ui/MetricCard";
import { Panel } from "@/components/ui/Panel";
import { Badge } from "@/components/ui/Badge";
import { StatusDot } from "@/components/ui/StatusDot";
import { useDashboardStats } from "../hooks/useDashboard";

const URGENCY_COLORS: Record<string, string> = {
  emergent: "#F0607A",
  urgent: "#F59E0B",
  routine: "#2DD4BF",
};

const STATUS_BADGE: Record<string, "success" | "warning" | "critical" | "info" | "default"> = {
  draft: "default",
  active: "info",
  in_review: "warning",
  closed: "success",
  archived: "inactive" as "default",
};

const SPECIALTY_LABELS: Record<string, string> = {
  oncology: "Oncology",
  surgical: "Surgical",
  rare_disease: "Rare Disease",
  complex_medical: "Complex Medical",
};

export default function DashboardPage() {
  const { data: stats, isLoading, error } = useDashboardStats();

  return (
    <div className="space-y-8">
      {/* Page header */}
      <div>
        <h1 className="text-2xl font-bold text-[#E8ECF4]">Dashboard</h1>
        <p className="mt-1 text-sm text-[#7A8298]">
          Aurora Clinical Case Intelligence Platform
        </p>
      </div>

      {/* Error banner */}
      {error && (
        <div className="flex items-start gap-3 rounded-lg border border-[#F59E0B]/30 bg-[#F59E0B]/5 px-4 py-3">
          <AlertTriangle size={18} className="mt-0.5 shrink-0 text-[#F59E0B]" />
          <div>
            <p className="text-sm font-medium text-[#F59E0B]">Unable to load dashboard data</p>
            <p className="mt-0.5 text-xs text-[#7A8298]">
              The API may be unavailable. Please try again.
            </p>
          </div>
        </div>
      )}

      {/* Metric cards */}
      {isLoading ? (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-28 animate-pulse rounded-lg border border-[#1C1C48] bg-[#16163A]" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <MetricCard
            label="Total Patients"
            value={stats?.total_patients ?? 0}
            description="In clinical schema"
            icon={<Users size={18} />}
            to="/profiles"
          />
          <MetricCard
            label="Active Cases"
            value={stats?.active_cases ?? 0}
            description={`${stats?.total_cases ?? 0} total`}
            icon={<Briefcase size={18} />}
            to="/cases"
          />
          <MetricCard
            label="Team Members"
            value={stats?.active_users ?? 0}
            description={`${stats?.total_users ?? 0} total users`}
            icon={<Activity size={18} />}
          />
          <MetricCard
            label="Pending Decisions"
            value={stats?.pending_decisions ?? 0}
            description="Awaiting review"
            icon={<Clock size={18} />}
            variant={stats?.pending_decisions ? "warning" : "default"}
            to="/decisions"
          />
        </div>
      )}

      {/* Two-column layout */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Recent Cases (2/3 width) */}
        <div className="lg:col-span-2">
          <Panel
            header={
              <div className="flex items-center justify-between w-full">
                <span className="text-sm font-semibold text-[#E8ECF4]">Recent Cases</span>
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
                <Loader2 size={24} className="animate-spin text-[#7A8298]" />
              </div>
            ) : (stats?.recent_cases ?? []).length > 0 ? (
              <div className="overflow-hidden rounded-lg border border-[#1C1C48]">
                <table className="w-full">
                  <thead>
                    <tr className="bg-[#16163A]">
                      {["Case", "Specialty", "Status", "Urgency", "Created"].map((h) => (
                        <th
                          key={h}
                          className="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-[#7A8298]"
                        >
                          {h}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {(stats?.recent_cases ?? []).map((c, i) => (
                      <tr
                        key={c.id}
                        className={`border-t border-[#16163A] transition-colors hover:bg-[#16163A]/50 cursor-pointer ${
                          i % 2 === 0 ? "bg-[#10102A]" : "bg-[#16163A]"
                        }`}
                        onClick={() => window.location.href = `/cases/${c.id}`}
                      >
                        <td className="px-4 py-2.5">
                          <div className="text-sm font-medium text-[#B4BAC8]">{c.title}</div>
                          <div className="text-[10px] text-[#4A5068] mt-0.5">by {c.creator_name}</div>
                        </td>
                        <td className="px-4 py-2.5">
                          <Badge variant="default">
                            {SPECIALTY_LABELS[c.specialty] ?? c.specialty}
                          </Badge>
                        </td>
                        <td className="px-4 py-2.5">
                          <Badge variant={STATUS_BADGE[c.status] ?? "default"}>
                            {c.status.replace(/_/g, " ")}
                          </Badge>
                        </td>
                        <td className="px-4 py-2.5">
                          <span
                            className="inline-flex items-center gap-1.5 text-xs font-medium"
                            style={{ color: URGENCY_COLORS[c.urgency] ?? "#7A8298" }}
                          >
                            <span
                              className="h-1.5 w-1.5 rounded-full"
                              style={{ backgroundColor: URGENCY_COLORS[c.urgency] ?? "#7A8298" }}
                            />
                            {c.urgency}
                          </span>
                        </td>
                        <td className="px-4 py-2.5 font-['IBM_Plex_Mono',monospace] text-xs text-[#4A5068]">
                          {new Date(c.created_at).toLocaleDateString("en-US", {
                            month: "short",
                            day: "numeric",
                          })}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="flex flex-col items-center justify-center py-12">
                <Briefcase size={32} className="mb-3 text-[#4A5068]" />
                <p className="text-sm font-medium text-[#B4BAC8]">No cases yet</p>
                <p className="mt-1 text-xs text-[#7A8298]">
                  Cases will appear here as they are created.
                </p>
              </div>
            )}
          </Panel>
        </div>

        {/* Quick Actions + System Health */}
        <div className="space-y-6">
          {/* Quick Actions */}
          <Panel
            header={
              <span className="text-sm font-semibold text-[#E8ECF4]">Quick Actions</span>
            }
          >
            <div className="flex flex-col gap-2">
              <Link
                to="/cases"
                className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#10102A] px-4 py-3 text-sm text-[#B4BAC8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <FolderOpen size={16} />
                Browse Cases
              </Link>
              <Link
                to="/sessions"
                className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#10102A] px-4 py-3 text-sm text-[#B4BAC8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <Calendar size={16} />
                Sessions
              </Link>
              <Link
                to="/profiles"
                className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#10102A] px-4 py-3 text-sm text-[#B4BAC8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <Users size={16} />
                Patient Profiles
              </Link>
              <Link
                to="/commons"
                className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#10102A] px-4 py-3 text-sm text-[#B4BAC8] transition-colors hover:border-[#2DD4BF]/30 hover:text-[#2DD4BF]"
              >
                <MessageSquare size={16} />
                Open Commons
              </Link>
            </div>
          </Panel>

          {/* System Health */}
          <Panel
            header={
              <div className="flex items-center justify-between w-full">
                <span className="text-sm font-semibold text-[#E8ECF4]">System Health</span>
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
                <Loader2 size={18} className="animate-spin text-[#7A8298]" />
              </div>
            ) : stats?.system_health ? (
              <div className="space-y-2">
                {Object.entries(stats.system_health).map(([name, status]) => (
                  <div
                    key={name}
                    className="flex items-center justify-between rounded-md border border-[#1C1C48] bg-[#10102A] px-3 py-2"
                  >
                    <span className="text-xs text-[#7A8298] capitalize">{name}</span>
                    <div className="flex items-center gap-2">
                      <StatusDot
                        status={
                          status === "healthy"
                            ? "healthy"
                            : status === "degraded"
                              ? "degraded"
                              : "critical"
                        }
                      />
                      <span className="text-xs text-[#B4BAC8] capitalize">{status}</span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-[#7A8298]">No health data available.</p>
            )}
          </Panel>
        </div>
      </div>
    </div>
  );
}
