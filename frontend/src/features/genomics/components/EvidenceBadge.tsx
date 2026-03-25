import { cn } from "@/lib/utils";
import { AlertTriangle } from "lucide-react";

interface EvidenceBadgeProps {
  evidenceLevel: string;
  source?: string | null;
  lastVerifiedAt?: string | null;
  className?: string;
}

const LEVEL_COLORS: Record<string, string> = {
  "1A": "bg-[#2DD4BF]/15 text-[#2DD4BF] border-[#2DD4BF]/25",
  "1B": "bg-[#2DD4BF]/10 text-[#2DD4BF] border-[#2DD4BF]/20",
  "2A": "bg-[#60A5FA]/15 text-[#60A5FA] border-[#60A5FA]/25",
  "2B": "bg-[#60A5FA]/10 text-[#60A5FA] border-[#60A5FA]/20",
  "3": "bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20",
  "3A": "bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20",
  "3B": "bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20",
  "4": "bg-[#7A8298]/10 text-[#7A8298] border-[#7A8298]/20",
};

function isStale(lastVerifiedAt: string | null | undefined): boolean {
  if (!lastVerifiedAt) return true;
  const days = (Date.now() - new Date(lastVerifiedAt).getTime()) / 86400000;
  return days > 30;
}

export function EvidenceBadge({ evidenceLevel, source, lastVerifiedAt, className }: EvidenceBadgeProps) {
  const colorClass = LEVEL_COLORS[evidenceLevel] ?? LEVEL_COLORS["4"];
  const stale = isStale(lastVerifiedAt);

  return (
    <span className={cn("inline-flex items-center gap-1", className)}>
      <span
        className={cn(
          "inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold",
          colorClass,
        )}
      >
        Level {evidenceLevel}
      </span>
      {source && (
        <span className="text-[10px] text-[#4A5068] uppercase">
          {source}
        </span>
      )}
      {stale && (
        <span title="Evidence not verified in >30 days">
          <AlertTriangle size={10} className="text-[#F59E0B]" />
        </span>
      )}
    </span>
  );
}
