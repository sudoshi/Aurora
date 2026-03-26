import { cn } from "@/lib/utils";
import type { ClinicianRating } from "../types";

interface OutcomeBadgeProps {
  rating: ClinicianRating | null;
  className?: string;
}

const RATING_STYLES: Record<
  ClinicianRating,
  { label: string; bg: string; text: string; border: string }
> = {
  excellent: {
    label: "Excellent",
    bg: "rgba(34, 197, 94, 0.15)",
    text: "#22C55E",
    border: "rgba(34, 197, 94, 0.3)",
  },
  good: {
    label: "Good",
    bg: "rgba(132, 204, 22, 0.15)",
    text: "#84CC16",
    border: "rgba(132, 204, 22, 0.3)",
  },
  mixed: {
    label: "Mixed",
    bg: "rgba(234, 179, 8, 0.15)",
    text: "#EAB308",
    border: "rgba(234, 179, 8, 0.3)",
  },
  poor: {
    label: "Poor",
    bg: "rgba(249, 115, 22, 0.15)",
    text: "#F97316",
    border: "rgba(249, 115, 22, 0.3)",
  },
  failure: {
    label: "Failure",
    bg: "rgba(239, 68, 68, 0.15)",
    text: "#EF4444",
    border: "rgba(239, 68, 68, 0.3)",
  },
};

export function OutcomeBadge({ rating, className }: OutcomeBadgeProps) {
  if (!rating) {
    return (
      <span
        className={cn(
          "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-[#7A8298]",
          className,
        )}
      >
        Not assessed
      </span>
    );
  }

  const style = RATING_STYLES[rating];

  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap",
        className,
      )}
      style={{
        backgroundColor: style.bg,
        color: style.text,
        border: `1px solid ${style.border}`,
      }}
    >
      {style.label}
    </span>
  );
}
