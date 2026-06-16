import { useState } from "react";
import { Loader2, Sparkles, CheckCircle, ExternalLink, AlertCircle } from "lucide-react";
import { useDraftDecision, useRecordDecision } from "../hooks/useDecisionDraft";
import type { DecisionDraft } from "../types/decisionDraft";

const DECISION_TYPES: { value: string; label: string }[] = [
  { value: "treatment_recommendation", label: "Treatment Recommendation" },
  { value: "diagnostic_workup", label: "Diagnostic Workup" },
  { value: "referral", label: "Referral" },
  { value: "monitoring_plan", label: "Monitoring Plan" },
  { value: "palliative", label: "Palliative" },
  { value: "other", label: "Other" },
];

interface Props {
  caseId: number;
}

export function AbbyDecisionDraft({ caseId }: Props) {
  const draftMutation = useDraftDecision(caseId);
  const recordMutation = useRecordDecision(caseId);

  const [draft, setDraft] = useState<DecisionDraft | null>(null);
  const [decisionType, setDecisionType] = useState("");
  const [recommendation, setRecommendation] = useState("");
  const [rationale, setRationale] = useState("");
  const [recorded, setRecorded] = useState(false);

  const handleDraft = () => {
    draftMutation.mutate(undefined, {
      onSuccess: (result) => {
        setDraft(result);
        setDecisionType(result.decision_type);
        setRecommendation(result.recommendation);
        setRationale(result.rationale);
      },
    });
  };

  const handleRecord = () => {
    if (!draft) return;
    recordMutation.mutate(
      {
        decision_type: decisionType,
        recommendation,
        rationale,
        ai_generated: true,
        ai_model: draft.model,
        ai_confidence: draft.confidence,
        ai_sources: draft.sources,
      },
      {
        onSuccess: () => setRecorded(true),
      },
    );
  };

  if (recorded) {
    return (
      <div className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <div className="flex items-center gap-2">
          <CheckCircle size={16} className="text-[var(--teal)]" />
          <span className="text-sm font-medium text-[var(--text-primary)]">
            Decision recorded successfully.
          </span>
        </div>
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4 space-y-4">
      {/* Header row */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Sparkles size={14} className="text-[var(--accent)]" />
          <span className="text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">
            Draft with Abby
          </span>
        </div>
        {!draft && (
          <button
            type="button"
            onClick={handleDraft}
            disabled={draftMutation.isPending}
            className="inline-flex items-center gap-1.5 rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-raised)] px-3 py-1.5 text-xs font-medium text-[var(--text-primary)] transition-colors hover:border-[var(--teal)] hover:text-[var(--teal)] disabled:opacity-50"
          >
            {draftMutation.isPending ? (
              <>
                <Loader2 size={12} className="animate-spin" />
                Drafting…
              </>
            ) : (
              <>
                <Sparkles size={12} />
                Draft with Abby
              </>
            )}
          </button>
        )}
      </div>

      {/* Error state */}
      {draftMutation.isError && (
        <div className="flex items-center gap-2 rounded-md border border-[#F0607A]/20 bg-[#F0607A]/5 p-2">
          <AlertCircle size={12} className="text-[#F0607A]" />
          <span className="text-xs text-[#F0607A]">
            Failed to generate draft. Please try again.
          </span>
        </div>
      )}

      {/* Draft form */}
      {draft && (
        <div className="space-y-4">
          {/* Disclaimer */}
          <div className="flex items-start gap-2 rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-2">
            <AlertCircle size={12} className="mt-0.5 shrink-0 text-[var(--text-muted)]" />
            <p className="text-[11px] text-[var(--text-muted)]">
              AI-drafted — review before recording. Non-device decision support.
            </p>
          </div>

          {/* Confidence chip */}
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">
              Confidence
            </span>
            <span
              className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
              style={{
                backgroundColor: `color-mix(in srgb, var(--teal) 15%, transparent)`,
                color: "var(--teal)",
              }}
            >
              {Math.round(draft.confidence * 100)}%
            </span>
          </div>

          {/* Decision type */}
          <div className="form-group">
            <label htmlFor="abby-decision-type" className="form-label">
              Decision Type
            </label>
            <select
              id="abby-decision-type"
              value={decisionType}
              onChange={(e) => setDecisionType(e.target.value)}
              className="form-input"
            >
              {DECISION_TYPES.map((dt) => (
                <option key={dt.value} value={dt.value}>
                  {dt.label}
                </option>
              ))}
            </select>
          </div>

          {/* Recommendation */}
          <div className="form-group">
            <label htmlFor="abby-recommendation" className="form-label">
              Recommendation
            </label>
            <textarea
              id="abby-recommendation"
              rows={3}
              value={recommendation}
              onChange={(e) => setRecommendation(e.target.value)}
              className="form-input resize-y"
            />
          </div>

          {/* Rationale */}
          <div className="form-group">
            <label htmlFor="abby-rationale" className="form-label">
              Rationale
            </label>
            <textarea
              id="abby-rationale"
              rows={4}
              value={rationale}
              onChange={(e) => setRationale(e.target.value)}
              className="form-input resize-y"
            />
          </div>

          {/* Guideline references */}
          {draft.guideline_references.length > 0 && (
            <div>
              <span className="text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">
                Guideline References
              </span>
              <ul className="mt-1 space-y-0.5">
                {draft.guideline_references.map((ref) => (
                  <li key={ref} className="text-xs text-[var(--text-secondary)]">
                    {ref}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Evidence sources */}
          {draft.sources.length > 0 && (
            <div>
              <div className="mb-1 flex items-center gap-3">
                <span className="text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">
                  Evidence
                </span>
                <span className="text-[10px] text-[var(--text-muted)]">
                  {draft.evidence_counts.articles > 0 && `${draft.evidence_counts.articles} article${draft.evidence_counts.articles !== 1 ? "s" : ""}`}
                  {draft.evidence_counts.trials > 0 && ` · ${draft.evidence_counts.trials} trial${draft.evidence_counts.trials !== 1 ? "s" : ""}`}
                  {draft.evidence_counts.variants > 0 && ` · ${draft.evidence_counts.variants} variant${draft.evidence_counts.variants !== 1 ? "s" : ""}`}
                </span>
              </div>
              <ul className="space-y-1">
                {draft.sources.map((src) => (
                  <li key={src.id} className="flex items-center gap-1.5">
                    <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-[10px] font-medium uppercase text-[var(--text-muted)]">
                      {src.type}
                    </span>
                    <a
                      href={src.url}
                      target="_blank"
                      rel="noreferrer"
                      className="flex items-center gap-1 text-xs text-[var(--teal)] hover:underline"
                    >
                      {src.title}
                      <ExternalLink size={10} />
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Record error */}
          {recordMutation.isError && (
            <div className="flex items-center gap-2 rounded-md border border-[#F0607A]/20 bg-[#F0607A]/5 p-2">
              <AlertCircle size={12} className="text-[#F0607A]" />
              <span className="text-xs text-[#F0607A]">
                Failed to record decision. Please try again.
              </span>
            </div>
          )}

          {/* Actions */}
          <div className="flex items-center justify-between gap-3 pt-1">
            <button
              type="button"
              onClick={handleDraft}
              disabled={draftMutation.isPending || recordMutation.isPending}
              className="text-xs text-[var(--text-muted)] hover:text-[var(--text-secondary)] disabled:opacity-50"
            >
              Re-draft
            </button>
            <button
              type="button"
              onClick={handleRecord}
              disabled={recordMutation.isPending || !recommendation.trim()}
              className="inline-flex items-center gap-1.5 rounded-md bg-[var(--teal)] px-4 py-1.5 text-xs font-semibold text-[#0A0A18] transition-colors hover:opacity-90 disabled:opacity-50"
            >
              {recordMutation.isPending ? (
                <>
                  <Loader2 size={12} className="animate-spin" />
                  Recording…
                </>
              ) : (
                "Confirm & record"
              )}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
