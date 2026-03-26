import { Loader2, RefreshCw } from "lucide-react";
import { cn } from "@/lib/utils";
import { usePatientFingerprint, useEncodePatient } from "../hooks/useFingerprint";

interface FingerprintBannerProps {
  patientId: number;
  className?: string;
}

interface DimensionIndicatorProps {
  emoji: string;
  label: string;
  available: boolean;
  confidence: number | null;
  encodedAt: string | null;
}

function DimensionIndicator({ emoji, label, available, confidence, encodedAt }: DimensionIndicatorProps) {
  const freshness = encodedAt ? getFreshness(encodedAt) : null;

  return (
    <div
      className={cn(
        "flex items-center gap-2 rounded-lg px-3 py-2 border",
        available
          ? "bg-[#0A0A18] border-[#1C1C48]"
          : "bg-[#0A0A18]/50 border-[#1C1C48]/50 opacity-50",
      )}
    >
      <span className="text-base">{emoji}</span>
      <div className="min-w-0">
        <div className="text-xs font-medium text-[#E8ECF4]">{label}</div>
        {available ? (
          <div className="text-[10px] text-[#7A8298]">
            {confidence !== null && `${Math.round(confidence * 100)}% confidence`}
            {freshness && ` · ${freshness}`}
          </div>
        ) : (
          <div className="text-[10px] text-[#7A8298]">No data</div>
        )}
      </div>
      {available && (
        <div
          className="ml-auto w-2 h-2 rounded-full"
          style={{
            backgroundColor:
              confidence !== null && confidence >= 0.7
                ? "#22C55E"
                : confidence !== null && confidence >= 0.4
                  ? "#EAB308"
                  : "#F97316",
          }}
        />
      )}
    </div>
  );
}

function getFreshness(encodedAt: string): string {
  const diff = Date.now() - new Date(encodedAt).getTime();
  const hours = Math.floor(diff / 3600000);
  if (hours < 1) return "Just now";
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;
  return `${Math.floor(days / 7)}w ago`;
}

export function FingerprintBanner({ patientId, className }: FingerprintBannerProps) {
  const { data: fingerprint, isLoading } = usePatientFingerprint(patientId);
  const encodeMutation = useEncodePatient();

  const handleEncode = () => {
    encodeMutation.mutate(patientId);
  };

  if (isLoading) {
    return (
      <div className={cn("rounded-xl border border-[#1C1C48] bg-[#10102A] p-4", className)}>
        <div className="flex items-center gap-2 text-[#7A8298]">
          <Loader2 size={14} className="animate-spin" />
          <span className="text-sm">Loading fingerprint status...</span>
        </div>
      </div>
    );
  }

  const hasFingerprint = fingerprint?.has_fingerprint ?? false;
  const dimensionCount = fingerprint?.dimension_count ?? 0;

  return (
    <div className={cn("rounded-xl border border-[#1C1C48] bg-[#10102A] p-4", className)}>
      <div className="flex items-start justify-between gap-4 mb-3">
        <div>
          <h3 className="text-sm font-semibold text-[#E8ECF4]">
            Patient Fingerprint
          </h3>
          <p className="text-xs text-[#7A8298] mt-0.5">
            {hasFingerprint
              ? `${dimensionCount}/3 dimensions encoded`
              : "No fingerprint encoded yet"}
          </p>
        </div>
        <button
          type="button"
          onClick={handleEncode}
          disabled={encodeMutation.isPending}
          className={cn(
            "inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors",
            "bg-[#2DD4BF]/10 text-[#2DD4BF] border border-[#2DD4BF]/20",
            "hover:bg-[#2DD4BF]/20 disabled:opacity-50 disabled:cursor-not-allowed",
          )}
        >
          {encodeMutation.isPending ? (
            <Loader2 size={12} className="animate-spin" />
          ) : (
            <RefreshCw size={12} />
          )}
          {hasFingerprint ? "Re-encode" : "Encode"}
        </button>
      </div>

      <div className="grid grid-cols-3 gap-2">
        <DimensionIndicator
          emoji="\u{1F9EC}"
          label="Genomic"
          available={fingerprint?.dimensions.genomic ?? false}
          confidence={fingerprint?.confidence.genomic ?? null}
          encodedAt={fingerprint?.encoded_at.genomic ?? null}
        />
        <DimensionIndicator
          emoji="\u{1F4D0}"
          label="Volumetric"
          available={fingerprint?.dimensions.volumetric ?? false}
          confidence={fingerprint?.confidence.volumetric ?? null}
          encodedAt={fingerprint?.encoded_at.volumetric ?? null}
        />
        <DimensionIndicator
          emoji="\u{1F3E5}"
          label="Clinical"
          available={fingerprint?.dimensions.clinical ?? false}
          confidence={fingerprint?.confidence.clinical ?? null}
          encodedAt={fingerprint?.encoded_at.clinical ?? null}
        />
      </div>

      {encodeMutation.isError && (
        <p className="mt-2 text-xs text-[#F0607A]">
          Encoding failed. Please try again.
        </p>
      )}
    </div>
  );
}
