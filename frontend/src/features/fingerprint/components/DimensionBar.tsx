import { cn } from "@/lib/utils";

interface DimensionBarProps {
  label: string;
  value: number | null;
  color: string;
  className?: string;
}

export function DimensionBar({ label, value, color, className }: DimensionBarProps) {
  const percent = value !== null ? Math.round(value * 100) : 0;
  const hasValue = value !== null;

  return (
    <div className={cn("flex items-center gap-2", className)}>
      <span className="text-xs text-[#7A8298] w-20 shrink-0 truncate">{label}</span>
      <div className="flex-1 h-2 rounded-full bg-[#1C1C48] overflow-hidden">
        {hasValue && (
          <div
            className="h-full rounded-full transition-all duration-500"
            style={{ width: `${percent}%`, backgroundColor: color }}
          />
        )}
      </div>
      <span className="text-xs font-mono w-10 text-right" style={{ color: hasValue ? color : "#7A8298" }}>
        {hasValue ? `${percent}%` : "N/A"}
      </span>
    </div>
  );
}
