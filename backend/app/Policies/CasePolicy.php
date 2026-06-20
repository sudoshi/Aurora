<?php

namespace App\Policies;

use App\Models\CaseTeamMember;
use App\Models\ClinicalCase;
use App\Models\User;

/**
 * Cases are the team-scoped entity in Aurora's "open clinical workspace" model
 * (decision D1): patient/genomics/imaging records are broadly visible to
 * authenticated clinical users, but a CASE — its discussion, decisions, and
 * curated context — is restricted to its creator + invited team members
 * (mirrors the ClinicalCase::forUser scope used by the listing). Admins retain
 * an override for governance.
 */
class CasePolicy
{
    public function view(User $user, ClinicalCase $case): bool
    {
        return $this->creatorOrMember($user, $case);
    }

    public function update(User $user, ClinicalCase $case): bool
    {
        return $this->creatorOrMember($user, $case);
    }

    /** Archiving a case is reserved to its creator (or an admin). */
    public function delete(User $user, ClinicalCase $case): bool
    {
        return $case->created_by === $user->id || $this->isAdmin($user);
    }

    /** Managing the team requires case access. */
    public function manageTeam(User $user, ClinicalCase $case): bool
    {
        return $this->creatorOrMember($user, $case);
    }

    private function creatorOrMember(User $user, ClinicalCase $case): bool
    {
        if ($case->created_by === $user->id || $this->isAdmin($user)) {
            return true;
        }

        return CaseTeamMember::where('case_id', $case->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
