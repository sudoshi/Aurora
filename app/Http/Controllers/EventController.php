<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /**
     * Get paginated events with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'start_date', 'end_date', 'category', 'per_page']);

        $events = $this->eventService->list($filters);

        return ApiResponse::paginated($events);
    }

    /**
     * Get a specific event by ID.
     */
    public function show(Event $event): JsonResponse
    {
        $event->load(['teamMembers', 'patients']);

        $eventData = $event->toArray();

        $eventData['patients'] = $event->patients->map(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->name,
                'condition' => $patient->condition,
                'status' => $patient->status,
            ];
        })->toArray();

        $eventData['team_members'] = $event->teamMembers->map(function ($member) {
            return [
                'name' => $member->name,
                'role' => $member->pivot->role,
            ];
        })->toArray();

        return ApiResponse::success($eventData);
    }

    /**
     * Create a new event.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->validated());

        return ApiResponse::success($event, 'Event created successfully.', 201);
    }

    /**
     * Update an existing event.
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $updatedEvent = $this->eventService->update($event, $request->validated());

        return ApiResponse::success($updatedEvent, 'Event updated successfully.');
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event): JsonResponse
    {
        $this->eventService->delete($event);

        return ApiResponse::success(null, 'Event deleted successfully.');
    }

    /**
     * Get upcoming events.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 5), 20);

        $events = $this->eventService->getUpcoming($limit);

        return ApiResponse::success($events, 'Upcoming events retrieved.');
    }
}
