<?php

use App\Models\CaseTeamMember;
use App\Models\Clinical\ClinicalPatient;
use App\Models\ClinicalCase;
use App\Models\User;
use App\Services\CaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CaseService;
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
});

// --- createCase -----------------------------------------------------------

describe('CaseService::createCase', function () {
    it('creates a case with created_by set to the given userId', function () {
        $case = $this->service->createCase([
            'title' => 'Test Case',
            'patient_id' => $this->patient->id,
            'specialty' => 'oncology',
            'case_type' => 'tumor_board',
            'urgency' => 'routine',
            'status' => 'active',
        ], $this->user->id);

        expect($case)->toBeInstanceOf(ClinicalCase::class);
        expect($case->created_by)->toBe($this->user->id);
        expect($case->title)->toBe('Test Case');
    });

    it('auto-creates a CaseTeamMember with role coordinator for the creator', function () {
        $case = $this->service->createCase([
            'title' => 'Auto Coordinator Test',
            'patient_id' => $this->patient->id,
            'specialty' => 'oncology',
            'case_type' => 'tumor_board',
            'urgency' => 'routine',
            'status' => 'active',
        ], $this->user->id);

        $member = CaseTeamMember::where('case_id', $case->id)
            ->where('user_id', $this->user->id)
            ->where('role', 'coordinator')
            ->first();

        expect($member)->not->toBeNull();
        expect($member->invited_at)->not->toBeNull();
        expect($member->accepted_at)->not->toBeNull();
    });

    it('returns the case with creator and teamMembers loaded', function () {
        $case = $this->service->createCase([
            'title' => 'Loaded Relations Test',
            'patient_id' => $this->patient->id,
            'specialty' => 'oncology',
            'case_type' => 'tumor_board',
            'urgency' => 'routine',
            'status' => 'active',
        ], $this->user->id);

        expect($case->relationLoaded('creator'))->toBeTrue();
        expect($case->relationLoaded('teamMembers'))->toBeTrue();
        expect($case->creator->id)->toBe($this->user->id);
        expect($case->teamMembers)->toHaveCount(1);
    });
});

// --- updateCase -----------------------------------------------------------

describe('CaseService::updateCase', function () {
    it('updates fields and returns a fresh case', function () {
        $case = ClinicalCase::factory()->create([
            'title' => 'Original Title',
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);

        $updated = $this->service->updateCase($case, ['title' => 'Updated Title']);

        expect($updated->title)->toBe('Updated Title');
        expect($updated->relationLoaded('creator'))->toBeTrue();
    });
});

// --- archiveCase ----------------------------------------------------------

describe('CaseService::archiveCase', function () {
    it('sets status to archived and closed_at to current time', function () {
        Carbon::setTestNow('2026-06-15 10:30:00');

        $case = ClinicalCase::factory()->create([
            'status' => 'active',
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);

        $archived = $this->service->archiveCase($case);

        expect($archived->status)->toBe('archived');
        expect($archived->closed_at)->not->toBeNull();
        expect($archived->closed_at->toDateTimeString())->toBe('2026-06-15 10:30:00');

        Carbon::setTestNow(); // reset
    });
});

// --- addTeamMember --------------------------------------------------------

describe('CaseService::addTeamMember', function () {
    it('creates a CaseTeamMember record with the correct role', function () {
        $case = ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);
        $otherUser = User::factory()->create();

        $member = $this->service->addTeamMember($case, $otherUser->id, 'specialist');

        expect($member)->toBeInstanceOf(CaseTeamMember::class);
        expect($member->role)->toBe('specialist');
        expect($member->user_id)->toBe($otherUser->id);
        expect($member->case_id)->toBe($case->id);
    });

    it('throws InvalidArgumentException for duplicate user', function () {
        $case = ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);
        $otherUser = User::factory()->create();

        // Add once
        CaseTeamMember::create([
            'case_id' => $case->id,
            'user_id' => $otherUser->id,
            'role' => 'specialist',
            'invited_at' => now(),
        ]);

        // Attempt duplicate
        expect(fn () => $this->service->addTeamMember($case, $otherUser->id, 'specialist'))
            ->toThrow(\InvalidArgumentException::class, 'User is already a team member');
    });
});

// --- removeTeamMember -----------------------------------------------------

describe('CaseService::removeTeamMember', function () {
    it('deletes the team member record', function () {
        $case = ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);
        $otherUser = User::factory()->create();

        CaseTeamMember::create([
            'case_id' => $case->id,
            'user_id' => $otherUser->id,
            'role' => 'specialist',
            'invited_at' => now(),
        ]);

        $this->service->removeTeamMember($case, $otherUser->id);

        expect(CaseTeamMember::where('case_id', $case->id)
            ->where('user_id', $otherUser->id)
            ->exists()
        )->toBeFalse();
    });

    it('throws InvalidArgumentException when removing the creator', function () {
        $case = ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);

        expect(fn () => $this->service->removeTeamMember($case, $this->user->id))
            ->toThrow(\InvalidArgumentException::class, 'Cannot remove the case creator');
    });

    it('throws InvalidArgumentException when user is not a team member', function () {
        $case = ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
        ]);
        $otherUser = User::factory()->create();

        expect(fn () => $this->service->removeTeamMember($case, $otherUser->id))
            ->toThrow(\InvalidArgumentException::class, 'User is not a team member');
    });
});

// --- getCasesForUser ------------------------------------------------------

describe('CaseService::getCasesForUser', function () {
    it('returns cases where user is the creator', function () {
        ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
            'status' => 'active',
        ]);
        // Another user's case
        ClinicalCase::factory()->create(['patient_id' => $this->patient->id]);

        $results = $this->service->getCasesForUser($this->user->id);

        expect($results->total())->toBe(1);
    });

    it('returns cases where user is a team member', function () {
        $otherUser = User::factory()->create();
        $case = ClinicalCase::factory()->create([
            'created_by' => $otherUser->id,
            'patient_id' => $this->patient->id,
        ]);
        CaseTeamMember::create([
            'case_id' => $case->id,
            'user_id' => $this->user->id,
            'role' => 'specialist',
            'invited_at' => now(),
        ]);

        $results = $this->service->getCasesForUser($this->user->id);

        expect($results->total())->toBe(1);
    });

    it('filters by status', function () {
        ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
            'status' => 'active',
        ]);
        ClinicalCase::factory()->create([
            'created_by' => $this->user->id,
            'patient_id' => $this->patient->id,
            'status' => 'closed',
        ]);

        $results = $this->service->getCasesForUser($this->user->id, ['status' => 'active']);

        expect($results->total())->toBe(1);
        expect($results->first()->status)->toBe('active');
    });
});
