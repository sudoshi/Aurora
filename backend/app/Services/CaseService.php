<?php

namespace App\Services;

use App\Models\CaseTeamMember;
use App\Models\ClinicalCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class CaseService
{
    /**
     * Create a new clinical case and auto-add the creator as coordinator.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCase(array $data, int $userId): ClinicalCase
    {
        $case = ClinicalCase::create(array_merge($data, [
            'created_by' => $userId,
        ]));

        // Auto-add creator as coordinator
        CaseTeamMember::create([
            'case_id' => $case->id,
            'user_id' => $userId,
            'role' => 'coordinator',
            'invited_at' => Carbon::now(),
            'accepted_at' => Carbon::now(),
        ]);

        return $case->load(['creator', 'teamMembers.user']);
    }

    /**
     * Update an existing clinical case.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCase(ClinicalCase $case, array $data): ClinicalCase
    {
        $case->update($data);

        return $case->fresh(['creator', 'teamMembers.user']);
    }

    /**
     * Archive a clinical case.
     */
    public function archiveCase(ClinicalCase $case): ClinicalCase
    {
        $case->update([
            'status' => 'archived',
            'closed_at' => Carbon::now(),
        ]);

        return $case->fresh();
    }

    /**
     * Add a team member to a case, preventing duplicates.
     */
    public function addTeamMember(ClinicalCase $case, int $userId, string $role): CaseTeamMember
    {
        $existing = CaseTeamMember::where('case_id', $case->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('User is already a team member of this case.');
        }

        return CaseTeamMember::create([
            'case_id' => $case->id,
            'user_id' => $userId,
            'role' => $role,
            'invited_at' => Carbon::now(),
        ]);
    }

    /**
     * Remove a team member from a case (cannot remove the creator).
     */
    public function removeTeamMember(ClinicalCase $case, int $userId): void
    {
        if ((int) $case->created_by === $userId) {
            throw new \InvalidArgumentException('Cannot remove the case creator from the team.');
        }

        $member = CaseTeamMember::where('case_id', $case->id)
            ->where('user_id', $userId)
            ->first();

        if (! $member) {
            throw new \InvalidArgumentException('User is not a team member of this case.');
        }

        $member->delete();
    }

    /**
     * Get paginated cases for a user with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getCasesForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = ClinicalCase::forUser($userId)
            ->with(['creator', 'patient'])
            ->withCount(['teamMembers', 'discussions', 'annotations', 'documents', 'decisions']);

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['specialty'])) {
            $query->bySpecialty($filters['specialty']);
        }

        if (! empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        if (! empty($filters['search'])) {
            $query->where('title', 'ilike', '%'.$filters['search'].'%');
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
