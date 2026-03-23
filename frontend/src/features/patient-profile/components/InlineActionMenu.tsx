/**
 * InlineActionMenu — context menu for data rows that lets clinicians
 * flag, discuss, create tasks, or annotate individual data points.
 */
import { useState, useRef, useEffect } from "react";
import {
  MoreVertical,
  Flag,
  MessageSquare,
  CheckSquare,
  PenLine,
} from "lucide-react";
import { useCreateFlag, useCreateTask } from "../hooks/useCollaboration";
import type { ClinicalDomain, FlagSeverity } from "../types/collaboration";

interface InlineActionMenuProps {
  recordRef: string;
  domain: ClinicalDomain;
  patientId: number;
  onFlag?: () => void;
  onTask?: () => void;
  onDiscuss?: () => void;
}

type ActiveForm = null | "flag" | "task";

export function InlineActionMenu({
  recordRef,
  domain,
  patientId,
  onFlag,
  onTask,
  onDiscuss,
}: InlineActionMenuProps) {
  const [open, setOpen] = useState(false);
  const [activeForm, setActiveForm] = useState<ActiveForm>(null);
  const [flagTitle, setFlagTitle] = useState("");
  const [flagSeverity, setFlagSeverity] = useState<FlagSeverity>("attention");
  const [taskTitle, setTaskTitle] = useState("");

  const menuRef = useRef<HTMLDivElement>(null);

  const createFlag = useCreateFlag(patientId);
  const createTask = useCreateTask(patientId);

  // Close on outside click
  useEffect(() => {
    if (!open) return;
    function handleClick(e: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setOpen(false);
        setActiveForm(null);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, [open]);

  function closeAll() {
    setOpen(false);
    setActiveForm(null);
    setFlagTitle("");
    setTaskTitle("");
    setFlagSeverity("attention");
  }

  function handleFlagSubmit() {
    if (!flagTitle.trim()) return;
    createFlag.mutate(
      { domain, record_ref: recordRef, severity: flagSeverity, title: flagTitle.trim() },
      {
        onSuccess: () => {
          closeAll();
          onFlag?.();
        },
      },
    );
  }

  function handleTaskSubmit() {
    if (!taskTitle.trim()) return;
    createTask.mutate(
      { title: taskTitle.trim(), domain, record_ref: recordRef },
      {
        onSuccess: () => {
          closeAll();
          onTask?.();
        },
      },
    );
  }

  return (
    <div className="relative inline-block" ref={menuRef}>
      {/* Trigger */}
      <button
        type="button"
        aria-label="Row actions"
        onClick={() => {
          setOpen((v) => !v);
          setActiveForm(null);
        }}
        className="flex items-center justify-center w-6 h-6 rounded text-[#4A5068] hover:text-[#B4BAC8] hover:bg-[#1E2235] transition-colors"
      >
        <MoreVertical size={14} />
      </button>

      {/* Dropdown */}
      {open && (
        <div
          className="absolute right-0 top-7 z-50 min-w-[200px] rounded-lg border border-[#2A2F45] bg-[#161929] shadow-xl"
          style={{ boxShadow: "0 8px 24px rgba(0,0,0,0.4)" }}
        >
          {/* Flag for review */}
          <div>
            <button
              type="button"
              onClick={() =>
                setActiveForm(activeForm === "flag" ? null : "flag")
              }
              className="flex w-full items-center gap-2.5 px-3 py-2 text-[12px] text-[#B4BAC8] hover:bg-[#1E2235] hover:text-[#E8ECF4] transition-colors rounded-t-lg"
            >
              <Flag size={12} className="text-[#F0607A] shrink-0" />
              Flag for review
            </button>

            {activeForm === "flag" && (
              <div className="px-3 pb-3 space-y-1.5 border-t border-[#2A2F45]">
                <input
                  type="text"
                  placeholder="Flag title…"
                  value={flagTitle}
                  onChange={(e) => setFlagTitle(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && handleFlagSubmit()}
                  autoFocus
                  className="mt-2 w-full rounded border border-[#2A2F45] bg-[#0E1120] px-2 py-1 text-[12px] text-[#E8ECF4] placeholder-[#4A5068] focus:border-[#A78BFA]/50 focus:outline-none"
                />
                <select
                  value={flagSeverity}
                  onChange={(e) =>
                    setFlagSeverity(e.target.value as FlagSeverity)
                  }
                  className="w-full rounded border border-[#2A2F45] bg-[#0E1120] px-2 py-1 text-[12px] text-[#B4BAC8] focus:border-[#A78BFA]/50 focus:outline-none"
                >
                  <option value="critical">Critical</option>
                  <option value="attention">Attention</option>
                  <option value="informational">Informational</option>
                </select>
                <button
                  type="button"
                  disabled={!flagTitle.trim() || createFlag.isPending}
                  onClick={handleFlagSubmit}
                  className="w-full rounded bg-[#F0607A]/80 px-2 py-1 text-[11px] font-medium text-white hover:bg-[#F0607A] disabled:opacity-40 transition-colors"
                >
                  {createFlag.isPending ? "Saving…" : "Submit Flag"}
                </button>
              </div>
            )}
          </div>

          {/* Add to discussion */}
          <button
            type="button"
            onClick={() => {
              closeAll();
              onDiscuss?.();
            }}
            className="flex w-full items-center gap-2.5 px-3 py-2 text-[12px] text-[#B4BAC8] hover:bg-[#1E2235] hover:text-[#E8ECF4] transition-colors"
          >
            <MessageSquare size={12} className="text-[#60A5FA] shrink-0" />
            Add to discussion
          </button>

          {/* Create task */}
          <div>
            <button
              type="button"
              onClick={() =>
                setActiveForm(activeForm === "task" ? null : "task")
              }
              className="flex w-full items-center gap-2.5 px-3 py-2 text-[12px] text-[#B4BAC8] hover:bg-[#1E2235] hover:text-[#E8ECF4] transition-colors"
            >
              <CheckSquare size={12} className="text-[#2DD4BF] shrink-0" />
              Create task
            </button>

            {activeForm === "task" && (
              <div className="px-3 pb-3 space-y-1.5 border-t border-[#2A2F45]">
                <input
                  type="text"
                  placeholder="Task title…"
                  value={taskTitle}
                  onChange={(e) => setTaskTitle(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && handleTaskSubmit()}
                  autoFocus
                  className="mt-2 w-full rounded border border-[#2A2F45] bg-[#0E1120] px-2 py-1 text-[12px] text-[#E8ECF4] placeholder-[#4A5068] focus:border-[#A78BFA]/50 focus:outline-none"
                />
                <button
                  type="button"
                  disabled={!taskTitle.trim() || createTask.isPending}
                  onClick={handleTaskSubmit}
                  className="w-full rounded bg-[#2DD4BF]/80 px-2 py-1 text-[11px] font-medium text-[#0E1120] hover:bg-[#2DD4BF] disabled:opacity-40 transition-colors"
                >
                  {createTask.isPending ? "Saving…" : "Create Task"}
                </button>
              </div>
            )}
          </div>

          {/* Annotate */}
          <button
            type="button"
            onClick={() => {
              closeAll();
              onDiscuss?.();
            }}
            className="flex w-full items-center gap-2.5 px-3 py-2 text-[12px] text-[#B4BAC8] hover:bg-[#1E2235] hover:text-[#E8ECF4] transition-colors rounded-b-lg"
          >
            <PenLine size={12} className="text-[#A78BFA] shrink-0" />
            Annotate
          </button>
        </div>
      )}
    </div>
  );
}
