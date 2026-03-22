import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import {
  ArrowLeft, Loader2, Play, Square, Radio, Clock,
  Calendar, Users, Briefcase, Pencil,
} from "lucide-react";

import {
  useSession,
  useStartSession,
  useEndSession,
  useUpdateSession,
} from "../hooks/useSessions";
import { SessionForm } from "../components/SessionForm";
import type { SessionCase, SessionParticipant, UpdateSessionData } from "../types/session";

// ── Color maps ───────────────────────────────────────────────────────────────

const TYPE_COLORS: Record<string, string> = {
  tumor_board:       "#F0607A",
  mdc:               "#60A5FA",
  surgical_planning: "#2DD4BF",
  grand_rounds:      "#A78BFA",
  ad_hoc:            "#F59E0B",
};

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  scheduled: { bg: "#60A5FA15", text: "#60A5FA" },
  live:      { bg: "#2DD4BF15", text: "#2DD4BF" },
  completed: { bg: "#4A506815", text: "#4A5068" },
  cancelled: { bg: "#2A2A6015", text: "#4A5068" },
};

const PARTICIPANT_ROLE_COLORS: Record<string, string> = {
  moderator: "#F59E0B",
  presenter: "#2DD4BF",
  reviewer:  "#60A5FA",
  observer:  "#7A8298",
};

// ── Agenda panel ─────────────────────────────────────────────────────────────

function AgendaPanel({ sessionCases }: { sessionCases: SessionCase[] }) {
  const sorted = [...sessionCases].sort((a, b) => a.order - b.order);

  if (sorted.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-8">
        <Briefcase size={20} className="mb-2 text-[#4A5068]" />
        <p className="text-xs text-[#7A8298]">No cases on the agenda</p>
      </div>
    );
  }

  return (
    <div className="space-y-2">
      {sorted.map((sc, idx) => {
        const caseName = sc.clinical_case?.title ?? `Case #${sc.case_id}`;
        const specialty = sc.clinical_case?.specialty ?? "";
        const specialtyColor = specialty
          ? TYPE_COLORS[specialty] ?? "#7A8298"
          : "#7A8298";

        return (
          <div
            key={sc.id}
            className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#16163A] p-3"
          >
            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#1C1C48] font-['IBM_Plex_Mono',monospace] text-[10px] font-bold text-[#7A8298]">
              {idx + 1}
            </span>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-[#B4BAC8] truncate">
                {caseName}
              </p>
              <div className="flex items-center gap-2 text-[10px] text-[#4A5068]">
                {specialty && (
                  <span style={{ color: specialtyColor }}>
                    {specialty.replace(/_/g, " ")}
                  </span>
                )}
                <span>&middot;</span>
                <span className="font-['IBM_Plex_Mono',monospace]">
                  {sc.time_allotted_minutes}m
                </span>
                {sc.presenter && (
                  <>
                    <span>&middot;</span>
                    <span>{sc.presenter.name}</span>
                  </>
                )}
              </div>
            </div>
            <span
              className="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
              style={{
                backgroundColor: sc.status === "completed" ? "#2DD4BF15" : "#2A2A6020",
                color: sc.status === "completed" ? "#2DD4BF" : "#7A8298",
              }}
            >
              {sc.status}
            </span>
          </div>
        );
      })}
    </div>
  );
}

// ── Participants panel ───────────────────────────────────────────────────────

function ParticipantsPanel({
  participants,
}: {
  participants: SessionParticipant[];
}) {
  if (participants.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-8">
        <Users size={20} className="mb-2 text-[#4A5068]" />
        <p className="text-xs text-[#7A8298]">No participants yet</p>
      </div>
    );
  }

  return (
    <div className="space-y-2">
      {participants.map((p) => {
        const roleColor = PARTICIPANT_ROLE_COLORS[p.role] ?? "#7A8298";
        const initials = p.user?.name
          ? p.user.name
              .split(" ")
              .map((w) => w[0])
              .join("")
              .slice(0, 2)
              .toUpperCase()
          : "??";

        return (
          <div
            key={p.id}
            className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#16163A] p-3"
          >
            {p.user?.avatar ? (
              <img
                src={p.user.avatar}
                alt={p.user.name}
                className="h-7 w-7 rounded-full"
              />
            ) : (
              <div
                className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[9px] font-bold"
                style={{ backgroundColor: `${roleColor}15`, color: roleColor }}
              >
                {initials}
              </div>
            )}
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-[#B4BAC8] truncate">
                {p.user?.name ?? `User #${p.user_id}`}
              </p>
              <span
                className="text-[10px] font-medium capitalize"
                style={{ color: roleColor }}
              >
                {p.role}
              </span>
            </div>
            {p.joined_at && !p.left_at && (
              <span className="inline-flex items-center gap-1 text-[10px] text-[#2DD4BF]">
                <Radio size={8} className="animate-pulse" />
                Online
              </span>
            )}
          </div>
        );
      })}
    </div>
  );
}

// ── Elapsed time hook ────────────────────────────────────────────────────────

function useElapsedTime(startedAt: string | null) {
  const [elapsed, setElapsed] = useState("");

  useEffect(() => {
    if (!startedAt) {
      setElapsed("");
      return;
    }

    const update = () => {
      const diff = Date.now() - new Date(startedAt).getTime();
      const mins = Math.floor(diff / 60000);
      const secs = Math.floor((diff % 60000) / 1000);
      setElapsed(`${mins}:${secs.toString().padStart(2, "0")}`);
    };

    update();
    const interval = setInterval(update, 1000);
    return () => clearInterval(interval);
  }, [startedAt]);

  return elapsed;
}

// ── Main page ────────────────────────────────────────────────────────────────

export default function SessionDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const sessionId = parseInt(id ?? "0", 10);

  const { data: session, isLoading } = useSession(sessionId);
  const startSession = useStartSession();
  const endSession = useEndSession();
  const updateSession = useUpdateSession();

  const [showEditForm, setShowEditForm] = useState(false);

  const elapsed = useElapsedTime(session?.started_at ?? null);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={24} className="animate-spin text-[#4A5068]" />
      </div>
    );
  }

  if (!session) {
    return (
      <div className="flex flex-col items-center justify-center py-24">
        <h2 className="text-lg font-semibold text-[#E8ECF4]">Session not found</h2>
        <button
          type="button"
          onClick={() => navigate("/sessions")}
          className="mt-4 inline-flex items-center gap-2 rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm text-[#7A8298]"
        >
          <ArrowLeft size={14} />
          Back to Sessions
        </button>
      </div>
    );
  }

  const statusCfg = STATUS_COLORS[session.status] ?? { bg: "#2A2A6020", text: "#7A8298" };
  const typeColor = TYPE_COLORS[session.session_type] ?? "#7A8298";

  const scheduledDate = new Date(session.scheduled_at);
  const dateStr = scheduledDate.toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  });
  const timeStr = scheduledDate.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
  });

  const handleUpdate = (data: UpdateSessionData) => {
    updateSession.mutate(
      { id: sessionId, data },
      { onSuccess: () => setShowEditForm(false) },
    );
  };

  return (
    <div className="space-y-6">
      {/* Back link */}
      <button
        type="button"
        onClick={() => navigate("/sessions")}
        className="inline-flex items-center gap-1.5 text-xs text-[#4A5068] transition-colors hover:text-[#7A8298]"
      >
        <ArrowLeft size={12} />
        Back to Sessions
      </button>

      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <div className="mb-2 flex items-center gap-2">
            <span
              className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
              style={{ backgroundColor: `${typeColor}15`, color: typeColor }}
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
            {session.status === "live" && elapsed && (
              <span className="inline-flex items-center gap-1 font-['IBM_Plex_Mono',monospace] text-xs text-[#2DD4BF]">
                <Clock size={12} />
                {elapsed}
              </span>
            )}
          </div>

          <h1 className="text-2xl font-bold text-[#E8ECF4]">{session.title}</h1>
          {session.description && (
            <p className="mt-1 text-sm text-[#7A8298]">{session.description}</p>
          )}

          <div className="mt-2 flex items-center gap-4 text-xs text-[#4A5068]">
            <span className="inline-flex items-center gap-1">
              <Calendar size={12} />
              <span className="font-['IBM_Plex_Mono',monospace]">{dateStr}</span>
            </span>
            <span className="inline-flex items-center gap-1">
              <Clock size={12} />
              <span className="font-['IBM_Plex_Mono',monospace]">
                {timeStr} ({session.duration_minutes}m)
              </span>
            </span>
            {session.creator && (
              <span>Created by {session.creator.name}</span>
            )}
          </div>
        </div>

        {/* Action buttons */}
        <div className="flex items-center gap-2">
          {session.status === "scheduled" && (
            <>
              <button
                type="button"
                onClick={() => setShowEditForm(true)}
                className="inline-flex items-center gap-1.5 rounded-lg border border-[#222256] bg-[#10102A] px-3 py-2 text-sm text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8]"
              >
                <Pencil size={14} />
                Edit
              </button>
              <button
                type="button"
                onClick={() => startSession.mutate(sessionId)}
                disabled={startSession.isPending}
                className="inline-flex items-center gap-1.5 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
              >
                <Play size={14} />
                {startSession.isPending ? "Starting..." : "Start Session"}
              </button>
            </>
          )}
          {session.status === "live" && (
            <button
              type="button"
              onClick={() => endSession.mutate(sessionId)}
              disabled={endSession.isPending}
              className="inline-flex items-center gap-1.5 rounded-lg bg-[#00D68F] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#B52238] disabled:opacity-50"
            >
              <Square size={14} />
              {endSession.isPending ? "Ending..." : "End Session"}
            </button>
          )}
        </div>
      </div>

      {/* Two-column layout */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Agenda (2/3) */}
        <div className="lg:col-span-2 space-y-4">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
            Agenda
          </h2>
          <AgendaPanel sessionCases={session.session_cases ?? []} />

          {/* Session notes */}
          {session.notes && (
            <div className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-4">
              <h3 className="mb-2 text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
                Notes
              </h3>
              <p className="text-sm text-[#B4BAC8] whitespace-pre-wrap">
                {session.notes}
              </p>
            </div>
          )}
        </div>

        {/* Participants (1/3) */}
        <div className="space-y-4">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
            Participants
            <span className="ml-2 font-['IBM_Plex_Mono',monospace] text-[#4A5068]">
              ({(session.participants ?? []).length})
            </span>
          </h2>
          <ParticipantsPanel participants={session.participants ?? []} />
        </div>
      </div>

      {/* Edit modal */}
      {showEditForm && (
        <SessionForm
          session={session}
          isPending={updateSession.isPending}
          onSubmit={handleUpdate}
          onClose={() => setShowEditForm(false)}
        />
      )}
    </div>
  );
}
