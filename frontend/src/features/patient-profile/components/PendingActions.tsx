import type { PatientTask, FollowUp } from "../types/collaboration";

interface PendingActionsProps {
  tasks: PatientTask[];
  followUps: FollowUp[];
  onCompleteTask: (taskId: number) => void;
  onCompleteFollowUp: (followUpId: number) => void;
}

interface ActionItem {
  id: number;
  title: string;
  assigneeName: string | null;
  dueDate: string | null;
  isFollowUp: boolean;
  onComplete: () => void;
}

function isOverdue(dueDate: string | null): boolean {
  if (!dueDate) return false;
  return new Date(dueDate) < new Date();
}

function formatDueDate(iso: string): string {
  const d = new Date(iso);
  const now = new Date();
  const diffDays = Math.round((d.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return "Today";
  if (diffDays === 1) return "Tomorrow";
  if (diffDays === -1) return "Yesterday";
  if (diffDays < 0) return `${Math.abs(diffDays)}d ago`;
  return d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

export function PendingActions({
  tasks,
  followUps,
  onCompleteTask,
  onCompleteFollowUp,
}: PendingActionsProps) {
  const pendingTasks = tasks.filter(
    (t) => t.status === "pending" || t.status === "in_progress",
  );
  const pendingFollowUps = followUps.filter(
    (f) => f.status === "pending" || f.status === "in_progress",
  );

  const items: ActionItem[] = [
    ...pendingTasks.map((t): ActionItem => ({
      id: t.id,
      title: t.title,
      assigneeName: t.assignee?.name ?? null,
      dueDate: t.due_date,
      isFollowUp: false,
      onComplete: () => onCompleteTask(t.id),
    })),
    ...pendingFollowUps.map((f): ActionItem => ({
      id: f.id,
      title: f.title,
      assigneeName: f.assignee?.name ?? null,
      dueDate: f.due_date,
      isFollowUp: true,
      onComplete: () => onCompleteFollowUp(f.id),
    })),
  ].sort((a, b) => {
    const aOverdue = isOverdue(a.dueDate);
    const bOverdue = isOverdue(b.dueDate);
    if (aOverdue && !bOverdue) return -1;
    if (!aOverdue && bOverdue) return 1;
    if (!a.dueDate && !b.dueDate) return 0;
    if (!a.dueDate) return 1;
    if (!b.dueDate) return -1;
    return new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime();
  });

  return (
    <div className="flex flex-col gap-1">
      <p
        className="text-[11px] font-semibold uppercase tracking-wide mb-2"
        style={{ color: "#60a5fa" }}
      >
        Pending Actions
      </p>

      {items.length === 0 && (
        <p className="text-xs text-[var(--text-ghost)] italic py-1">
          No pending tasks or follow-ups.
        </p>
      )}

      <div className="flex flex-col gap-1">
        {items.map((item) => {
          const overdue = isOverdue(item.dueDate);
          return (
            <div
              key={`${item.isFollowUp ? "fu" : "t"}-${item.id}`}
              className="flex items-start gap-2 rounded px-2 py-1.5 hover:bg-white/5 transition-colors group"
            >
              {/* Checkbox */}
              <button
                type="button"
                title="Mark complete"
                onClick={item.onComplete}
                className="mt-0.5 h-3.5 w-3.5 shrink-0 rounded border border-[var(--border-default)] hover:border-blue-400 hover:bg-blue-400/10 transition-colors flex items-center justify-center"
              >
                <span className="sr-only">Complete</span>
              </button>

              {/* Title + suffix */}
              <div className="flex-1 min-w-0">
                <p className="text-xs text-[var(--text-primary)] truncate">
                  {item.title}
                  {item.isFollowUp && (
                    <span className="text-[var(--text-ghost)] ml-1 text-[10px]">
                      (from decision)
                    </span>
                  )}
                </p>
                {item.assigneeName && (
                  <p className="text-[10px] text-[var(--text-ghost)] truncate mt-0.5">
                    {item.assigneeName}
                  </p>
                )}
              </div>

              {/* Due date */}
              {item.dueDate && (
                <span
                  className="shrink-0 text-[10px] font-medium"
                  style={{ color: overdue ? "#ef4444" : "#9ca3af" }}
                >
                  {formatDueDate(item.dueDate)}
                </span>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
