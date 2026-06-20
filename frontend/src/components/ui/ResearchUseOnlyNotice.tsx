import { type HTMLAttributes } from "react";
import { cn } from "@/lib/utils";

const RUO_TEXT =
  "Research Use Only — not validated for primary clinical decision-making.";

export interface ResearchUseOnlyNoticeProps
  extends HTMLAttributes<HTMLDivElement> {
  /**
   * "footer" renders a full-width slim line at the bottom of a layout (default).
   * "chip" renders a small fixed chip anchored to the bottom-left corner, kept
   * clear of the bottom-right realtime "Reconnecting…" pill in DashboardLayout.
   */
  variant?: "footer" | "chip";
}

/**
 * Persistent, unobtrusive Research-Use-Only disclaimer. Aurora surfaces
 * AI-derived and experimental outputs (mock imaging segmentation, LLM advisory
 * decision support), so this notice keeps users aware the platform is not a
 * validated clinical device.
 */
export function ResearchUseOnlyNotice({
  className,
  variant = "footer",
  ...props
}: ResearchUseOnlyNoticeProps) {
  return (
    <div
      role="note"
      aria-label="Research Use Only disclaimer"
      className={cn(
        "select-none text-[11px] leading-tight text-muted-foreground",
        variant === "footer"
          ? "w-full border-t border-border/40 bg-background/60 px-4 py-1.5 text-center"
          : "fixed bottom-3 left-3 z-50 rounded-full border border-border/40 bg-background/80 px-3 py-1 backdrop-blur",
        className,
      )}
      {...props}
    >
      {RUO_TEXT}
    </div>
  );
}
