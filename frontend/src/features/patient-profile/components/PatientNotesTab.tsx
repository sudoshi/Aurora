import { useState } from "react";
import { Loader2, FileText, ChevronLeft, ChevronRight, ChevronDown, ChevronUp, Calendar, Tag, User } from "lucide-react";
import { cn } from "@/lib/utils";
import { usePatientNotes } from "../hooks/useProfiles";
import type { ClinicalNote } from "../types/profile";
import { InlineActionMenu } from "./InlineActionMenu";

interface PatientNotesTabProps {
  patientId: number;
}

function NoteCard({ note, isExpanded, onToggle, patientId }: { note: ClinicalNote; isExpanded: boolean; onToggle: () => void; patientId: number }) {
  const previewLength = 300;
  const needsTruncation = note.content.length > previewLength;

  return (
    <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] overflow-hidden">
      <button
        type="button"
        onClick={onToggle}
        className="w-full flex items-start justify-between gap-3 px-4 py-3 hover:bg-[var(--surface-overlay)] transition-colors text-left"
      >
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm font-semibold text-[var(--text-primary)] truncate">
              {note.title || "Untitled Note"}
            </span>
            <span className="shrink-0 inline-flex items-center gap-1 rounded-full bg-[var(--info-bg)] px-2 py-0.5 text-[10px] font-medium text-[var(--info)]">
              <Tag size={9} />
              {note.note_type}
            </span>
            <span
              className="shrink-0"
              onClick={(e) => e.stopPropagation()}
              onKeyDown={(e) => e.stopPropagation()}
            >
              <InlineActionMenu
                recordRef={`general:${note.id}`}
                domain="general"
                patientId={patientId}
                onDiscuss={() => {}}
              />
            </span>
          </div>
          <div className="flex items-center gap-3 mt-1.5 text-[11px] text-[var(--text-muted)]">
            <span className="inline-flex items-center gap-1">
              <Calendar size={10} />
              {note.authored_at}
            </span>
            {note.author && (
              <span className="inline-flex items-center gap-1">
                <User size={10} />
                {note.author}
              </span>
            )}
            {note.visit_id && (
              <span className="text-[var(--text-ghost)]">
                Visit #{note.visit_id}
              </span>
            )}
          </div>
        </div>
        <div className="shrink-0 mt-0.5">
          {isExpanded ? (
            <ChevronUp size={14} className="text-[var(--text-ghost)]" />
          ) : (
            <ChevronDown size={14} className="text-[var(--text-ghost)]" />
          )}
        </div>
      </button>

      <div className="px-4 pb-3">
        <div className="rounded bg-[var(--surface-base)] border border-[var(--surface-overlay)] p-3">
          <pre className="text-xs text-[var(--text-secondary)] whitespace-pre-wrap font-mono leading-relaxed max-h-[600px] overflow-y-auto">
            {isExpanded || !needsTruncation
              ? note.content
              : note.content.slice(0, previewLength) + "..."}
          </pre>
        </div>
        {needsTruncation && !isExpanded && (
          <button
            type="button"
            onClick={onToggle}
            className="mt-2 text-[11px] text-[var(--accent)] hover:text-[var(--accent-light)] transition-colors"
          >
            Show full note ({Math.ceil(note.content.length / 1000)}k chars)
          </button>
        )}
      </div>
    </div>
  );
}

export function PatientNotesTab({ patientId }: PatientNotesTabProps) {
  const [page, setPage] = useState(1);
  const [expandedNoteId, setExpandedNoteId] = useState<number | null>(null);

  const { data, isLoading, error } = usePatientNotes(patientId, page);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 size={24} className="animate-spin text-[var(--text-muted)]" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center h-48 rounded-lg border border-dashed border-[var(--border-default)] bg-[var(--surface-raised)]">
        <FileText size={24} className="text-[var(--text-ghost)] mb-3" />
        <p className="text-sm text-[var(--critical)]">Failed to load clinical notes</p>
      </div>
    );
  }

  if (!data || data.data.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-48 rounded-lg border border-dashed border-[var(--border-default)] bg-[var(--surface-raised)]">
        <FileText size={24} className="text-[var(--text-ghost)] mb-3" />
        <p className="text-sm text-[var(--text-muted)]">No clinical notes available for this patient</p>
      </div>
    );
  }

  const { meta } = data;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <FileText size={14} className="text-[var(--info)]" />
          <span className="text-sm font-semibold text-[var(--text-primary)]">Clinical Notes</span>
          <span className="text-xs text-[var(--text-muted)]">({(meta.total ?? 0).toLocaleString()} total)</span>
        </div>
        {meta.last_page > 1 && (
          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
              className={cn(
                "inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs transition-colors",
                page <= 1
                  ? "border-[var(--surface-overlay)] text-[var(--text-disabled)] cursor-not-allowed"
                  : "border-[var(--border-default)] text-[var(--text-muted)] hover:text-[var(--text-primary)] hover:border-[var(--text-ghost)]",
              )}
            >
              <ChevronLeft size={12} />
              Prev
            </button>
            <span className="text-xs text-[var(--text-muted)]">
              {meta.current_page} / {meta.last_page}
            </span>
            <button
              type="button"
              disabled={page >= meta.last_page}
              onClick={() => setPage((p) => p + 1)}
              className={cn(
                "inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs transition-colors",
                page >= meta.last_page
                  ? "border-[var(--surface-overlay)] text-[var(--text-disabled)] cursor-not-allowed"
                  : "border-[var(--border-default)] text-[var(--text-muted)] hover:text-[var(--text-primary)] hover:border-[var(--text-ghost)]",
              )}
            >
              Next
              <ChevronRight size={12} />
            </button>
          </div>
        )}
      </div>

      <div className="space-y-3">
        {data.data.map((note) => (
          <NoteCard
            key={note.id}
            note={note}
            isExpanded={expandedNoteId === note.id}
            onToggle={() =>
              setExpandedNoteId((prev) => (prev === note.id ? null : note.id))
            }
            patientId={patientId}
          />
        ))}
      </div>
    </div>
  );
}
