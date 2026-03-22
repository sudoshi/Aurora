import { useNavigate } from "react-router-dom";
import {
  Loader2, Gavel, CheckCircle, Clock, AlertCircle,
  ChevronRight, User, Calendar,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useDecisionDashboard, useUpdateFollowUpStatus } from "../../collaboration/hooks/useDecisions";
import type { Decision, FollowUp } from "../../collaboration/types/decision";

// ── Color maps ───────────────────────────────────────────────────────────────

const DECISION_STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  proposed:     { bg: "#F59E0B15", text: "#F59E0B" },
  under_review: { bg: "#60A5FA15", text: "#60A5FA" },
  approved:     { bg: "#2DD4BF15", text: "#2DD4BF" },
  rejected:     { bg: "#F0607A15", text: "#F0607A" },
  deferred:     { bg: "#7A829815", text: "#7A8298" },
};

// ── Stat card ────────────────────────────────────────────────────────────────

function StatCard({
  label,
  value,
  color,
  icon,
}: {
  label: string;
  value: number;
  color: string;
  icon: React.ReactNode;
}) {
  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
      <div className="mb-2 flex items-center gap-2">
        <span style={{ color }}>{icon}</span>
        <span className="text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
          {label}
        </span>
      </div>
      <p
        className="font-['IBM_Plex_Mono',monospace] text-2xl font-bold"
        style={{ color }}
      >
        {value}
      </p>
    </div>
  );
}

// ── Recent decision row ──────────────────────────────────────────────────────

function DecisionRow({ decision }: { decision: Decision }) {
  const navigate = useNavigate();
  const statusCfg = DECISION_STATUS_COLORS[decision.status] ?? {
    bg: "#2A2A6020",
    text: "#7A8298",
  };

  return (
    <button
      type="button"
      onClick={() => navigate(`/cases/${decision.case_id}`)}
      className="flex w-full items-center justify-between rounded-lg border border-[#1C1C48] bg-[#16163A] p-3 text-left transition-all hover:border-[#2DD4BF]/30 hover:bg-[#16163A]"
    >
      <div className="flex-1 min-w-0">
        <div className="mb-1 flex items-center gap-2">
          <span
            className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
            style={{ backgroundColor: statusCfg.bg, color: statusCfg.text }}
          >
            {decision.status.replace(/_/g, " ")}
          </span>
          <span className="rounded bg-[#1C1C48] px-1.5 py-0.5 text-[10px] text-[#7A8298]">
            {decision.decision_type.replace(/_/g, " ")}
          </span>
        </div>
        <p className="text-sm font-medium text-[#B4BAC8] truncate">
          {decision.recommendation}
        </p>
        <div className="mt-1 flex items-center gap-2 text-[10px] text-[#4A5068]">
          {decision.proposer && <span>{decision.proposer.name}</span>}
          <span>&middot;</span>
          <span className="font-['IBM_Plex_Mono',monospace]">
            {new Date(decision.created_at).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
            })}
          </span>
          {decision.votes_summary && (
            <>
              <span>&middot;</span>
              <span className="text-[#2DD4BF]">
                {decision.votes_summary.agree} agree
              </span>
              <span className="text-[#F0607A]">
                {decision.votes_summary.disagree} disagree
              </span>
            </>
          )}
        </div>
      </div>
      <ChevronRight size={14} className="ml-2 shrink-0 text-[#2A2A60]" />
    </button>
  );
}

// ── Follow-up row ────────────────────────────────────────────────────────────

function FollowUpRow({ followUp }: { followUp: FollowUp }) {
  const updateStatus = useUpdateFollowUpStatus();
  const isPending = followUp.status === "pending";

  const handleToggle = () => {
    const newStatus = isPending ? "completed" : "pending";
    updateStatus.mutate({ followUpId: followUp.id, status: newStatus });
  };

  return (
    <div className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#16163A] p-3">
      <button
        type="button"
        onClick={handleToggle}
        disabled={updateStatus.isPending}
        className={cn(
          "flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors",
          isPending
            ? "border-[#2A2A60] bg-[#10102A] hover:border-[#2DD4BF]"
            : "border-[#2DD4BF] bg-[#2DD4BF]/10",
        )}
      >
        {!isPending && <CheckCircle size={12} className="text-[#2DD4BF]" />}
      </button>

      <div className="flex-1 min-w-0">
        <p
          className={cn(
            "text-sm",
            isPending
              ? "font-medium text-[#B4BAC8]"
              : "text-[#4A5068] line-through",
          )}
        >
          {followUp.title}
        </p>
        <div className="flex items-center gap-2 text-[10px] text-[#4A5068]">
          {followUp.assignee && (
            <span className="inline-flex items-center gap-1">
              <User size={8} />
              {followUp.assignee.name}
            </span>
          )}
          {followUp.due_date && (
            <span className="inline-flex items-center gap-1">
              <Calendar size={8} />
              <span className="font-['IBM_Plex_Mono',monospace]">
                {new Date(followUp.due_date).toLocaleDateString("en-US", {
                  month: "short",
                  day: "numeric",
                })}
              </span>
            </span>
          )}
        </div>
      </div>
    </div>
  );
}

// ── Main page ────────────────────────────────────────────────────────────────

export default function DecisionDashboardPage() {
  const { data, isLoading } = useDecisionDashboard();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={24} className="animate-spin text-[#4A5068]" />
      </div>
    );
  }

  const stats = data?.stats ?? { approved: 0, pending: 0, deferred: 0, total: 0 };
  const recentDecisions = data?.recent_decisions ?? [];
  const pendingFollowUps = data?.pending_follow_ups ?? [];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-[#E8ECF4]">Decisions</h1>
        <p className="mt-1 text-sm text-[#7A8298]">
          Cross-case decision tracking and follow-ups
        </p>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          label="Total"
          value={stats.total}
          color="#B4BAC8"
          icon={<Gavel size={16} />}
        />
        <StatCard
          label="Approved"
          value={stats.approved}
          color="#2DD4BF"
          icon={<CheckCircle size={16} />}
        />
        <StatCard
          label="Pending"
          value={stats.pending}
          color="#F59E0B"
          icon={<Clock size={16} />}
        />
        <StatCard
          label="Deferred"
          value={stats.deferred}
          color="#7A8298"
          icon={<AlertCircle size={16} />}
        />
      </div>

      {/* Two-column layout */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent decisions */}
        <div className="space-y-3">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
            Recent Decisions
          </h2>
          {recentDecisions.length > 0 ? (
            <div className="space-y-2">
              {recentDecisions.map((d) => (
                <DecisionRow key={d.id} decision={d} />
              ))}
            </div>
          ) : (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
              <Gavel size={24} className="mb-2 text-[#4A5068]" />
              <p className="text-sm text-[#7A8298]">No decisions yet</p>
            </div>
          )}
        </div>

        {/* Pending follow-ups */}
        <div className="space-y-3">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
            Pending Follow-ups
            <span className="ml-2 font-['IBM_Plex_Mono',monospace] text-[#4A5068]">
              ({pendingFollowUps.length})
            </span>
          </h2>
          {pendingFollowUps.length > 0 ? (
            <div className="space-y-2">
              {pendingFollowUps.map((fu) => (
                <FollowUpRow key={fu.id} followUp={fu} />
              ))}
            </div>
          ) : (
            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
              <CheckCircle size={24} className="mb-2 text-[#2DD4BF]" />
              <p className="text-sm text-[#7A8298]">All caught up</p>
              <p className="mt-1 text-xs text-[#4A5068]">
                No pending follow-ups assigned to you.
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
