import { useState, type FormEvent } from "react";
import { Plus, X, UserPlus, Users, Shield, Eye, Presentation } from "lucide-react";

import { useAddTeamMember, useRemoveTeamMember } from "../hooks/useCases";
import type { CaseTeamMember, TeamMemberRole } from "../types/case";

// ── Role config ──────────────────────────────────────────────────────────────

const ROLE_CONFIG: Record<
  TeamMemberRole,
  { label: string; color: string; icon: typeof Shield }
> = {
  presenter:   { label: "Presenter", color: "#2DD4BF", icon: Presentation },
  reviewer:    { label: "Reviewer", color: "#60A5FA", icon: Shield },
  observer:    { label: "Observer", color: "#7A8298", icon: Eye },
  coordinator: { label: "Coordinator", color: "#F59E0B", icon: UserPlus },
};

const ROLES: TeamMemberRole[] = ["presenter", "reviewer", "observer", "coordinator"];

// ── Add member modal ─────────────────────────────────────────────────────────

function AddMemberForm({
  caseId,
  onClose,
}: {
  caseId: number;
  onClose: () => void;
}) {
  const addMember = useAddTeamMember();
  const [userId, setUserId] = useState("");
  const [role, setRole] = useState<TeamMemberRole>("reviewer");

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const id = parseInt(userId, 10);
    if (isNaN(id) || id <= 0) return;

    addMember.mutate(
      { caseId, userId: id, role },
      { onSuccess: () => onClose() },
    );
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        onClick={onClose}
      />
      <div className="relative z-10 w-full max-w-sm rounded-xl border border-[#1C1C48] bg-[#16163A] shadow-xl">
        <div className="flex items-center justify-between border-b border-[#1C1C48] px-5 py-4">
          <h2 className="text-base font-semibold text-[#E8ECF4]">Add Team Member</h2>
          <button
            type="button"
            onClick={onClose}
            className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#222256] hover:text-[#7A8298]"
          >
            <X size={16} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4 px-5 py-4">
          <div className="form-group">
            <label htmlFor="member-user-id" className="form-label">
              User ID
            </label>
            <input
              id="member-user-id"
              type="number"
              value={userId}
              onChange={(e) => setUserId(e.target.value)}
              placeholder="Enter user ID"
              className="form-input"
              min={1}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="member-role" className="form-label">
              Role
            </label>
            <select
              id="member-role"
              value={role}
              onChange={(e) => setRole(e.target.value as TeamMemberRole)}
              className="form-input"
            >
              {ROLES.map((r) => (
                <option key={r} value={r}>
                  {ROLE_CONFIG[r].label}
                </option>
              ))}
            </select>
          </div>

          <div className="flex justify-end gap-3 border-t border-[#1C1C48] pt-4">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm text-[#7A8298] transition-colors hover:border-[#2A2A60] hover:text-[#B4BAC8]"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!userId || addMember.isPending}
              className="rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5] disabled:opacity-50"
            >
              {addMember.isPending ? "Adding..." : "Add Member"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

// ── Main panel ───────────────────────────────────────────────────────────────

interface CaseTeamPanelProps {
  caseId: number;
  createdBy: number;
  teamMembers: CaseTeamMember[];
}

export function CaseTeamPanel({
  caseId,
  createdBy,
  teamMembers,
}: CaseTeamPanelProps) {
  const [showAddForm, setShowAddForm] = useState(false);
  const removeMember = useRemoveTeamMember();

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-[#B4BAC8]">
          Team Members
          <span className="ml-2 font-['IBM_Plex_Mono',monospace] text-xs text-[#4A5068]">
            ({teamMembers.length})
          </span>
        </h3>
        <button
          type="button"
          onClick={() => setShowAddForm(true)}
          className="inline-flex items-center gap-1.5 rounded-lg bg-[#2DD4BF] px-3 py-1.5 text-xs font-semibold text-[#0A0A18] transition-colors hover:bg-[#25B8A5]"
        >
          <Plus size={12} />
          Add Member
        </button>
      </div>

      {/* Member list */}
      {teamMembers.length > 0 ? (
        <div className="space-y-2">
          {teamMembers.map((member) => {
            const config = ROLE_CONFIG[member.role];
            const Icon = config.icon;
            const initials = member.user?.name
              ? member.user.name
                  .split(" ")
                  .map((w) => w[0])
                  .join("")
                  .slice(0, 2)
                  .toUpperCase()
              : "??";
            const isCreator = member.user_id === createdBy;

            return (
              <div
                key={member.id}
                className="flex items-center justify-between rounded-lg border border-[#1C1C48] bg-[#16163A] p-3"
              >
                <div className="flex items-center gap-3">
                  {member.user?.avatar ? (
                    <img
                      src={member.user.avatar}
                      alt={member.user.name}
                      className="h-8 w-8 rounded-full"
                    />
                  ) : (
                    <div
                      className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                      style={{ backgroundColor: `${config.color}15`, color: config.color }}
                    >
                      {initials}
                    </div>
                  )}
                  <div>
                    <p className="text-sm font-medium text-[#B4BAC8]">
                      {member.user?.name ?? `User #${member.user_id}`}
                    </p>
                    <div className="flex items-center gap-1.5">
                      <Icon size={10} style={{ color: config.color }} />
                      <span
                        className="text-[10px] font-medium"
                        style={{ color: config.color }}
                      >
                        {config.label}
                      </span>
                      {member.user?.email && (
                        <span className="font-['IBM_Plex_Mono',monospace] text-[10px] text-[#4A5068]">
                          {member.user.email}
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                {!isCreator && (
                  <button
                    type="button"
                    onClick={() =>
                      removeMember.mutate({ caseId, userId: member.user_id })
                    }
                    disabled={removeMember.isPending}
                    title="Remove member"
                    className="flex h-7 w-7 items-center justify-center rounded-md text-[#4A5068] transition-colors hover:bg-[#00D68F15] hover:text-[#F0607A]"
                  >
                    <X size={14} />
                  </button>
                )}
              </div>
            );
          })}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] py-12">
          <Users size={24} className="mb-2 text-[#4A5068]" />
          <p className="text-sm text-[#7A8298]">No team members yet</p>
          <p className="mt-1 text-xs text-[#4A5068]">
            Add members to collaborate on this case.
          </p>
        </div>
      )}

      {/* Add member modal */}
      {showAddForm && (
        <AddMemberForm
          caseId={caseId}
          onClose={() => setShowAddForm(false)}
        />
      )}
    </div>
  );
}
