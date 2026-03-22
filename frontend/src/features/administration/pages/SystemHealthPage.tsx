import { RefreshCw } from "lucide-react";
import { Panel } from "@/components/ui/Panel";
import { Badge, type BadgeVariant } from "@/components/ui/Badge";
import { StatusDot, type StatusDotVariant } from "@/components/ui/StatusDot";
import { Button } from "@/components/ui/Button";
import type { SystemHealthService } from "../api/adminApi";
import { useSystemHealth } from "../hooks/useAiProviders";

const STATUS_MAP: Record<string, { badge: BadgeVariant; dot: StatusDotVariant }> = {
  healthy:  { badge: "success",  dot: "healthy" },
  degraded: { badge: "warning",  dot: "degraded" },
  down:     { badge: "critical", dot: "critical" },
};

function ServiceCard({ service }: { service: SystemHealthService }) {
  const { badge, dot } = STATUS_MAP[service.status] ?? STATUS_MAP.down;
  const queueDetails = service.details as { pending?: number; failed?: number } | undefined;

  return (
    <Panel className="h-full">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-center gap-3">
          <StatusDot status={dot} />
          <div>
            <p className="font-semibold text-[#E8ECF4]">{service.name}</p>
            <p className="mt-0.5 text-sm text-[#7A8298]">{service.message}</p>
          </div>
        </div>
        <Badge variant={badge}>{service.status}</Badge>
      </div>

      {queueDetails?.pending !== undefined && (
        <div className="mt-3 flex gap-4 text-sm">
          <span className="text-[#7A8298]">
            Pending:{" "}
            <span className="font-medium text-[#E8ECF4]">{queueDetails.pending ?? 0}</span>
          </span>
          <span className="text-[#7A8298]">
            Failed:{" "}
            <span
              className={`font-medium ${(queueDetails.failed ?? 0) > 0 ? "text-[#F0607A]" : "text-[#E8ECF4]"}`}
            >
              {queueDetails.failed ?? 0}
            </span>
          </span>
        </div>
      )}
    </Panel>
  );
}

export default function SystemHealthPage() {
  const { data, isLoading, isFetching, refetch, dataUpdatedAt } = useSystemHealth();

  const overallStatus =
    data?.services.find((s) => s.status === "down")
      ? "down"
      : data?.services.find((s) => s.status === "degraded")
        ? "degraded"
        : "healthy";

  const overallDot: StatusDotVariant = overallStatus === "healthy" ? "healthy" : "degraded";
  const checkedAt = dataUpdatedAt ? new Date(dataUpdatedAt).toLocaleTimeString() : null;

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-[#E8ECF4]">System Health</h1>
          <p className="mt-1 text-sm text-[#7A8298]">
            Live status of Aurora services: database, cache, queue, and AI backend. Auto-refreshes every 30 seconds.
          </p>
        </div>
        <Button
          variant="secondary"
          size="sm"
          onClick={() => refetch()}
          disabled={isFetching}
        >
          <RefreshCw className={`h-4 w-4 mr-1 ${isFetching ? "animate-spin" : ""}`} />
          Refresh
        </Button>
      </div>

      {/* Overall banner */}
      {data && (
        <Panel>
          <div className="flex items-center gap-3">
            <StatusDot status={overallDot} />
            <span className="text-sm font-medium text-[#E8ECF4]">
              Server Status
            </span>
            <Badge variant={overallStatus === "healthy" ? "success" : "warning"}>
              {overallStatus === "healthy" ? "Healthy" : "Needs Attention"}
            </Badge>
            {checkedAt && (
              <span className="ml-auto text-xs text-[#7A8298]">
                Last checked at {checkedAt}
              </span>
            )}
          </div>
        </Panel>
      )}

      {/* Service cards */}
      {isLoading ? (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-24 animate-pulse rounded-lg border border-[#1C1C48] bg-[#16163A]" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          {(data?.services ?? []).map((s) => (
            <ServiceCard key={s.key} service={s} />
          ))}
        </div>
      )}
    </div>
  );
}
