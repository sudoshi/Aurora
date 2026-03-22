<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EventService
{
    /**
     * List events with optional filters and pagination.
     *
     * @param  array{search?: string, start_date?: string, end_date?: string, category?: string, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Event::with(['teamMembers', 'patients']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['start_date'])) {
            $query->where('time', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('time', '<=', $filters['end_date']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderBy('time', 'desc')->paginate($perPage);
    }

    /**
     * Find a single event by ID with relationships.
     */
    public function find(int $id): Event
    {
        return Event::with(['teamMembers', 'patients'])->findOrFail($id);
    }

    /**
     * Create a new event and attach relationships.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Event
    {
        $event = Event::create($data);

        if (isset($data['team_members'])) {
            foreach ($data['team_members'] as $teamMember) {
                $event->teamMembers()->attach(
                    $teamMember['user_id'],
                    ['role' => $teamMember['role']]
                );
            }
        }

        if (isset($data['patient_ids'])) {
            $event->patients()->sync($data['patient_ids']);
        }

        return $event->load(['teamMembers', 'patients']);
    }

    /**
     * Update an existing event and its relationships.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        if (isset($data['team_members'])) {
            $teamMemberIds = [];
            foreach ($data['team_members'] as $teamMember) {
                $teamMemberIds[$teamMember['user_id']] = ['role' => $teamMember['role']];
            }
            $event->teamMembers()->sync($teamMemberIds);
        }

        if (isset($data['patient_ids'])) {
            $event->patients()->sync($data['patient_ids']);
        }

        return $event->load(['teamMembers', 'patients']);
    }

    /**
     * Delete an event.
     */
    public function delete(Event $event): void
    {
        $event->delete();
    }

    /**
     * Get upcoming events.
     */
    public function getUpcoming(int $limit = 5): Collection
    {
        return Event::with(['teamMembers', 'patients'])
            ->where('time', '>=', now())
            ->orderBy('time', 'asc')
            ->limit($limit)
            ->get();
    }
}
