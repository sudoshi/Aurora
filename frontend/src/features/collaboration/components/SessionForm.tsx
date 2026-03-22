import { useState, type FormEvent } from "react";
import { X } from "lucide-react";

import type {
  Session,
  SessionType,
  CreateSessionData,
  UpdateSessionData,
} from "../types/session";

// ── Session type options ─────────────────────────────────────────────────────

const SESSION_TYPES: { value: SessionType; label: string }[] = [
  { value: "tumor_board", label: "Tumor Board" },
  { value: "mdc", label: "Multidisciplinary Conference" },
  { value: "surgical_planning", label: "Surgical Planning" },
  { value: "grand_rounds", label: "Grand Rounds" },
  { value: "ad_hoc", label: "Ad Hoc" },
];

// ── Component ────────────────────────────────────────────────────────────────

interface SessionFormProps {
  session?: Session | null;
  isPending?: boolean;
  onSubmit: (data: CreateSessionData | UpdateSessionData) => void;
  onClose: () => void;
}

export function SessionForm({
  session,
  isPending,
  onSubmit,
  onClose,
}: SessionFormProps) {
  const isEdit = !!session;

  const [title, setTitle] = useState(session?.title ?? "");
  const [description, setDescription] = useState(session?.description ?? "");
  const [sessionType, setSessionType] = useState<SessionType>(
    session?.session_type ?? "tumor_board",
  );
  const [scheduledAt, setScheduledAt] = useState(() => {
    if (session?.scheduled_at) {
      // Format for datetime-local input
      const d = new Date(session.scheduled_at);
      return d.toISOString().slice(0, 16);
    }
    return "";
  });
  const [duration, setDuration] = useState(
    session?.duration_minutes?.toString() ?? "60",
  );

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const data: CreateSessionData = {
      title: title.trim(),
      description: description.trim() || undefined,
      session_type: sessionType,
      scheduled_at: new Date(scheduledAt).toISOString(),
      duration_minutes: parseInt(duration, 10) || 60,
    };
    onSubmit(data);
  };

  const isValid = title.trim().length > 0 && scheduledAt.length > 0;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Panel */}
      <div className="relative z-10 w-full max-w-lg rounded-xl border border-[#232328] bg-[#1C1C20] shadow-xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-[#232328] px-5 py-4">
          <h2 className="text-base font-semibold text-[#F0EDE8]">
            {isEdit ? "Edit Session" : "Schedule Session"}
          </h2>
          <button
            type="button"
            onClick={onClose}
            className="flex h-7 w-7 items-center justify-center rounded-md text-[#5A5650] transition-colors hover:bg-[#2A2A30] hover:text-[#8A857D]"
          >
            <X size={16} />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4 px-5 py-4">
          {/* Title */}
          <div className="form-group">
            <label htmlFor="session-title" className="form-label">
              Title
            </label>
            <input
              id="session-title"
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="e.g., Weekly Tumor Board - Thoracic"
              className="form-input"
              required
            />
          </div>

          {/* Description */}
          <div className="form-group">
            <label htmlFor="session-desc" className="form-label">
              Description (optional)
            </label>
            <textarea
              id="session-desc"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Session description..."
              rows={2}
              className="form-input resize-none"
            />
          </div>

          {/* Type + Duration row */}
          <div className="grid grid-cols-2 gap-3">
            <div className="form-group">
              <label htmlFor="session-type" className="form-label">
                Session Type
              </label>
              <select
                id="session-type"
                value={sessionType}
                onChange={(e) => setSessionType(e.target.value as SessionType)}
                className="form-input"
              >
                {SESSION_TYPES.map((t) => (
                  <option key={t.value} value={t.value}>
                    {t.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="session-duration" className="form-label">
                Duration (minutes)
              </label>
              <input
                id="session-duration"
                type="number"
                value={duration}
                onChange={(e) => setDuration(e.target.value)}
                min={15}
                max={480}
                step={15}
                className="form-input"
              />
            </div>
          </div>

          {/* Date/Time */}
          <div className="form-group">
            <label htmlFor="session-datetime" className="form-label">
              Scheduled Date &amp; Time
            </label>
            <input
              id="session-datetime"
              type="datetime-local"
              value={scheduledAt}
              onChange={(e) => setScheduledAt(e.target.value)}
              className="form-input"
              required
            />
          </div>

          {/* Footer */}
          <div className="flex justify-end gap-3 border-t border-[#232328] pt-4">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-[#2A2A30] bg-[#151518] px-4 py-2 text-sm text-[#8A857D] transition-colors hover:border-[#3A3A42] hover:text-[#C5C0B8]"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!isValid || isPending}
              className="rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0E0E11] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
            >
              {isPending
                ? "Saving..."
                : isEdit
                  ? "Update Session"
                  : "Schedule Session"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
