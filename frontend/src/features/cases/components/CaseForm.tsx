import { useState, type FormEvent } from "react";
import { X } from "lucide-react";
import { cn } from "@/lib/utils";
import type {
  ClinicalCase,
  CaseSpecialty,
  CaseType,
  CaseUrgency,
  CreateCaseData,
  UpdateCaseData,
} from "../types/case";

// ── Select options ───────────────────────────────────────────────────────────

const SPECIALTIES: { value: CaseSpecialty; label: string }[] = [
  { value: "oncology", label: "Oncology" },
  { value: "surgical", label: "Surgical" },
  { value: "rare_disease", label: "Rare Disease" },
  { value: "complex_medical", label: "Complex Medical" },
];

const CASE_TYPES: { value: CaseType; label: string }[] = [
  { value: "tumor_board", label: "Tumor Board" },
  { value: "surgical_review", label: "Surgical Review" },
  { value: "rare_disease", label: "Rare Disease" },
  { value: "medical_complex", label: "Medical Complex" },
];

const URGENCIES: { value: CaseUrgency; label: string }[] = [
  { value: "routine", label: "Routine" },
  { value: "urgent", label: "Urgent" },
  { value: "emergent", label: "Emergent" },
];

// ── Component ────────────────────────────────────────────────────────────────

interface CaseFormProps {
  clinicalCase?: ClinicalCase | null;
  isPending?: boolean;
  onSubmit: (data: CreateCaseData | UpdateCaseData) => void;
  onClose: () => void;
}

export function CaseForm({ clinicalCase, isPending, onSubmit, onClose }: CaseFormProps) {
  const isEdit = !!clinicalCase;

  const [title, setTitle] = useState(clinicalCase?.title ?? "");
  const [specialty, setSpecialty] = useState<CaseSpecialty>(
    clinicalCase?.specialty ?? "oncology",
  );
  const [caseType, setCaseType] = useState<CaseType>(
    clinicalCase?.case_type ?? "tumor_board",
  );
  const [urgency, setUrgency] = useState<CaseUrgency>(
    clinicalCase?.urgency ?? "routine",
  );
  const [clinicalQuestion, setClinicalQuestion] = useState(
    clinicalCase?.clinical_question ?? "",
  );
  const [summary, setSummary] = useState(clinicalCase?.summary ?? "");

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const data: CreateCaseData = {
      title: title.trim(),
      specialty,
      case_type: caseType,
      urgency,
      clinical_question: clinicalQuestion.trim() || undefined,
      summary: summary.trim() || undefined,
    };
    onSubmit(data);
  };

  const isValid = title.trim().length > 0;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Panel */}
      <div className="relative z-10 w-full max-w-lg rounded-xl border border-[#232328] bg-[#1C1C20] shadow-xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-[#232328] px-5 py-4">
          <h2 className="text-base font-semibold text-[#F0EDE8]">
            {isEdit ? "Edit Case" : "New Case"}
          </h2>
          <button
            type="button"
            onClick={onClose}
            className="flex h-7 w-7 items-center justify-center rounded-md text-[#5A5650] transition-colors hover:bg-[#2A2A30] hover:text-[#8A857D]"
          >
            <X size={16} />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4 px-5 py-4">
          {/* Title */}
          <div className="form-group">
            <label htmlFor="case-title" className="form-label">
              Title
            </label>
            <input
              id="case-title"
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="e.g., Pancreatic mass MDT review"
              className="form-input"
              required
            />
          </div>

          {/* Specialty + Case Type row */}
          <div className="grid grid-cols-2 gap-3">
            <div className="form-group">
              <label htmlFor="case-specialty" className="form-label">
                Specialty
              </label>
              <select
                id="case-specialty"
                value={specialty}
                onChange={(e) => setSpecialty(e.target.value as CaseSpecialty)}
                className="form-input"
              >
                {SPECIALTIES.map((s) => (
                  <option key={s.value} value={s.value}>
                    {s.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="case-type" className="form-label">
                Case Type
              </label>
              <select
                id="case-type"
                value={caseType}
                onChange={(e) => setCaseType(e.target.value as CaseType)}
                className="form-input"
              >
                {CASE_TYPES.map((t) => (
                  <option key={t.value} value={t.value}>
                    {t.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Urgency */}
          <div className="form-group">
            <label className="form-label">Urgency</label>
            <div className="flex gap-2">
              {URGENCIES.map((u) => (
                <button
                  key={u.value}
                  type="button"
                  onClick={() => setUrgency(u.value)}
                  className={cn(
                    "rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors",
                    urgency === u.value
                      ? "border-[#2DD4BF] bg-[#2DD4BF]/10 text-[#2DD4BF]"
                      : "border-[#232328] bg-[#151518] text-[#8A857D] hover:border-[#3A3A42]",
                  )}
                >
                  {u.label}
                </button>
              ))}
            </div>
          </div>

          {/* Clinical question */}
          <div className="form-group">
            <label htmlFor="case-question" className="form-label">
              Clinical Question
            </label>
            <textarea
              id="case-question"
              value={clinicalQuestion}
              onChange={(e) => setClinicalQuestion(e.target.value)}
              placeholder="What clinical question should this case address?"
              rows={3}
              className="form-input resize-none"
            />
          </div>

          {/* Summary */}
          <div className="form-group">
            <label htmlFor="case-summary" className="form-label">
              Summary
            </label>
            <textarea
              id="case-summary"
              value={summary}
              onChange={(e) => setSummary(e.target.value)}
              placeholder="Brief case summary..."
              rows={3}
              className="form-input resize-none"
            />
          </div>

          {/* Footer */}
          <div className="flex justify-end gap-3 border-t border-[#232328] pt-4">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-[#2A2A30] bg-[#151518] px-4 py-2 text-sm text-[#8A857D] transition-colors hover:border-[#3A3A42] hover:text-[#C5C0B8]"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!isValid || isPending}
              className="rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0E0E11] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
            >
              {isPending
                ? "Saving..."
                : isEdit
                  ? "Update Case"
                  : "Create Case"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
