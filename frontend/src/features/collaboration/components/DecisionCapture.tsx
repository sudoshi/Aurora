import { useState, type FormEvent } from "react";
import {
  ThumbsUp, ThumbsDown, Minus, CheckCircle, XCircle,
  Clock, Gavel, Plus, Send, ChevronDown, ChevronUp,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
  useCreateDecision,
  useCastVote,
  useFinalizeDecision,
  useCreateFollowUp,
} from "../hooks/useDecisions";
import type { Decision, DecisionType, VoteType, CreateDecisionData } from "../types/decision";

// ── Decision type options ────────────────────────────────────────────────────

const DECISION_TYPES: { value: DecisionType; label: string }[] = [
  { value: "treatment_recommendation", label: "Treatment Recommendation" },
  { value: "diagnostic_workup", label: "Diagnostic Workup" },
  { value: "referral", label: "Referral" },
  { value: "monitoring_plan", label: "Monitoring Plan" },
  { value: "palliative", label: "Palliative" },
  { value: "other", label: "Other" },
];

const STATUS_CONFIG: Record<string, { color: string; icon: typeof Clock }> = {
  proposed:     { color: "#F59E0B", icon: Clock },
  under_review: { color: "#60A5FA", icon: Clock },
  approved:     { color: "#2DD4BF", icon: CheckCircle },
  rejected:     { color: "#F0607A", icon: XCircle },
  deferred:     { color: "#7A8298", icon: Clock },
};

const VOTE_CONFIG: Record<VoteType, { label: string; color: string; icon: typeof ThumbsUp }> = {
  agree:   { label: "Agree", color: "#2DD4BF", icon: ThumbsUp },
  disagree: { label: "Disagree", color: "#F0607A", icon: ThumbsDown },
  abstain:  { label: "Abstain", color: "#7A8298", icon: Minus },
};

// ── Single decision card ─────────────────────────────────────────────────────

function DecisionCard({
  decision,
}: {
  decision: Decision;
  caseId: number;
}) {
  const castVote = useCastVote();
  const finalize = useFinalizeDecision();
  const createFollowUp = useCreateFollowUp();

  const [expanded, setExpanded] = useState(false);
  const [followUpTitle, setFollowUpTitle] = useState("");

  const statusCfg = STATUS_CONFIG[decision.status] ?? STATUS_CONFIG.proposed;
  const StatusIcon = statusCfg.icon;

  const summary = decision.votes_summary ?? { agree: 0, disagree: 0, abstain: 0 };
  const totalVotes = summary.agree + summary.disagree + summary.abstain;

  const handleVote = (vote: VoteType) => {
    castVote.mutate({ decisionId: decision.id, data: { vote } });
  };

  const handleAddFollowUp = (e: FormEvent) => {
    e.preventDefault();
    if (!followUpTitle.trim()) return;
    createFollowUp.mutate(
      { decisionId: decision.id, data: { title: followUpTitle.trim() } },
      { onSuccess: () => setFollowUpTitle("") },
    );
  };

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#16163A]">
      {/* Header */}
      <div className="flex items-start justify-between p-4">
        <div className="flex-1">
          <div className="mb-1 flex items-center gap-2">
            <StatusIcon size={12} style={{ color: statusCfg.color }} />
            <span
              className="text-[10px] font-semibold uppercase tracking-wider"
              style={{ color: statusCfg.color }}
            >
              {decision.status.replace(/_/g, " ")}
            </span>
            <span className="rounded bg-[#1C1C48] px-1.5 py-0.5 text-[10px] font-medium text-[#7A8298]">
              {decision.decision_type.replace(/_/g, " ")}
            </span>
          </div>
          <p className="text-sm font-medium text-[#B4BAC8]">
            {decision.recommendation}
          </p>
          {decision.proposer && (
            <p className="mt-1 text-[10px] text-[#4A5068]">
              Proposed by{" "}
              <span className="text-[#7A8298]">{decision.proposer.name}</span>
              {" "}&middot;{" "}
              <span className="font-['IBM_Plex_Mono',monospace]">
                {new Date(decision.created_at).toLocaleDateString("en-US", {
                  month: "short",
                  day: "numeric",
                })}
              </span>
            </p>
          )}
        </div>

        <button
          type="button"
          onClick={() => setExpanded(!expanded)}
          className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#1C1C48] hover:text-[#7A8298]"
        >
          {expanded ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
        </button>
      </div>

      {/* Voting bar */}
      <div className="flex items-center gap-4 border-t border-[#1C1C48] px-4 py-3">
        {(Object.entries(VOTE_CONFIG) as Array<[VoteType, typeof VOTE_CONFIG.agree]>).map(
          ([voteType, cfg]) => {
            const Icon = cfg.icon;
            const count = summary[voteType];
            return (
              <button
                key={voteType}
                type="button"
                onClick={() => handleVote(voteType)}
                disabled={castVote.isPending}
                className={cn(
                  "inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium transition-colors",
                  "border-[#1C1C48] hover:border-[#2A2A60]",
                )}
                style={{ color: cfg.color }}
              >
                <Icon size={12} />
                {cfg.label}
                {count > 0 && (
                  <span className="font-['IBM_Plex_Mono',monospace] text-[10px]">
                    {count}
                  </span>
                )}
              </button>
            );
          },
        )}

        {totalVotes > 0 && (
          <span className="ml-auto font-['IBM_Plex_Mono',monospace] text-[10px] text-[#4A5068]">
            {totalVotes} vote{totalVotes !== 1 ? "s" : ""}
          </span>
        )}

        {decision.status !== "approved" && decision.status !== "rejected" && (
          <button
            type="button"
            onClick={() => finalize.mutate(decision.id)}
            disabled={finalize.isPending}
            className="ml-auto inline-flex items-center gap-1 rounded-lg bg-[#2DD4BF]/10 px-2.5 py-1 text-xs font-semibold text-[#2DD4BF] transition-colors hover:bg-[#2DD4BF]/20"
          >
            <CheckCircle size={12} />
            Finalize
          </button>
        )}
      </div>

      {/* Expanded section */}
      {expanded && (
        <div className="border-t border-[#1C1C48] px-4 py-3 space-y-3">
          {decision.rationale && (
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
                Rationale
              </p>
              <p className="mt-1 text-xs text-[#7A8298]">{decision.rationale}</p>
            </div>
          )}
          {decision.guideline_reference && (
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
                Guideline Reference
              </p>
              <p className="mt-1 font-['IBM_Plex_Mono',monospace] text-xs text-[#2DD4BF]">
                {decision.guideline_reference}
              </p>
            </div>
          )}

          {/* Follow-ups */}
          {decision.follow_ups && decision.follow_ups.length > 0 && (
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider text-[#4A5068]">
                Follow-ups
              </p>
              <div className="mt-1 space-y-1">
                {decision.follow_ups.map((fu) => (
                  <div
                    key={fu.id}
                    className="flex items-center gap-2 rounded border border-[#1C1C48] bg-[#10102A] px-2 py-1"
                  >
                    <span
                      className={cn(
                        "h-1.5 w-1.5 rounded-full",
                        fu.status === "completed" ? "bg-[#2DD4BF]" : "bg-[#F59E0B]",
                      )}
                    />
                    <span className="flex-1 text-xs text-[#7A8298]">{fu.title}</span>
                    {fu.assignee && (
                      <span className="text-[10px] text-[#4A5068]">
                        {fu.assignee.name}
                      </span>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Add follow-up */}
          <form onSubmit={handleAddFollowUp} className="flex gap-2">
            <input
              type="text"
              value={followUpTitle}
              onChange={(e) => setFollowUpTitle(e.target.value)}
              placeholder="Add a follow-up task..."
              className="flex-1 rounded-lg border border-[#1C1C48] bg-[#10102A] px-3 py-1.5 text-xs text-[#E8ECF4] placeholder:text-[#4A5068] focus:border-[#2DD4BF] focus:outline-none"
            />
            <button
              type="submit"
              disabled={!followUpTitle.trim() || createFollowUp.isPending}
              className="flex h-7 w-7 items-center justify-center rounded-lg bg-[#2DD4BF] text-[#0A0A18] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
            >
              <Plus size={12} />
            </button>
          </form>
        </div>
      )}
    </div>
  );
}

// ── Propose decision form ────────────────────────────────────────────────────

function ProposeDecisionForm({
  caseId,
  sessionId,
  onClose,
}: {
  caseId: number;
  sessionId?: number;
  onClose: () => void;
}) {
  const createDecision = useCreateDecision();
  const [decisionType, setDecisionType] = useState<DecisionType>("treatment_recommendation");
  const [recommendation, setRecommendation] = useState("");
  const [rationale, setRationale] = useState("");
  const [guidelineRef, setGuidelineRef] = useState("");

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!recommendation.trim()) return;

    const data: CreateDecisionData = {
      case_id: caseId,
      session_id: sessionId,
      decision_type: decisionType,
      recommendation: recommendation.trim(),
      rationale: rationale.trim() || undefined,
      guideline_reference: guidelineRef.trim() || undefined,
    };

    createDecision.mutate(data, { onSuccess: () => onClose() });
  };

  return (
    <form onSubmit={handleSubmit} className="rounded-lg border border-[#1C1C48] bg-[#16163A] p-4 space-y-3">
      <h4 className="text-xs font-semibold uppercase tracking-wider text-[#7A8298]">
        Propose Decision
      </h4>

      <div className="form-group">
        <label htmlFor="decision-type" className="form-label">
          Type
        </label>
        <select
          id="decision-type"
          value={decisionType}
          onChange={(e) => setDecisionType(e.target.value as DecisionType)}
          className="form-input"
        >
          {DECISION_TYPES.map((t) => (
            <option key={t.value} value={t.value}>
              {t.label}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group">
        <label htmlFor="decision-rec" className="form-label">
          Recommendation
        </label>
        <textarea
          id="decision-rec"
          value={recommendation}
          onChange={(e) => setRecommendation(e.target.value)}
          placeholder="What is the recommendation?"
          rows={3}
          className="form-input resize-none"
          required
        />
      </div>

      <div className="form-group">
        <label htmlFor="decision-rationale" className="form-label">
          Rationale (optional)
        </label>
        <textarea
          id="decision-rationale"
          value={rationale}
          onChange={(e) => setRationale(e.target.value)}
          placeholder="Supporting rationale..."
          rows={2}
          className="form-input resize-none"
        />
      </div>

      <div className="form-group">
        <label htmlFor="decision-guideline" className="form-label">
          Guideline Reference (optional)
        </label>
        <input
          id="decision-guideline"
          type="text"
          value={guidelineRef}
          onChange={(e) => setGuidelineRef(e.target.value)}
          placeholder="e.g., NCCN Guidelines v2.2026"
          className="form-input"
        />
      </div>

      <div className="flex justify-end gap-3">
        <button
          type="button"
          onClick={onClose}
          className="rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8]"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={!recommendation.trim() || createDecision.isPending}
          className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
        >
          <Send size={14} />
          {createDecision.isPending ? "Proposing..." : "Propose"}
        </button>
      </div>
    </form>
  );
}

// ── Main component ───────────────────────────────────────────────────────────

interface DecisionCaptureProps {
  caseId: number;
  sessionId?: number;
  decisions: Decision[];
}

export function DecisionCapture({
  caseId,
  sessionId,
  decisions,
}: DecisionCaptureProps) {
  const [showForm, setShowForm] = useState(false);

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-[#B4BAC8]">
          Decisions
          <span className="ml-2 font-['IBM_Plex_Mono',monospace] text-xs text-[#4A5068]">
            ({decisions.length})
          </span>
        </h3>
        {!showForm && (
          <button
            type="button"
            onClick={() => setShowForm(true)}
            className="inline-flex items-center gap-1.5 rounded-lg bg-[#2DD4BF] px-3 py-1.5 text-xs font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5]"
          >
            <Gavel size={12} />
            Propose Decision
          </button>
        )}
      </div>

      {/* Propose form */}
      {showForm && (
        <ProposeDecisionForm
          caseId={caseId}
          sessionId={sessionId}
          onClose={() => setShowForm(false)}
        />
      )}

      {/* Decision list */}
      {decisions.length > 0 ? (
        <div className="space-y-3">
          {decisions.map((d) => (
            <DecisionCard key={d.id} decision={d} caseId={caseId} />
          ))}
        </div>
      ) : (
        !showForm && (
          <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
            <Gavel size={24} className="mb-2 text-[#4A5068]" />
            <p className="text-sm text-[#7A8298]">No decisions yet</p>
            <p className="mt-1 text-xs text-[#4A5068]">
              Propose a decision to start the consensus process.
            </p>
          </div>
        )
      )}
    </div>
  );
}
