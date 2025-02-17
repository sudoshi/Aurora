php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Get all events
     */
    public function index(): JsonResponse
    {
        \Log::info('Fetching all events');
        try {
            $events = Event::with(['teamMembers', 'patients'])->get();
            return response()->json($events);
        } catch (\Exception $e) {
            \Log::error('Error fetching events', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get a specific event by ID
     */
    public function show(Event $event): JsonResponse
    {
        try {
            $event->load(['teamMembers']);
            
            // Get the event data with the patients JSON
            $eventData = $event->toArray();
            
            // Ensure patients data is properly decoded
            if (is_string($eventData['patients'])) {
                $eventData['patients'] = json_decode($eventData['patients'], true);
            }
            
            return response()->json($eventData);
        } catch (\Exception $e) {
            \Log::error('Error fetching event', [

                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }


    /**
     * Create a new event
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'time' => 'required|date',
            'duration' => 'required|integer',
            'location' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team_members' => 'nullable|array', // Changed to team_members
            'team_members.*.user_id' => 'required|exists:users,id', // Ensure user_id exists
            'team_members.*.role' => 'nullable|string',
            'patient_ids' => 'nullable|array', // Changed to patient_ids
            'patient_ids.*' => 'required|exists:patients,id', // Ensure patient_id exists
        ]);

        $event = Event::create($validated);

        // Add team members
        if (isset($validated['team_members'])) {
            foreach ($validated['team_members'] as $teamMember) {
                $event->teamMembers()->attach($teamMember['user_id'], ['role' => $teamMember['role']]);
            }
        }

        // Add patients
        if (isset($validated['patient_ids'])) {
            $event->patients()->sync($validated['patient_ids']);
        }

        return response()->json($event, 201);
    }

    /**
     * Update an existing event
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'time' => 'sometimes|date',
            'duration' => 'sometimes|integer',
            'location' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'team_members' => 'nullable|array', // Changed to team_members
            'team_members.*.user_id' => 'required|exists:users,id', // Ensure user_id exists
            'team_members.*.role' => 'nullable|string',
            'patient_ids' => 'nullable|array', // Changed to patient_ids
            'patient_ids.*' => 'required|exists:patients,id', // Ensure patient_id exists
        ]);

        $event->update($validated);

        // Update team members
        if (isset($validated['team_members'])) {
            $teamMemberIds = [];
            foreach ($validated['team_members'] as $teamMember) {
                $teamMemberIds[$teamMember['user_id']] = ['role' => $teamMember['role']];
            }
            $event->teamMembers()->sync($teamMemberIds);
        }

        // Update patients
        if (isset($validated['patient_ids'])) {
            $event->patients()->sync($validated['patient_ids']);
        }


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
