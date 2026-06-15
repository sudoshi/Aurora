<?php

use App\Models\Event;
use App\Models\Patient;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new EventService;
});

describe('EventService::list', function () {
    it('returns paginated results', function () {
        Event::factory()->count(3)->create();

        $result = $this->service->list();

        expect($result->total())->toBe(3)
            ->and($result->perPage())->toBe(15);
    });

    it('applies search filter across searchable fields', function () {
        Event::factory()->create(['title' => 'Tumor Board Meeting']);
        Event::factory()->create(['title' => 'Staff Huddle']);

        $result = $this->service->list(['search' => 'Tumor']);

        expect($result->total())->toBe(1)
            ->and($result->items()[0]->title)->toBe('Tumor Board Meeting');
    });

    it('applies date range filters', function () {
        Event::factory()->create(['time' => '2025-12-31 09:00:00']);
        Event::factory()->create(['time' => '2026-06-01 09:00:00']);
        Event::factory()->create(['time' => '2027-01-01 09:00:00']);

        $result = $this->service->list([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        expect($result->total())->toBe(1)
            ->and($result->items()[0]->time->format('Y-m-d'))->toBe('2026-06-01');
    });

    it('respects per_page filter capped at 100', function () {
        Event::factory()->count(105)->create();

        $result = $this->service->list(['per_page' => 500]);

        expect($result->perPage())->toBe(100)
            ->and(count($result->items()))->toBe(100);
    });
});

describe('EventService::create', function () {
    it('creates an event and syncs optional relationships', function () {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $result = $this->service->create([
            'title' => 'Tumor Board',
            'time' => '2026-04-01 09:00:00',
            'duration' => 60,
            'location' => 'Conference Room A',
            'category' => 'clinical',
            'team_members' => [
                ['user_id' => $user->id, 'role' => 'presenter'],
            ],
            'patient_ids' => [$patient->id],
        ]);

        expect($result->title)->toBe('Tumor Board')
            ->and($result->relationLoaded('teamMembers'))->toBeTrue()
            ->and($result->teamMembers)->toHaveCount(1)
            ->and($result->teamMembers->first()->pivot->role)->toBe('presenter')
            ->and($result->relationLoaded('patients'))->toBeTrue()
            ->and($result->patients)->toHaveCount(1);

        $this->assertDatabaseHas('dev.events', ['title' => 'Tumor Board']);
        $this->assertDatabaseHas('dev.event_team_members', [
            'event_id' => $result->id,
            'user_id' => $user->id,
            'role' => 'presenter',
        ]);
        $this->assertDatabaseHas('dev.event_patients', [
            'event_id' => $result->id,
            'patient_id' => $patient->id,
        ]);
    });
});

describe('EventService::update', function () {
    it('updates an event and resyncs relationships', function () {
        $event = Event::factory()->create(['title' => 'Original Title']);
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $result = $this->service->update($event, [
            'title' => 'Updated Title',
            'team_members' => [
                ['user_id' => $user->id, 'role' => 'reviewer'],
            ],
            'patient_ids' => [$patient->id],
        ]);

        expect($result->title)->toBe('Updated Title')
            ->and($result->teamMembers)->toHaveCount(1)
            ->and($result->teamMembers->first()->pivot->role)->toBe('reviewer')
            ->and($result->patients)->toHaveCount(1);
    });
});

describe('EventService::delete', function () {
    it('deletes an event', function () {
        $event = Event::factory()->create();

        $this->service->delete($event);

        $this->assertDatabaseMissing('dev.events', ['id' => $event->id]);
    });
});

describe('EventService::getUpcoming', function () {
    it('returns upcoming events ordered by time ascending', function () {
        Event::factory()->create(['title' => 'Past', 'time' => now()->subDay()]);
        Event::factory()->create(['title' => 'Second', 'time' => now()->addDays(2)]);
        Event::factory()->create(['title' => 'First', 'time' => now()->addDay()]);

        $result = $this->service->getUpcoming();

        expect($result)->toHaveCount(2)
            ->and($result->pluck('title')->all())->toBe(['First', 'Second']);
    });

    it('accepts a custom limit', function () {
        Event::factory()->count(3)->create(['time' => now()->addDay()]);

        $result = $this->service->getUpcoming(2);

        expect($result)->toHaveCount(2);
    });
});
