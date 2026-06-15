<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

describe('GET /api/dashboard/stats', function () {
    it('returns dashboard statistics', function () {
        ClinicalPatient::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'total_patients',
                    'total_cases',
                    'active_cases',
                    'active_users',
                    'total_users',
                    'pending_decisions',
                    'recent_cases',
                    'system_health',
                ],
            ]);

        expect($response->json('data.total_patients'))->toBeGreaterThanOrEqual(3);
    });

    it('includes system health status', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.system_health.database', 'healthy')
            ->assertJsonPath('data.system_health.cache', 'healthy');
    });

    it('requires authentication', function () {
        $this->getJson('/api/dashboard/stats')->assertStatus(401);
    });
});
