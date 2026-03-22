import { useNavigate } from "react-router-dom";
import { RefreshCw, Brain, Users, AlertCircle } from "lucide-react";
import { cn } from "@/lib/utils";
import { Badge } from "@/components/ui/Badge";
import { Skeleton } from "@/components/ui/Skeleton";
import { EmptyState } from "@/components/ui/EmptyState";
import { Button } from "@/components/ui/Button";
import { useSimilarPatients, useEmbedPatient } from "../hooks/useSimilarity";
import type { SimilarPatient } from "../api/similarityApi";

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

interface PatientsLikeThisProps {
  patientId: number;
}

// ---------------------------------------------------------------------------
// Score color helper
// ---------------------------------------------------------------------------

function scoreColor(score: number): string {
  if (score >= 0.8) return "#2DD4BF"; // green/teal
  if (score >= 0.6) return "#F59E0B"; // yellow/amber
  return "#E85A6B";                   // red
}

function scoreLabel(score: number): string {
  if (score >= 0.8) return "High";
  if (score >= 0.6) return "Moderate";
  return "Low";
}

// ---------------------------------------------------------------------------
// SimilarPatientCard
// ---------------------------------------------------------------------------

function SimilarPatientCard({ patient }: { patient: SimilarPatient }) {
  const navigate = useNavigate();
  const color = scoreColor(patient.score);
  const pct = Math.round(patient.score * 100);

  return (
    <button
      type="button"
      onClick={() => navigate(`/profiles/${patient.patient_id}`)}
      className="w-full text-left rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 hover:border-[#2DD4BF]/30 hover:bg-[var(--surface-overlay)] transition-colors"
    >
      {/* Score bar */}
      <div className="flex items-center justify-between gap-3 mb-3">
        <span className="text-sm font-semibold text-[var(--text-primary)] font-['IBM_Plex_Mono',monospace]">
          Patient #{patient.patient_id}
        </span>
        <span
          className="text-xs font-semibold px-2 py-0.5 rounded-full"
          style={{ color, backgroundColor: `${color}15`, border: `1px solid ${color}30` }}
        >
          {pct}% {scoreLabel(patient.score)}
        </span>
      </div>

      {/* Similarity bar */}
      <div className="w-full h-1.5 rounded-full bg-[var(--surface-elevated)] mb-3">
        <div
          className="h-full rounded-full transition-all duration-300"
          style={{ width: `${pct}%`, backgroundColor: color }}
        />
      </div>

      {/* Shared conditions */}
      {patient.shared_conditions.length > 0 && (
        <div className="mb-2">
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1">
            Shared Conditions
          </p>
          <div className="flex flex-wrap gap-1">
            {patient.shared_conditions.map((c) => (
              <Badge key={c} variant="critical" className="text-[10px]">
                {c}
              </Badge>
            ))}
          </div>
        </div>
      )}

      {/* Shared medications */}
      {patient.shared_medications.length > 0 && (
        <div className="mb-2">
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1">
            Shared Medications
          </p>
          <div className="flex flex-wrap gap-1">
            {patient.shared_medications.map((m) => (
              <Badge key={m} variant="info" className="text-[10px]">
                {m}
              </Badge>
            ))}
          </div>
        </div>
      )}

      {/* Key differences */}
      {patient.key_differences.length > 0 && (
        <div className="mb-2">
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1">
            Key Differences
          </p>
          <ul className="space-y-0.5">
            {patient.key_differences.map((d) => (
              <li key={d} className="text-[11px] text-[var(--text-ghost)]">
                {d}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Outcome summary */}
      {patient.outcome_summary && (
        <div className="mt-2 pt-2 border-t border-[var(--border-default)]">
          <p className="text-[10px] font-medium text-[var(--text-muted)] uppercase tracking-wider mb-1">
            Outcome
          </p>
          <p className="text-xs text-[var(--text-secondary)]">
            {patient.outcome_summary}
          </p>
        </div>
      )}
    </button>
  );
}

// ---------------------------------------------------------------------------
// Loading skeleton
// ---------------------------------------------------------------------------

function SimilarPatientSkeleton() {
  return (
    <div className="space-y-3">
      {Array.from({ length: 3 }).map((_, i) => (
        <div
          key={i}
          className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] p-4 space-y-3"
        >
          <div className="flex items-center justify-between">
            <Skeleton variant="text" width="120px" />
            <Skeleton variant="text" width="60px" />
          </div>
          <Skeleton variant="text" width="100%" height="6px" />
          <div className="flex gap-1">
            <Skeleton variant="text" width="70px" height="20px" />
            <Skeleton variant="text" width="90px" height="20px" />
          </div>
        </div>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// PatientsLikeThis (main component)
// ---------------------------------------------------------------------------

export function PatientsLikeThis({ patientId }: PatientsLikeThisProps) {
  const {
    data,
    isLoading,
    isError,
    error,
    refetch,
    isFetching,
  } = useSimilarPatients(patientId);

  const embedMutation = useEmbedPatient();

  const handleRefresh = () => {
    refetch();
  };

  const handleEmbed = () => {
    embedMutation.mutate(patientId);
  };

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Brain size={16} className="text-[#2DD4BF]" />
          <h2 className="text-sm font-semibold text-[var(--text-primary)]">
            Patients Like This
          </h2>
          {data?.results && (
            <span className="text-xs text-[var(--text-muted)]">
              ({data.results.length})
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={handleEmbed}
            disabled={embedMutation.isPending}
          >
            {embedMutation.isPending ? "Embedding..." : "Re-embed"}
          </Button>
          <Button
            variant="ghost"
            size="sm"
            icon
            onClick={handleRefresh}
            disabled={isFetching}
          >
            <RefreshCw
              size={14}
              className={cn(isFetching && "animate-spin")}
            />
          </Button>
        </div>
      </div>

      {/* Loading */}
      {isLoading && <SimilarPatientSkeleton />}

      {/* Error */}
      {isError && !isLoading && (
        <div className="flex items-center gap-3 rounded-lg border border-[#E85A6B]/20 bg-[#E85A6B]/5 p-4">
          <AlertCircle size={16} className="text-[#E85A6B] shrink-0" />
          <div className="min-w-0">
            <p className="text-sm text-[#E85A6B]">
              Failed to load similar patients
            </p>
            <p className="text-xs text-[var(--text-muted)] mt-0.5">
              {error instanceof Error ? error.message : "An unexpected error occurred."}
            </p>
          </div>
        </div>
      )}

      {/* Empty state */}
      {data && data.results.length === 0 && (
        <EmptyState
          icon={<Users size={32} className="text-[var(--text-ghost)]" />}
          title="No similar patients found"
          message="Embeddings may need to be computed. Click 'Re-embed' to generate embeddings for this patient."
          action={
            <Button
              variant="primary"
              size="sm"
              onClick={handleEmbed}
              disabled={embedMutation.isPending}
            >
              {embedMutation.isPending ? "Computing..." : "Compute Embeddings"}
            </Button>
          }
        />
      )}

      {/* Results */}
      {data && data.results.length > 0 && (
        <div className="space-y-3">
          {data.results.map((patient) => (
            <SimilarPatientCard key={patient.patient_id} patient={patient} />
          ))}
        </div>
      )}
    </div>
  );
}
