import { useState, type FormEvent } from "react";
import { Send, Tag } from "lucide-react";
import { cn } from "@/lib/utils";
import { useCaseAnnotations, useCreateAnnotation } from "../hooks/useCases";
import type { CaseAnnotation } from "../types/case";

// ── Domain options ───────────────────────────────────────────────────────────

const DOMAINS = [
  "radiology",
  "pathology",
  "surgery",
  "oncology",
  "genetics",
  "pharmacy",
  "nursing",
  "other",
];

const DOMAIN_COLORS: Record<string, string> = {
  radiology: "#60A5FA",
  pathology: "#E85A6B",
  surgery:   "#2DD4BF",
  oncology:  "#9B1B30",
  genetics:  "#A78BFA",
  pharmacy:  "#F59E0B",
  nursing:   "#10B981",
  other:     "#8A857D",
};

// ── Grouped annotation list ──────────────────────────────────────────────────

function AnnotationGroup({
  domain,
  annotations,
}: {
  domain: string;
  annotations: CaseAnnotation[];
}) {
  const color = DOMAIN_COLORS[domain] ?? "#8A857D";

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-2">
        <span
          className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
          style={{ backgroundColor: `${color}15`, color }}
        >
          <Tag size={10} />
          {domain}
        </span>
        <span className="font-['IBM_Plex_Mono',monospace] text-[10px] text-[#5A5650]">
          {annotations.length}
        </span>
      </div>

      <div className="space-y-2 pl-2">
        {annotations.map((annotation) => (
          <div
            key={annotation.id}
            className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-3"
          >
            <p className="text-sm text-[#C5C0B8]">{annotation.content}</p>
            <div className="mt-2 flex items-center gap-2 text-[10px] text-[#5A5650]">
              {annotation.user && (
                <span className="font-medium text-[#8A857D]">
                  {annotation.user.name}
                </span>
              )}
              <span>&middot;</span>
              <span className="font-['IBM_Plex_Mono',monospace]">
                {new Date(annotation.created_at).toLocaleDateString("en-US", {
                  month: "short",
                  day: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
                })}
              </span>
              {annotation.record_ref && (
                <>
                  <span>&middot;</span>
                  <span className="font-['IBM_Plex_Mono',monospace] text-[#2DD4BF]">
                    {annotation.record_ref}
                  </span>
                </>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

// ── Main panel ───────────────────────────────────────────────────────────────

interface CaseAnnotationPanelProps {
  caseId: number;
}

export function CaseAnnotationPanel({ caseId }: CaseAnnotationPanelProps) {
  const { data: annotations, isLoading } = useCaseAnnotations(caseId);
  const createAnnotation = useCreateAnnotation();

  const [domain, setDomain] = useState(DOMAINS[0]);
  const [content, setContent] = useState("");

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!content.trim()) return;

    createAnnotation.mutate(
      { caseId, data: { domain, content: content.trim() } },
      { onSuccess: () => setContent("") },
    );
  };

  // Group annotations by domain
  const grouped = (annotations ?? []).reduce<Record<string, CaseAnnotation[]>>(
    (acc, a) => {
      const key = a.domain;
      return { ...acc, [key]: [...(acc[key] ?? []), a] };
    },
    {},
  );

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <span className="text-sm text-[#5A5650]">Loading annotations...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Annotation groups */}
      {Object.keys(grouped).length > 0 ? (
        <div className="space-y-6">
          {Object.entries(grouped).map(([d, items]) => (
            <AnnotationGroup key={d} domain={d} annotations={items} />
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#323238] bg-[#151518] py-12">
          <Tag size={24} className="mb-2 text-[#5A5650]" />
          <p className="text-sm text-[#8A857D]">No annotations yet</p>
          <p className="mt-1 text-xs text-[#5A5650]">
            Add the first clinical annotation below.
          </p>
        </div>
      )}

      {/* Add annotation form */}
      <form
        onSubmit={handleSubmit}
        className="rounded-lg border border-[#232328] bg-[#1A1A1E] p-4"
      >
        <h4 className="mb-3 text-xs font-semibold uppercase tracking-wider text-[#8A857D]">
          Add Annotation
        </h4>

        <div className="mb-3">
          <label htmlFor="annotation-domain" className="form-label">
            Domain
          </label>
          <select
            id="annotation-domain"
            value={domain}
            onChange={(e) => setDomain(e.target.value)}
            className="form-input"
          >
            {DOMAINS.map((d) => (
              <option key={d} value={d}>
                {d.charAt(0).toUpperCase() + d.slice(1)}
              </option>
            ))}
          </select>
        </div>

        <div className="mb-3">
          <label htmlFor="annotation-content" className="form-label">
            Content
          </label>
          <textarea
            id="annotation-content"
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="Enter your clinical annotation..."
            rows={3}
            className="form-input resize-none"
          />
        </div>

        <div className="flex justify-end">
          <button
            type="submit"
            disabled={!content.trim() || createAnnotation.isPending}
            className={cn(
              "inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors",
              "bg-[#2DD4BF] text-[#0E0E11] hover:bg-[#25B8A5] disabled:opacity-50",
            )}
          >
            <Send size={14} />
            {createAnnotation.isPending ? "Saving..." : "Add Annotation"}
          </button>
        </div>
      </form>
    </div>
  );
}
