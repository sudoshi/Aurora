<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
});

describe('GET /api/events', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/events');

        $response->assertStatus(401);
    });

    it('returns paginated event list', function () {
        Event::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['total', 'page', 'per_page', 'last_page'],
            ]);
    });

    it('filters events by search term', function () {
        Event::factory()->create(['title' => 'Tumor Board Meeting']);
        Event::factory()->create(['title' => 'Staff Huddle']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/events?search=Tumor');

        $response->assertStatus(200);
    });

    it('filters events by date range', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/events?start_date=2026-01-01&end_date=2026-12-31');

        $response->assertStatus(200);
    });
});

describe('POST /api/events', function () {
    it('creates an event with valid data', function () {
        $payload = [
            'title' => 'New Clinical Review',
            'time' => '2026-04-15 14:00:00',
            'duration' => 60,
            'location' => 'Conference Room A',
            'category' => 'clinical',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/events', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('dev.events', [
            'title' => 'New Clinical Review',
        ]);
    });

    it('returns 422 for missing required fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/events', []);

        $response->assertStatus(422);
    });

    it('returns 422 for invalid time format', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/events', [
                'title' => 'Test Event',
                'time' => 'not-a-date',
                'duration' => 60,
                'location' => 'Room B',
                'category' => 'clinical',
            ]);

        $response->assertStatus(422);
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/events', [
            'title' => 'Unauthorized Event',
        ]);

        $response->assertStatus(401);
    });
});

describe('PUT /api/events/{id}', function () {
    it('updates an existing event', function () {
        $event = Event::factory()->create([
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Updated Title',
                'time' => '2026-04-15 14:00:00',
                'duration' => 90,
                'location' => 'Room C',
                'category' => 'administrative',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('returns 404 for non-existent event', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/events/99999', [
                'title' => 'Ghost Event',
                'time' => '2026-04-15 14:00:00',
                'duration' => 60,
                'location' => 'Room D',
                'category' => 'clinical',
            ]);

        $response->assertStatus(404);
    });
});

describe('DELETE /api/events/{id}', function () {
    it('deletes an existing event', function () {
        $event = Event::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('dev.events', [
            'id' => $event->id,
        ]);
    });

    it('returns 404 for non-existent event', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/events/99999');

        $response->assertStatus(404);
    });
});

describe('GET /api/events/upcoming', function () {
    it('returns upcoming events', function () {
        Event::factory()->create([
            'time' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/events/upcoming');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('respects limit parameter', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/events/upcoming?limit=3');

        $response->assertStatus(200);
    });
});
