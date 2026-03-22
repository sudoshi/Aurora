import { useState, type FormEvent } from "react";
import { Reply, Send, MessageSquare } from "lucide-react";
import { cn } from "@/lib/utils";
import { useCaseDiscussions, useCreateDiscussion } from "../hooks/useCases";
import type { CaseDiscussion } from "../types/case";

// ── Single message ───────────────────────────────────────────────────────────

function DiscussionMessage({
  message,
  caseId,
  depth,
}: {
  message: CaseDiscussion;
  caseId: number;
  depth: number;
}) {
  const [replying, setReplying] = useState(false);
  const [replyContent, setReplyContent] = useState("");
  const createDiscussion = useCreateDiscussion();

  const handleReply = (e: FormEvent) => {
    e.preventDefault();
    if (!replyContent.trim()) return;

    createDiscussion.mutate(
      { caseId, content: replyContent.trim(), parentId: message.id },
      {
        onSuccess: () => {
          setReplyContent("");
          setReplying(false);
        },
      },
    );
  };

  const initials = message.user?.name
    ? message.user.name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .slice(0, 2)
        .toUpperCase()
    : "??";

  const formattedDate = new Date(message.created_at).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  return (
    <div className={cn(depth > 0 && "ml-6 border-l border-[#1C1C48] pl-4")}>
      <div className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-3">
        {/* Author row */}
        <div className="mb-2 flex items-center gap-2">
          {message.user?.avatar ? (
            <img
              src={message.user.avatar}
              alt={message.user.name}
              className="h-6 w-6 rounded-full"
            />
          ) : (
            <div
              className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[9px] font-bold"
              style={{ backgroundColor: "#2DD4BF15", color: "#2DD4BF" }}
            >
              {initials}
            </div>
          )}
          <span className="text-xs font-medium text-[#B4BAC8]">
            {message.user?.name ?? "Unknown"}
          </span>
          <span className="font-['IBM_Plex_Mono',monospace] text-[10px] text-[#4A5068]">
            {formattedDate}
          </span>
        </div>

        {/* Content */}
        <p className="text-sm text-[#B4BAC8] whitespace-pre-wrap">{message.content}</p>

        {/* Reply toggle */}
        {depth < 3 && (
          <button
            type="button"
            onClick={() => setReplying(!replying)}
            className="mt-2 inline-flex items-center gap-1 text-[10px] text-[#4A5068] transition-colors hover:text-[#2DD4BF]"
          >
            <Reply size={10} />
            Reply
          </button>
        )}

        {/* Inline reply form */}
        {replying && (
          <form onSubmit={handleReply} className="mt-2 flex gap-2">
            <input
              type="text"
              value={replyContent}
              onChange={(e) => setReplyContent(e.target.value)}
              placeholder="Write a reply..."
              className="flex-1 rounded-lg border border-[#1C1C48] bg-[#10102A] px-3 py-1.5 text-xs text-[#E8ECF4] placeholder:text-[#4A5068] focus:border-[#2DD4BF] focus:outline-none"
              autoFocus
            />
            <button
              type="submit"
              disabled={!replyContent.trim() || createDiscussion.isPending}
              className="flex h-7 w-7 items-center justify-center rounded-lg bg-[#2DD4BF] text-[#0A0A18] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
            >
              <Send size={12} />
            </button>
          </form>
        )}
      </div>

      {/* Nested replies */}
      {message.replies && message.replies.length > 0 && (
        <div className="mt-2 space-y-2">
          {message.replies.map((reply) => (
            <DiscussionMessage
              key={reply.id}
              message={reply}
              caseId={caseId}
              depth={depth + 1}
            />
          ))}
        </div>
      )}
    </div>
  );
}

// ── Main thread ──────────────────────────────────────────────────────────────

interface CaseDiscussionThreadProps {
  caseId: number;
}

export function CaseDiscussionThread({ caseId }: CaseDiscussionThreadProps) {
  const { data: discussions, isLoading } = useCaseDiscussions(caseId);
  const createDiscussion = useCreateDiscussion();
  const [newComment, setNewComment] = useState("");

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!newComment.trim()) return;

    createDiscussion.mutate(
      { caseId, content: newComment.trim() },
      { onSuccess: () => setNewComment("") },
    );
  };

  // Filter top-level messages (no parent)
  const topLevel = (discussions ?? []).filter((d) => d.parent_id === null);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <span className="text-sm text-[#4A5068]">Loading discussion...</span>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Messages */}
      {topLevel.length > 0 ? (
        <div className="space-y-3">
          {topLevel.map((msg) => (
            <DiscussionMessage key={msg.id} message={msg} caseId={caseId} depth={0} />
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
          <MessageSquare size={24} className="mb-2 text-[#4A5068]" />
          <p className="text-sm text-[#7A8298]">No discussion yet</p>
          <p className="mt-1 text-xs text-[#4A5068]">
            Start the conversation below.
          </p>
        </div>
      )}

      {/* New comment form */}
      <form
        onSubmit={handleSubmit}
        className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-4"
      >
        <label htmlFor="new-comment" className="mb-2 block text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
          Add Comment
        </label>
        <textarea
          id="new-comment"
          value={newComment}
          onChange={(e) => setNewComment(e.target.value)}
          placeholder="Share your thoughts or clinical input..."
          rows={3}
          className="form-input mb-3 resize-none"
        />
        <div className="flex justify-end">
          <button
            type="submit"
            disabled={!newComment.trim() || createDiscussion.isPending}
            className={cn(
              "inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors",
              "bg-[#2DD4BF] text-[#0A0A18] hover:bg-[#25B8A5] disabled:opacity-50",
            )}
          >
            <Send size={14} />
            {createDiscussion.isPending ? "Posting..." : "Post Comment"}
          </button>
        </div>
      </form>
    </div>
  );
}
