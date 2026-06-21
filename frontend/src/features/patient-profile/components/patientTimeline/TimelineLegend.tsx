import type { ClinicalDomain, ClinicalEvent } from "../../types/profile";
import { DOMAIN_CONFIG, DOMAIN_RESOLVED } from "../patientTimeline.utils";

interface TimelineLegendProps {
  activeDomains: ClinicalDomain[];
  domainEvents: Record<ClinicalDomain, ClinicalEvent[]>;
}

export function TimelineLegend({ activeDomains, domainEvents }: TimelineLegendProps) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-2 border-t border-[var(--border-default)] bg-[var(--surface-overlay)]">
      <div className="flex flex-wrap gap-3">
        {activeDomains.map((domain) => {
          const resolvedColor = DOMAIN_RESOLVED[domain];
          return (
            <div key={domain} className="flex items-center gap-1.5">
              <div className="w-2.5 h-2.5 rounded-sm" style={{ backgroundColor: resolvedColor }} />
              <span className="text-[10px] text-[var(--text-muted)]">
                {DOMAIN_CONFIG[domain].label} ({domainEvents[domain].length})
              </span>
            </div>
          );
        })}
      </div>
      <span className="text-[10px] text-[var(--text-disabled)]">
        Ctrl+scroll to zoom &middot; Drag to pan &middot; Click event for details
      </span>
    </div>
  );
}
