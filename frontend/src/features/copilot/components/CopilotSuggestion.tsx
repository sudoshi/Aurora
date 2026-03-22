import { useState } from "react";
import { Sparkles, Check, X } from "lucide-react";
import { cn } from "@/lib/utils";

// ---------------------------------------------------------------------------
// CopilotSuggestion — inline AI suggestion card
// ---------------------------------------------------------------------------

interface CopilotSuggestionProps {
  suggestion: string;
  context?: string;
  onApply: () => void;
  onDismiss: () => void;
  className?: string;
}

export function CopilotSuggestion({
  suggestion,
  context,
  onApply,
  onDismiss,
  className,
}: CopilotSuggestionProps) {
  const [dismissed, setDismissed] = useState(false);

  if (dismissed) return null;

  const handleDismiss = () => {
    setDismissed(true);
    onDismiss();
  };

  return (
    <div
      className={cn(
        "rounded-lg border border-[#2DD4BF]/20 bg-[#2DD4BF]/5 p-3 space-y-2",
        className,
      )}
    >
      <div className="flex items-start gap-2">
        <Sparkles size={14} className="text-[#2DD4BF] mt-0.5 shrink-0" />
        <div className="min-w-0 flex-1">
          <p className="text-xs font-medium text-[#2DD4BF] uppercase tracking-wider mb-1">
            Abby Suggestion
          </p>
          <p className="text-sm text-[var(--text-primary)]">{suggestion}</p>
          {context && (
            <p className="text-xs text-[var(--text-muted)] mt-1">{context}</p>
          )}
        </div>
      </div>

      <div className="flex items-center gap-2 ml-6">
        <button
          type="button"
          onClick={onApply}
          className="inline-flex items-center gap-1 rounded-md bg-[#2DD4BF]/15 px-2.5 py-1 text-xs font-medium text-[#2DD4BF] hover:bg-[#2DD4BF]/25 transition-colors"
        >
          <Check size={12} />
          Apply
        </button>
        <button
          type="button"
          onClick={handleDismiss}
          className="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-[var(--text-muted)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-overlay)] transition-colors"
        >
          <X size={12} />
          Dismiss
        </button>
      </div>
    </div>
  );
}
