<?php

namespace App\Services\RareDisease;

use App\Models\DiagnosticOdyssey;
use Illuminate\Support\Facades\DB;

class OdysseyService
{
    public function __construct(private OdysseyStateMachine $machine) {}

    public function create(array $data, int $actorId): DiagnosticOdyssey
    {
        return DB::transaction(function () use ($data, $actorId) {
            $odyssey = DiagnosticOdyssey::create([
                'patient_id' => $data['patient_id'],
                'case_id' => $data['case_id'] ?? null,
                'title' => $data['title'],
                'referral_reason' => $data['referral_reason'] ?? null,
                'status' => 'referral',
                'progress_status' => 'in_progress',
                'created_by' => $actorId,
            ]);

            $odyssey->transitions()->create([
                'from_status' => null,
                'to_status' => 'referral',
                'actor_id' => $actorId,
                'note' => 'Odyssey created',
            ]);

            return $odyssey;
        });
    }

    public function transition(DiagnosticOdyssey $odyssey, string $to, int $actorId, ?string $note = null): DiagnosticOdyssey
    {
        $from = $odyssey->status;

        if (! $this->machine->canTransition($from, $to)) {
            throw new InvalidOdysseyTransitionException($from, $to);
        }

        return DB::transaction(function () use ($odyssey, $from, $to, $actorId, $note) {
            $odyssey->update([
                'status' => $to,
                'progress_status' => $this->machine->progressStatusFor($to),
                'solved_at' => $to === 'diagnosed' ? now() : $odyssey->solved_at,
            ]);

            $odyssey->transitions()->create([
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $actorId,
                'note' => $note,
            ]);

            return $odyssey->fresh(['transitions']);
        });
    }
}
