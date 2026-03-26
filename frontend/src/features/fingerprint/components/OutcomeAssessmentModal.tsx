import { useState } from "react";
import { Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";
import { Modal } from "@/components/ui/Modal";
import { Button } from "@/components/ui/Button";
import { useAssessOutcome } from "../hooks/useFingerprint";
import { DecisionTagChips } from "./DecisionTagChips";
import type { ClinicianRating, OutcomeAssessmentPayload } from "../types";

interface OutcomeAssessmentModalProps {
  open: boolean;
  onClose: () => void;
  patientId: number;
}

const RATINGS: { value: ClinicianRating; label: string; color: string }[] = [
  { value: "excellent", label: "Excellent", color: "#22C55E" },
  { value: "good", label: "Good", color: "#84CC16" },
  { value: "mixed", label: "Mixed", color: "#EAB308" },
  { value: "poor", label: "Poor", color: "#F97316" },
  { value: "failure", label: "Failure", color: "#EF4444" },
];

const DEFAULT_TAGS = [
  "drug-switch",
  "dose-reduction",
  "surgical-candidate",
  "immunotherapy-ae",
  "palliative-transition",
  "complete-response",
];

export function OutcomeAssessmentModal({
  open,
  onClose,
  patientId,
}: OutcomeAssessmentModalProps) {
  const [rating, setRating] = useState<ClinicianRating | null>(null);
  const [decisionTags, setDecisionTags] = useState<string[]>([]);
  const [factors, setFactors] = useState("");
  const [hindsight, setHindsight] = useState("");

  const assessMutation = useAssessOutcome();

  const handleSave = () => {
    if (!rating) return;

    const payload: OutcomeAssessmentPayload = {
      clinician_rating: rating,
      clinician_factors: factors || undefined,
      decision_tags: decisionTags.length > 0 ? decisionTags : undefined,
      hindsight_note: hindsight || undefined,
    };

    assessMutation.mutate(
      { patientId, payload },
      {
        onSuccess: () => {
          resetForm();
          onClose();
        },
      },
    );
  };

  const resetForm = () => {
    setRating(null);
    setDecisionTags([]);
    setFactors("");
    setHindsight("");
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  return (
    <Modal
      open={open}
      onClose={handleClose}
      title="Outcome Assessment"
      size="lg"
      footer={
        <div className="flex items-center justify-end gap-2">
          <Button variant="ghost" onClick={handleClose}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleSave}
            disabled={!rating || assessMutation.isPending}
          >
            {assessMutation.isPending ? (
              <>
                <Loader2 size={14} className="animate-spin mr-1" />
                Saving...
              </>
            ) : (
              "Save Assessment"
            )}
          </Button>
        </div>
      }
    >
      <div className="space-y-5">
        {/* Rating selector */}
        <div>
          <label className="block text-sm font-medium text-[#E8ECF4] mb-2">
            Overall Outcome Rating
          </label>
          <div className="flex gap-2">
            {RATINGS.map((r) => (
              <button
                key={r.value}
                type="button"
                onClick={() => setRating(r.value)}
                className={cn(
                  "flex-1 rounded-lg px-3 py-2.5 text-xs font-semibold transition-all border",
                  rating === r.value
                    ? "scale-[1.02]"
                    : "bg-[#0A0A18] text-[#7A8298] border-[#1C1C48] hover:border-[#4A5068]",
                )}
                style={
                  rating === r.value
                    ? {
                        backgroundColor: `${r.color}15`,
                        color: r.color,
                        borderColor: `${r.color}40`,
                      }
                    : undefined
                }
              >
                {r.label}
              </button>
            ))}
          </div>
        </div>

        {/* Decision tags */}
        <div>
          <label className="block text-sm font-medium text-[#E8ECF4] mb-2">
            Decision Point Tags
          </label>
          <DecisionTagChips
            tags={DEFAULT_TAGS}
            selected={decisionTags}
            onChange={setDecisionTags}
            allowCustom
          />
        </div>

        {/* Key factors */}
        <div>
          <label className="block text-sm font-medium text-[#E8ECF4] mb-2">
            Key Factors
          </label>
          <textarea
            value={factors}
            onChange={(e) => setFactors(e.target.value)}
            rows={3}
            placeholder="What were the key factors that influenced this patient's outcome?"
            className="w-full rounded-lg border border-[#1C1C48] bg-[#0A0A18] px-3 py-2 text-sm text-[#E8ECF4] placeholder:text-[#7A8298] focus:border-[#2DD4BF]/50 focus:outline-none focus:ring-1 focus:ring-[#2DD4BF]/20 resize-none"
          />
        </div>

        {/* Hindsight note */}
        <div>
          <label className="block text-sm font-medium text-[#E8ECF4] mb-2">
            Hindsight Note{" "}
            <span className="text-[#7A8298] font-normal">(optional)</span>
          </label>
          <textarea
            value={hindsight}
            onChange={(e) => setHindsight(e.target.value)}
            rows={2}
            placeholder="Knowing what you know now, what would you do differently?"
            className="w-full rounded-lg border border-[#1C1C48] bg-[#0A0A18] px-3 py-2 text-sm text-[#E8ECF4] placeholder:text-[#7A8298] focus:border-[#2DD4BF]/50 focus:outline-none focus:ring-1 focus:ring-[#2DD4BF]/20 resize-none"
          />
        </div>

        {/* Error message */}
        {assessMutation.isError && (
          <p className="text-xs text-[#F0607A]">
            Failed to save assessment. Please try again.
          </p>
        )}
      </div>
    </Modal>
  );
}
