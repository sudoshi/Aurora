import {
  usePatientCollaboration,
  useUpdateFlag,
  useUpdateTask,
} from "../hooks/useCollaboration";
import type { PatientProfile } from "../types/profile";
import { ActiveProblemsList } from "./ActiveProblemsList";
import { FlaggedFindings } from "./FlaggedFindings";
import { PendingActions } from "./PendingActions";
import { RecentDecisions } from "./RecentDecisions";

interface PatientBriefingProps {
  patientId: number;
  profile: PatientProfile;
  onNavigate: (tab: string) => void;
}

function QuadrantCard({ children }: { children: React.ReactNode }) {
  return (
    <div
      className="rounded-lg overflow-auto"
      style={{
        background: "rgba(26, 26, 46, 1)",
        border: "1px solid rgba(255, 255, 255, 0.06)",
        padding: "14px 16px",
        minHeight: "180px",
      }}
    >
      {children}
    </div>
  );
}

function SkeletonQuadrant() {
  return (
    <div
      className="rounded-lg animate-pulse"
      style={{
        background: "rgba(26, 26, 46, 1)",
        border: "1px solid rgba(255, 255, 255, 0.06)",
        padding: "14px 16px",
        minHeight: "180px",
      }}
    >
      <div className="h-2.5 w-32 rounded bg-white/10 mb-4" />
      <div className="space-y-2">
        <div className="h-2 w-full rounded bg-white/[0.06]" />
        <div className="h-2 w-4/5 rounded bg-white/[0.06]" />
        <div className="h-2 w-3/5 rounded bg-white/[0.06]" />
        <div className="h-2 w-full rounded bg-white/[0.06]" />
        <div className="h-2 w-2/3 rounded bg-white/[0.06]" />
      </div>
    </div>
  );
}

export function PatientBriefing({ patientId, profile, onNavigate }: PatientBriefingProps) {
  const { data: collab, isLoading } = usePatientCollaboration(patientId);
  const updateFlag = useUpdateFlag(patientId);
  const updateTask = useUpdateTask(patientId);

  function handleResolveFlag(flagId: number) {
    updateFlag.mutate({
      flagId,
      data: { resolve: true },
    });
  }

  function handleCompleteTask(taskId: number) {
    updateTask.mutate({
      taskId,
      data: { status: "completed" },
    });
  }

  function handleCompleteFollowUp(_followUpId: number) {
    // Follow-up completion goes through the follow-up API endpoint.
    // For now we invalidate via the collaboration query after a no-op.
    // A dedicated useUpdateFollowUp hook can be wired in once available.
  }

  if (isLoading) {
    return (
      <div
        className="grid gap-4"
        style={{ gridTemplateColumns: "1fr 1fr" }}
      >
        <SkeletonQuadrant />
        <SkeletonQuadrant />
        <SkeletonQuadrant />
        <SkeletonQuadrant />
      </div>
    );
  }

  const flags = collab?.flags ?? [];
  const tasks = collab?.tasks ?? [];
  const followUps = collab?.follow_ups ?? [];
  const decisions = collab?.decisions ?? [];

  return (
    <div
      className="grid gap-4"
      style={{ gridTemplateColumns: "1fr 1fr" }}
    >
      {/* Top-left: Active Problems */}
      <QuadrantCard>
        <ActiveProblemsList
          conditions={profile.conditions}
          medications={profile.medications}
          onNavigate={onNavigate}
        />
      </QuadrantCard>

      {/* Top-right: Flagged Findings */}
      <QuadrantCard>
        <FlaggedFindings
          flags={flags}
          onResolve={handleResolveFlag}
          onNavigate={(recordRef) => {
            // Navigate to the appropriate view based on the record_ref prefix.
            // Format: "domain:id" — extract domain portion for tab navigation.
            const domain = recordRef.split(":")[0] ?? "";
            if (domain) onNavigate(domain);
          }}
        />
      </QuadrantCard>

      {/* Bottom-left: Pending Actions */}
      <QuadrantCard>
        <PendingActions
          tasks={tasks}
          followUps={followUps}
          onCompleteTask={handleCompleteTask}
          onCompleteFollowUp={handleCompleteFollowUp}
        />
      </QuadrantCard>

      {/* Bottom-right: Recent Decisions */}
      <QuadrantCard>
        <RecentDecisions decisions={decisions} />
      </QuadrantCard>
    </div>
  );
}
