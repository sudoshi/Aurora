<?php

namespace App\Http\Controllers;

use App\Models\Case;
use App\Models\TeamMember;
use App\Models\VideoSession;
use App\Models\Schedule;
use App\Events\SessionScheduled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamSchedulingController extends Controller
{
    public function getTeamAvailability(Request $request, Case $case)
    {
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        // Get team members' schedules
        $schedules = Schedule::whereHas('user', function ($query) use ($case) {
            $query->whereHas('teams', function ($q) use ($case) {
                $q->where('cases.id', $case->id);
            });
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->with(['user', 'availabilityBlocks'])
        ->get();

        // Get existing sessions
        $existingSessions = VideoSession::where('case_id', $case->id)
            ->whereBetween('scheduled_start', [$startDate, $endDate])
            ->get();

        // Process schedules into availability slots
        $availability = [];
        foreach ($schedules as $schedule) {
            $availability[$schedule->user_id] = $this->processAvailability(
                $schedule,
                $existingSessions
            );
        }

        // Find common available time slots
        $commonSlots = $this->findCommonAvailability($availability);

        return response()->json([
            'individual_availability' => $availability,
            'common_slots' => $commonSlots
        ]);
    }

    protected function processAvailability($schedule, $existingSessions)
    {
        $slots = [];
        foreach ($schedule->availabilityBlocks as $block) {
            $start = Carbon::parse($block->start_time);
            $end = Carbon::parse($block->end_time);

            // Check for conflicts with existing sessions
            $conflicts = $existingSessions->filter(function ($session) use ($start, $end) {
                $sessionStart = Carbon::parse($session->scheduled_start);
                $sessionEnd = Carbon::parse($session->scheduled_end);
                return $sessionStart->between($start, $end) || 
                       $sessionEnd->between($start, $end);
            });

            if ($conflicts->isEmpty()) {
                $slots[] = [
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    'type' => $block->type // regular, on-call, etc.
                ];
            }
        }

        return $slots;
    }

    protected function findCommonAvailability($individualAvailability)
    {
        if (empty($individualAvailability)) {
            return [];
        }

        // Start with the first person's availability
        $commonSlots = array_shift($individualAvailability);

        foreach ($individualAvailability as $userSlots) {
            $commonSlots = $this->findOverlappingSlots($commonSlots, $userSlots);
        }

        return $commonSlots;
    }

    protected function findOverlappingSlots($slots1, $slots2)
    {
        $overlapping = [];

        foreach ($slots1 as $slot1) {
            $start1 = Carbon::parse($slot1['start']);
            $end1 = Carbon::parse($slot1['end']);

            foreach ($slots2 as $slot2) {
                $start2 = Carbon::parse($slot2['start']);
                $end2 = Carbon::parse($slot2['end']);

                $overlapStart = $start1->max($start2);
                $overlapEnd = $end1->min($end2);

                if ($overlapStart < $overlapEnd) {
                    $overlapping[] = [
                        'start' => $overlapStart->toIso8601String(),
                        'end' => $overlapEnd->toIso8601String(),
                        'type' => 'overlap'
                    ];
                }
            }
        }

        return $overlapping;
    }

    public function scheduleSession(Request $request, Case $case)
    {
        $validated = $request->validate([
            'scheduled_start' => 'required|date',
            'duration' => 'required|integer|min:15|max:240',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'session_type' => 'required|in:video,in-person,hybrid'
        ]);

        DB::beginTransaction();
        try {
            // Calculate end time
            $startTime = Carbon::parse($validated['scheduled_start']);
            $endTime = $startTime->copy()->addMinutes($validated['duration']);

            // Check availability for all participants
            $this->validateParticipantAvailability(
                $validated['participant_ids'],
                $startTime,
                $endTime
            );

            // Create video session
            $session = VideoSession::create([
                'case_id' => $case->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'session_type' => $validated['session_type'],
                'scheduled_start' => $startTime,
                'scheduled_end' => $endTime,
                'status' => 'scheduled',
                'created_by' => auth()->id()
            ]);

            // Attach participants
            $session->participants()->attach($validated['participant_ids']);

            // Create video conference
            if ($validated['session_type'] !== 'in-person') {
                $conference = $this->createVideoConference($session);
                $session->update(['conference_id' => $conference->id]);
            }

            // Send notifications
            $this->notifyParticipants($session);

            DB::commit();

            broadcast(new SessionScheduled($session))->toOthers();

            return response()->json($session->load('participants'));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function validateParticipantAvailability($participantIds, $startTime, $endTime)
    {
        $conflicts = Schedule::whereIn('user_id', $participantIds)
            ->where('date', $startTime->toDateString())
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>=', $startTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<=', $endTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->count();

        if ($conflicts > 0) {
            throw new \Exception('One or more participants have scheduling conflicts.');
        }
    }

    protected function createVideoConference($session)
    {
        // Initialize video service provider
        $videoService = app(VideoConferenceService::class);

        // Create conference
        $conference = $videoService->createConference([
            'title' => $session->title,
            'start_time' => $session->scheduled_start,
            'duration' => $session->getDurationInMinutes(),
            'participants' => $session->participants->map(function ($participant) {
                return [
                    'name' => $participant->name,
                    'email' => $participant->email,
                    'role' => $participant->pivot->role
                ];
            })->toArray()
        ]);

        return $conference;
    }

    protected function notifyParticipants($session)
    {
        $session->participants->each(function ($participant) use ($session) {
            $participant->notify(new SessionScheduledNotification($session));
        });
    }
}