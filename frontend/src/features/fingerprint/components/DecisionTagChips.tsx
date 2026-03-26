import { useState } from "react";
import { Plus, X } from "lucide-react";
import { cn } from "@/lib/utils";

interface DecisionTagChipsProps {
  tags: string[];
  selected: string[];
  onChange: (tags: string[]) => void;
  allowCustom?: boolean;
  className?: string;
}

const DEFAULT_TAGS = [
  "drug-switch",
  "dose-reduction",
  "surgical-candidate",
  "immunotherapy-ae",
  "palliative-transition",
  "complete-response",
];

function formatTagLabel(tag: string): string {
  return tag
    .split("-")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
}

export function DecisionTagChips({
  tags = DEFAULT_TAGS,
  selected,
  onChange,
  allowCustom = true,
  className,
}: DecisionTagChipsProps) {
  const [showInput, setShowInput] = useState(false);
  const [customValue, setCustomValue] = useState("");

  const toggleTag = (tag: string) => {
    const next = selected.includes(tag)
      ? selected.filter((t) => t !== tag)
      : [...selected, tag];
    onChange(next);
  };

  const addCustomTag = () => {
    const trimmed = customValue.trim().toLowerCase().replace(/\s+/g, "-");
    if (trimmed && !selected.includes(trimmed)) {
      onChange([...selected, trimmed]);
    }
    setCustomValue("");
    setShowInput(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") {
      e.preventDefault();
      addCustomTag();
    }
    if (e.key === "Escape") {
      setShowInput(false);
      setCustomValue("");
    }
  };

  const allTags = [...new Set([...tags, ...selected])];

  return (
    <div className={cn("flex flex-wrap gap-2", className)}>
      {allTags.map((tag) => {
        const isSelected = selected.includes(tag);
        return (
          <button
            key={tag}
            type="button"
            onClick={() => toggleTag(tag)}
            className={cn(
              "inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium transition-colors border",
              isSelected
                ? "bg-[#2DD4BF]/15 text-[#2DD4BF] border-[#2DD4BF]/30"
                : "bg-[#0A0A18] text-[#7A8298] border-[#1C1C48] hover:border-[#4A5068] hover:text-[#B4BAC8]",
            )}
          >
            {formatTagLabel(tag)}
            {isSelected && !tags.includes(tag) && (
              <X size={10} className="ml-0.5" />
            )}
          </button>
        );
      })}

      {allowCustom && !showInput && (
        <button
          type="button"
          onClick={() => setShowInput(true)}
          className="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium text-[#7A8298] border border-dashed border-[#1C1C48] hover:border-[#4A5068] hover:text-[#B4BAC8] transition-colors"
        >
          <Plus size={10} />
          Custom tag
        </button>
      )}

      {allowCustom && showInput && (
        <input
          type="text"
          value={customValue}
          onChange={(e) => setCustomValue(e.target.value)}
          onKeyDown={handleKeyDown}
          onBlur={addCustomTag}
          autoFocus
          placeholder="Tag name..."
          className="rounded-full px-3 py-1 text-xs bg-[#0A0A18] border border-[#2DD4BF]/30 text-[#E8ECF4] placeholder:text-[#7A8298] outline-none w-28"
        />
      )}
    </div>
  );
}
