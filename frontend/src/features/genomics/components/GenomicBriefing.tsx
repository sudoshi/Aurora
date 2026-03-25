import { useState, useEffect } from "react";
import { Brain, RefreshCw, Loader2 } from "lucide-react";
import { useGenomicBriefing } from "../hooks/useGenomics";
import type {
  GenomicBriefingRequest,
  GenomicBriefingResponse,
} from "../types";

interface GenomicBriefingProps {
  briefingData: GenomicBriefingRequest;
}

export function GenomicBriefing({ briefingData }: GenomicBriefingProps) {
  const briefingMutation = useGenomicBriefing();
  const [response, setResponse] = useState<GenomicBriefingResponse | null>(null);

  // Auto-generate on mount if we have variants
  useEffect(() => {
    if (briefingData.variants.length > 0 && !response) {
      briefingMutation.mutate(briefingData, {
        onSuccess: (data) => setResponse(data),
      });
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const handleRegenerate = () => {
    briefingMutation.mutate(briefingData, {
      onSuccess: (data) => setResponse(data),
    });
  };

  const isLoading = briefingMutation.isPending;
  const briefing = response;

  return (
    <div className="rounded-lg border border-[#A78BFA]/30 bg-[#A78BFA]/5 p-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <div className="flex items-center justify-center w-7 h-7 rounded-full bg-[#A78BFA]/15">
            <Brain size={14} className="text-[#A78BFA]" />
          </div>
          <h3 className="text-sm font-semibold text-[#E8ECF4]">Genomic Summary</h3>
          <span className="text-[10px] text-[#A78BFA] font-medium">Abby AI</span>
        </div>
        <button
          type="button"
          onClick={handleRegenerate}
          disabled={isLoading}
          className="inline-flex items-center gap-1 text-[10px] text-[#7A8298] hover:text-[#A78BFA] transition-colors disabled:opacity-50"
        >
          <RefreshCw size={10} className={isLoading ? "animate-spin" : ""} />
          Regenerate
        </button>
      </div>

      {/* Content */}
      {isLoading && !briefing && (
        <div className="flex items-center gap-2 py-4">
          <Loader2 size={16} className="animate-spin text-[#A78BFA]" />
          <span className="text-sm text-[#7A8298]">Generating genomic briefing...</span>
        </div>
      )}

      {briefing && !briefing.error && (
        <div className="space-y-2">
          <p className="text-sm text-[#B4BAC8] leading-relaxed whitespace-pre-wrap">
            {briefing.briefing}
          </p>
          <div className="flex items-center gap-3 text-[10px] text-[#4A5068]">
            <span>{briefing.actionable_count} actionable / {briefing.variant_count} total variants</span>
            {briefing.generated_at && (
              <span>
                Generated {new Date(briefing.generated_at).toLocaleString("en-US", {
                  month: "short", day: "numeric", hour: "numeric", minute: "2-digit",
                })}
              </span>
            )}
          </div>
        </div>
      )}

      {briefing?.error && (
        <div className="flex items-center gap-2 py-2">
          <span className="text-sm text-[#F0607A]">{briefing.error}</span>
          <button
            type="button"
            onClick={handleRegenerate}
            className="text-xs text-[#7A8298] hover:text-[#E8ECF4] underline"
          >
            Retry
          </button>
        </div>
      )}

      {!isLoading && !briefing && briefingData.variants.length === 0 && (
        <p className="text-sm text-[#7A8298] py-2">
          No variants available for briefing.
        </p>
      )}
    </div>
  );
}
