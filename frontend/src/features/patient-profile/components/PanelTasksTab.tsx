import { useState } from 'react';
import type { PatientTask, FollowUp } from '../types/collaboration';
import { useCreateTask } from '../hooks/useCollaboration';

interface PanelTasksTabProps {
  tasks: PatientTask[];
  followUps: FollowUp[];
  patientId: number;
  onCompleteTask: (taskId: number) => void;
  onCompleteFollowUp: (followUpId: number) => void;
}

interface UnifiedItem {
  key: string;
  id: number;
  title: string;
  assigneeName: string | null;
  dueDate: string | null;
  isFollowUp: boolean;
  onComplete: () => void;
}

function isOverdue(dueDate: string | null): boolean {
  return !!dueDate && new Date(dueDate) < new Date();
}

function formatDue(iso: string): string {
  const d = new Date(iso);
  const diffDays = Math.round((d.getTime() - Date.now()) / 86_400_000);
  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return 'Tomorrow';
  if (diffDays === -1) return 'Yesterday';
  if (diffDays < 0) return `${Math.abs(diffDays)}d ago`;
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export function PanelTasksTab({
  tasks,
  followUps,
  patientId,
  onCompleteTask,
  onCompleteFollowUp,
}: PanelTasksTabProps) {
  const [newTitle, setNewTitle] = useState('');
  const createTask = useCreateTask(patientId);

  const pending = tasks.filter((t) => t.status === 'pending' || t.status === 'in_progress');
  const pendingFU = followUps.filter((f) => f.status === 'pending' || f.status === 'in_progress');

  const items: UnifiedItem[] = [
    ...pending.map((t): UnifiedItem => ({
      key: `t-${t.id}`,
      id: t.id,
      title: t.title,
      assigneeName: t.assignee?.name ?? null,
      dueDate: t.due_date,
      isFollowUp: false,
      onComplete: () => onCompleteTask(t.id),
    })),
    ...pendingFU.map((f): UnifiedItem => ({
      key: `fu-${f.id}`,
      id: f.id,
      title: f.title,
      assigneeName: f.assignee?.name ?? null,
      dueDate: f.due_date,
      isFollowUp: true,
      onComplete: () => onCompleteFollowUp(f.id),
    })),
  ].sort((a, b) => {
    if (!a.dueDate && !b.dueDate) return 0;
    if (!a.dueDate) return 1;
    if (!b.dueDate) return -1;
    return new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime();
  });

  function handleCreate() {
    const title = newTitle.trim();
    if (!title) return;
    createTask.mutate({ title });
    setNewTitle('');
  }

  return (
    <div className="flex flex-col h-full">
      {/* Task list */}
      <div className="flex-1 overflow-y-auto flex flex-col gap-1 px-3 py-2">
        {items.length === 0 && (
          <p className="text-xs text-[var(--text-ghost)] italic py-2">
            No pending tasks or follow-ups.
          </p>
        )}

        {items.map((item) => {
          const overdue = isOverdue(item.dueDate);
          return (
            <div
              key={item.key}
              className="flex items-start gap-2 rounded px-2 py-1.5 hover:bg-white/5 transition-colors group"
            >
              <button
                type="button"
                title="Mark complete"
                onClick={item.onComplete}
                className="mt-0.5 h-3.5 w-3.5 shrink-0 rounded border border-[var(--border-default)] hover:border-blue-400 hover:bg-blue-400/10 transition-colors"
              />
              <div className="flex-1 min-w-0">
                <p className="text-[12px] text-[var(--text-primary)] truncate">
                  {item.title}
                  {item.isFollowUp && (
                    <span className="ml-1.5 text-[10px] text-[var(--text-ghost)]">(decision)</span>
                  )}
                </p>
                {item.assigneeName && (
                  <span className="inline-block mt-0.5 rounded-full bg-white/8 px-1.5 py-px text-[10px] text-[var(--text-ghost)]">
                    {item.assigneeName}
                  </span>
                )}
              </div>
              {item.dueDate && (
                <span
                  className="shrink-0 text-[10px] font-medium"
                  style={{ color: overdue ? '#ef4444' : '#9ca3af' }}
                >
                  {formatDue(item.dueDate)}
                </span>
              )}
            </div>
          );
        })}
      </div>

      {/* New task form */}
      <div className="shrink-0 border-t border-[var(--border-default)] px-3 py-2 flex gap-2">
        <input
          type="text"
          value={newTitle}
          onChange={(e) => setNewTitle(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && handleCreate()}
          placeholder="New task..."
          className="flex-1 min-w-0 rounded px-2 py-1 text-[12px] bg-white/5 border border-[var(--border-default)] text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] outline-none focus:border-white/30"
        />
        <button
          type="button"
          disabled={!newTitle.trim() || createTask.isPending}
          onClick={handleCreate}
          className="shrink-0 rounded px-2 py-1 text-[11px] font-medium bg-blue-600/70 text-blue-100 hover:bg-blue-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          Add
        </button>
      </div>
    </div>
  );
}
