import { useState } from "react";
import { CheckCircle2, XCircle, BookOpen, Send } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Skeleton } from "@/components/ui/Skeleton";
import { EmptyState } from "@/components/ui/EmptyState";
import { useGuidelineCheck } from "../hooks/useDecisionSupport";
import type { ConcordanceResult, PatientContext } from "../types/decision-support";

// ---------------------------------------------------------------------------
// Result display
// ---------------------------------------------------------------------------

function ConcordanceDisplay({ result }: { result: ConcordanceResult }) {
  const concordant = result.concordant;

  return (
    <div className="space-y-4">
      {/* Banner */}
      <div
        className="flex items-center gap-3 rounded-lg p-4"
        style={{
          backgroundColor: concordant ? "#2DD4BF10" : "#F0607A10",
          border: `1px solid ${concordant ? "#2DD4BF30" : "#F0607A30"}`,
        }}
      >
        {concordant ? (
          <CheckCircle2 size={20} className="text-[#2DD4BF] shrink-0" />
        ) : (
          <XCircle size={20} className="text-[#F0607A] shrink-0" />
        )}
        <div>
          <p
            className="text-sm font-semibold"
            style={{ color: concordant ? "#2DD4BF" : "#F0607A" }}
          >
            {concordant ? "Concordant with Guidelines" : "Non-Concordant"}
          </p>
          <p className="text-xs text-[var(--text-muted)] mt-0.5">
            {result.guideline_referenced}
          </p>
        </div>
        <span
          className="ml-auto text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase shrink-0"
          style={{
            color: concordant ? "#2DD4BF" : "#F0607A",
            backgroundColor: concordant ? "#2DD4BF15" : "#F0607A15",
            border: `1px solid ${concordant ? "#2DD4BF30" : "#F0607A30"}`,
          }}
        >
          {result.confidence}
        </span>
      </div>

      {/* Supporting evidence */}
      {result.supporting_evidence.length > 0 && (
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1.5">
            Supporting Evidence
          </p>
          <ul className="space-y-1">
            {result.supporting_evidence.map((e, i) => (
              <li key={i} className="text-xs text-[var(--text-secondary)] flex items-start gap-2">
                <span className="text-[#2DD4BF] mt-0.5 shrink-0">&#8226;</span>
                {e}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Concerns */}
      {result.concerns.length > 0 && (
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1.5">
            Concerns
          </p>
          <ul className="space-y-1">
            {result.concerns.map((c, i) => (
              <li key={i} className="text-xs text-[#F0607A] flex items-start gap-2">
                <span className="mt-0.5 shrink-0">&#8226;</span>
                {c}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Alternatives */}
      {result.alternative_recommendations.length > 0 && (
        <div>
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1.5">
            Alternative Recommendations
          </p>
          <div className="flex flex-wrap gap-1">
            {result.alternative_recommendations.map((a) => (
              <Badge key={a} variant="accent" className="text-[10px]">
                {a}
              </Badge>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// GuidelineConcordance
// ---------------------------------------------------------------------------

interface GuidelineConcordanceProps {
  patientContext: PatientContext | null;
}

export function GuidelineConcordance({ patientContext }: GuidelineConcordanceProps) {
  const [recommendation, setRecommendation] = useState("");
  const mutation = useGuidelineCheck();

  const handleCheck = () => {
    if (!patientContext || recommendation.trim().length === 0) return;
    mutation.mutate({ recommendation: recommendation.trim(), patientContext });
  };

  return (
    <div className="space-y-4">
      {/* Input */}
      <div className="space-y-2">
        <label className="text-xs font-medium text-[var(--text-muted)] uppercase tracking-wider">
          Clinical Recommendation
        </label>
        <div className="flex gap-2">
          <textarea
            value={recommendation}
            onChange={(e) => setRecommendation(e.target.value)}
            placeholder="Enter a clinical recommendation to check against guidelines..."
            className="flex-1 rounded-lg border border-[var(--border-default)] bg-[var(--surface-base)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-ghost)] focus:outline-none focus:border-[#2DD4BF]/50 resize-none"
            rows={3}
          />
        </div>
        <Button
          variant="primary"
          size="sm"
          onClick={handleCheck}
          disabled={
            !patientContext ||
            recommendation.trim().length === 0 ||
            mutation.isPending
          }
        >
          <Send size={12} className="mr-1.5" />
          {mutation.isPending ? "Checking..." : "Check Guidelines"}
        </Button>
      </div>

      {/* Loading */}
      {mutation.isPending && (
        <div className="space-y-2">
          <Skeleton variant="card" height="80px" />
          <Skeleton variant="text" count={3} />
        </div>
      )}

      {/* Error */}
      {mutation.isError && (
        <div className="rounded-lg border border-[#F0607A]/20 bg-[#F0607A]/5 p-4 text-center">
          <p className="text-sm text-[#F0607A]">Failed to check guidelines</p>
          <p className="text-xs text-[var(--text-muted)] mt-1">Please try again.</p>
        </div>
      )}

      {/* Result */}
      {mutation.data && <ConcordanceDisplay result={mutation.data} />}

      {/* Empty prompt */}
      {!mutation.data && !mutation.isPending && !mutation.isError && (
        <EmptyState
          icon={<BookOpen size={32} className="text-[var(--text-ghost)]" />}
          title="Guideline Concordance Check"
          message="Enter a clinical recommendation above to check it against established guidelines for this patient."
        />
      )}
    </div>
  );
}
