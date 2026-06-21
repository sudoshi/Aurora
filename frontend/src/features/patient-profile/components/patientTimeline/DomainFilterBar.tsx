import { cn } from "@/lib/utils";
import type { ClinicalDomain, ClinicalEvent } from "../../types/profile";
import { DOMAIN_CONFIG, DOMAIN_RESOLVED } from "../patientTimeline.utils";

interface DomainFilterBarProps {
  domains: ClinicalDomain[];
  events: ClinicalEvent[];
  hiddenDomains: Set<ClinicalDomain>;
  onToggle: (domain: ClinicalDomain) => void;
}

export function DomainFilterBar({
  domains,
  events,
  hiddenDomains,
  onToggle,
}: DomainFilterBarProps) {
  return (
    <div className="flex items-center gap-1.5 px-4 py-2 bg-[var(--surface-raised)] border-b border-[var(--border-default)] overflow-x-auto">
      <span className="text-[10px] text-[var(--text-ghost)] shrink-0 mr-1">Domains:</span>
      {domains.map((domain) => {
        const cfg = DOMAIN_CONFIG[domain];
        const resolvedColor = DOMAIN_RESOLVED[domain];
        const hidden = hiddenDomains.has(domain);
        const count = events.filter((e) => e.domain === domain).length;
        return (
          <button
            key={domain}
            type="button"
            onClick={() => onToggle(domain)}
            className={cn(
              "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-medium border transition-all shrink-0",
              hidden && "border-[var(--border-default)] text-[var(--text-ghost)] bg-transparent",
            )}
            style={
              hidden
                ? {}
                : {
                    backgroundColor: `${resolvedColor}15`,
                    color: resolvedColor,
                    borderColor: `${resolvedColor}40`,
                  }
            }
          >
            {cfg.label} ({count})
          </button>
        );
      })}
    </div>
  );
}
