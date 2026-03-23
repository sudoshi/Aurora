import { useState } from 'react';
import type { AnchoredDiscussion, ClinicalDomain } from '../types/collaboration';

interface PanelDiscussionTabProps {
  discussions: AnchoredDiscussion[];
  patientId: number;
  domain?: ClinicalDomain;
}

const DOMAIN_LABELS: Record<ClinicalDomain, string> = {
  condition: 'Conditions',
  medication: 'Medications',
  procedure: 'Procedures',
  measurement: 'Labs',
  observation: 'Observations',
  genomic: 'Genomics',
  imaging: 'Imaging',
  general: 'General',
};

function relativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60_000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  return `${Math.floor(hrs / 24)}d ago`;
}

export function PanelDiscussionTab({ discussions, domain }: PanelDiscussionTabProps) {
  const [draft, setDraft] = useState('');
  const placeholder = domain
    ? `New thread about ${DOMAIN_LABELS[domain]}...`
    : 'New thread...';

  const threads = discussions.filter((d) => d.parent_id == null);

  return (
    <div className="flex flex-col h-full">
      {/* Thread list */}
      <div className="flex-1 overflow-y-auto flex flex-col gap-2 px-3 py-2">
        {threads.length === 0 && (
          <p className="text-xs text-[var(--text-ghost)] italic py-2">
            No discussion threads yet.
          </p>
        )}

        {threads.map((thread) => (
          <div
            key={thread.id}
            className="rounded-md px-2.5 py-2 bg-white/5 border border-white/8 hover:border-white/15 transition-colors"
          >
            {/* Header */}
            <div className="flex items-center gap-1.5 mb-1">
              <span className="text-[11px] font-medium text-[var(--text-primary)] truncate">
                {thread.user?.name ?? 'Unknown'}
              </span>
              <span className="text-[10px] text-[var(--text-ghost)] shrink-0 ml-auto">
                {relativeTime(thread.created_at)}
              </span>
            </div>

            {/* Content preview */}
            <p className="text-[12px] text-[var(--text-secondary)] leading-snug line-clamp-3">
              {thread.content.length > 200
                ? thread.content.slice(0, 200) + '…'
                : thread.content}
            </p>

            {/* Reply count */}
            {(thread.replies?.length ?? 0) > 0 && (
              <p className="text-[10px] text-[var(--text-ghost)] mt-1.5">
                {thread.replies!.length} {thread.replies!.length === 1 ? 'reply' : 'replies'}
              </p>
            )}
          </div>
        ))}
      </div>

      {/* Quick-compose form (UI only — creating requires case context) */}
      <div className="shrink-0 border-t border-[var(--border-default)] px-3 py-2 flex gap-2">
        <input
          type="text"
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          placeholder={placeholder}
          className="flex-1 min-w-0 rounded px-2 py-1 text-[12px] bg-white/5 border border-[var(--border-default)] text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] outline-none focus:border-white/30"
        />
        <button
          type="button"
          disabled={!draft.trim()}
          className="shrink-0 rounded px-2 py-1 text-[11px] font-medium bg-blue-600/70 text-blue-100 hover:bg-blue-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          Post
        </button>
      </div>
    </div>
  );
}
