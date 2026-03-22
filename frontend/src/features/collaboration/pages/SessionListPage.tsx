import { useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  Plus, Loader2, Calendar, Users, Briefcase,
  ChevronDown, ChevronUp, Radio, Clock,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useSessions, useCreateSession } from "../hooks/useSessions";
import { SessionForm } from "../components/SessionForm";
import type { Session, SessionFilters, CreateSessionData, UpdateSessionData } from "../types/session";

// ── Color maps ───────────────────────────────────────────────────────────────

const TYPE_COLORS: Record<string, { bg: string; text: string }> = {
  tumor_board:       { bg: "#F0607A15", text: "#F0607A" },
  mdc:               { bg: "#60A5FA15", text: "#60A5FA" },
  surgical_planning: { bg: "#2DD4BF15", text: "#2DD4BF" },
  grand_rounds:      { bg: "#A78BFA15", text: "#A78BFA" },
  ad_hoc:            { bg: "#F59E0B15", text: "#F59E0B" },
};

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  scheduled: { bg: "#60A5FA15", text: "#60A5FA" },
  live:      { bg: "#2DD4BF15", text: "#2DD4BF" },
  completed: { bg: "#4A506815", text: "#4A5068" },
  cancelled: { bg: "#2A2A6015", text: "#4A5068" },
};

// ── Session card ─────────────────────────────────────────────────────────────

function SessionCard({ session }: { session: Session }) {
  const navigate = useNavigate();
  const typeCfg = TYPE_COLORS[session.session_type] ?? { bg: "#2A2A6020", text: "#7A8298" };
  const statusCfg = STATUS_COLORS[session.status] ?? { bg: "#2A2A6020", text: "#7A8298" };

  const scheduledDate = new Date(session.scheduled_at);
  const dateStr = scheduledDate.toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
  });
  const timeStr = scheduledDate.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
  });

  const caseCount = session.session_cases?.length ?? 0;
  const participantCount = session.participants?.length ?? 0;

  return (
    <button
      type="button"
      onClick={() => navigate(`/sessions/${session.id}`)}
      className={cn(
        "w-full text-left rounded-lg border border-[#1C1C48] bg-[#10102A] p-4 transition-all",
        "hover:border-[#2DD4BF]/30 hover:bg-[#16163A] focus:outline-none focus:ring-1 focus:ring-[#2DD4BF]/40",
        session.status === "live" && "border-[#2DD4BF]/40",
      )}
    >
      {/* Top row */}
      <div className="mb-3 flex items-center gap-2">
        <span
          className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
          style={{ backgroundColor: typeCfg.bg, color: typeCfg.text }}
        >
          {session.session_type.replace(/_/g, " ")}
        </span>
        <span
          className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
          style={{ backgroundColor: statusCfg.bg, color: statusCfg.text }}
        >
          {session.status === "live" && (
            <Radio size={8} className="animate-pulse" />
          )}
          {session.status}
        </span>
      </div>

      {/* Title */}
      <h3 className="mb-1 text-sm font-semibold text-[#E8ECF4]">
        {session.title}
      </h3>
      {session.description && (
        <p className="mb-3 text-xs text-[#7A8298] line-clamp-1">
          {session.description}
        </p>
      )}

      {/* Bottom row */}
      <div className="flex items-center justify-between border-t border-[#16163A] pt-3">
        <div className="flex items-center gap-3">
          <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
            <Calendar size={10} />
            <span className="font-['IBM_Plex_Mono',monospace]">{dateStr}</span>
          </span>
          <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
            <Clock size={10} />
            <span className="font-['IBM_Plex_Mono',monospace]">{timeStr}</span>
          </span>
          <span className="text-[10px] text-[#4A5068]">
            ({session.duration_minutes}m)
          </span>
        </div>
        <div className="flex items-center gap-3">
          {caseCount > 0 && (
            <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
              <Briefcase size={10} />
              {caseCount} case{caseCount !== 1 ? "s" : ""}
            </span>
          )}
          {participantCount > 0 && (
            <span className="inline-flex items-center gap-1 text-[10px] text-[#4A5068]">
              <Users size={10} />
              {participantCount}
            </span>
          )}
        </div>
      </div>
    </button>
  );
}

// ── Main page ────────────────────────────────────────────────────────────────

export default function SessionListPage() {
  const [filters] = useState<SessionFilters>({ page: 1, per_page: 50 });
  const [showForm, setShowForm] = useState(false);
  const [showPast, setShowPast] = useState(false);

  const { data, isLoading } = useSessions(filters);
  const createSession = useCreateSession();

  const sessions = data?.data ?? [];

  const upcoming = sessions.filter(
    (s) => s.status === "scheduled" || s.status === "live",
  );
  const past = sessions.filter(
    (s) => s.status === "completed" || s.status === "cancelled",
  );

  const handleCreate = (formData: CreateSessionData | UpdateSessionData) => {
    createSession.mutate(formData as CreateSessionData, {
      onSuccess: () => setShowForm(false),
    });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-[#E8ECF4]">Sessions</h1>
          <p className="mt-1 text-sm text-[#7A8298]">
            Collaborative review sessions and conferences
          </p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5]"
        >
          <Plus size={16} />
          Schedule Session
        </button>
      </div>

      {/* Loading */}
      {isLoading ? (
        <div className="flex items-center justify-center py-16">
          <Loader2 size={24} className="animate-spin text-[#4A5068]" />
        </div>
      ) : sessions.length === 0 ? (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-16">
          <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#16163A]">
            <Calendar size={24} className="text-[#7A8298]" />
          </div>
          <h3 className="text-lg font-semibold text-[#E8ECF4]">No sessions yet</h3>
          <p className="mt-2 text-sm text-[#7A8298]">
            Schedule your first collaborative session.
          </p>
          <button
            type="button"
            onClick={() => setShowForm(true)}
            className="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5]"
          >
            <Plus size={16} />
            Schedule Session
          </button>
        </div>
      ) : (
        <>
          {/* Upcoming / Live */}
          <div>
            <h2 className="mb-3 text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
              Upcoming &amp; Live
              <span className="ml-2 font-['IBM_Plex_Mono',monospace] text-[#4A5068]">
                ({upcoming.length})
              </span>
            </h2>
            {upcoming.length > 0 ? (
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {upcoming.map((s) => (
                  <SessionCard key={s.id} session={s} />
                ))}
              </div>
            ) : (
              <p className="rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-8 text-center text-sm text-[#4A5068]">
                No upcoming sessions
              </p>
            )}
          </div>

          {/* Past sessions */}
          {past.length > 0 && (
            <div>
              <button
                type="button"
                onClick={() => setShowPast(!showPast)}
                className="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-[#7A8298] transition-colors hover:text-[#B4BAC8]"
              >
                Past Sessions
                <span className="font-['IBM_Plex_Mono',monospace] text-[#4A5068]">
                  ({past.length})
                </span>
                {showPast ? <ChevronUp size={12} /> : <ChevronDown size={12} />}
              </button>
              {showPast && (
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                  {past.map((s) => (
                    <SessionCard key={s.id} session={s} />
                  ))}
                </div>
              )}
            </div>
          )}
        </>
      )}

      {/* Create session modal */}
      {showForm && (
        <SessionForm
          isPending={createSession.isPending}
          onSubmit={handleCreate}
          onClose={() => setShowForm(false)}
        />
      )}
    </div>
  );
}
