<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    /**
     * Get a specific event by ID
     */
    public function show(Event $event): JsonResponse
    {
        // Convert the event data to match the frontend format
        $eventData = [
            'id' => $event->id,
            'title' => $event->title,
            'time' => $event->time,
            'duration' => $event->duration,
            'location' => $event->location,
            'category' => $event->category,
            'description' => $event->description,
            'team' => $event->team ?? [],
            'patients' => $event->patients ?? [],
            'relatedItems' => $event->related_items ?? []
        ];

        return response()->json($eventData);
    }

    /**
     * Create a new event
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'time' => 'required|string',
            'duration' => 'required|integer',
            'location' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team' => 'nullable|array',
            'patients' => 'nullable|array',
            'related_items' => 'nullable|array',
        ]);

        $event = Event::create($validated);

        return response()->json($event, 201);
    }

    /**
     * Update an existing event
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'time' => 'sometimes|string',
            'duration' => 'sometimes|integer',
            'location' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'team' => 'nullable|array',
            'patients' => 'nullable|array',
            'related_items' => 'nullable|array',
        ]);

        $event->update($validated);

        return response()->json($event);
    }

    /**
     * Delete an event
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();
        return response()->json(null, 204);
    }
}
