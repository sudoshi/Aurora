<?php

use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->admin = User::where('email', 'admin@acumenus.net')->first();
});

describe('GET /api/admin/system-health', function () {
    it('requires authentication', function () {
        $this->getJson('/api/admin/system-health')->assertStatus(401);
    });

    it('returns a services array including the new dependency checkers', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/system-health');

        $response->assertStatus(200);

        $services = $response->json('services');
        expect($services)->toBeArray();

        $byKey = collect($services)->keyBy('key');

        foreach (['orthanc', 'federation', 'reverb'] as $key) {
            expect($byKey->has($key))->toBeTrue("missing checker: {$key}");

            $service = $byKey->get($key);
            expect($service['status'])->toBeString();
            expect($service['status'])->toBeIn(['healthy', 'degraded', 'down']);
        }
    });

    it('returns a single service with metrics via show()', function () {
        foreach (['orthanc', 'federation', 'reverb'] as $key) {
            $response = $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/admin/system-health/{$key}");

            $response->assertStatus(200);
            expect($response->json('service.key'))->toBe($key);
            expect($response->json('service.status'))->toBeIn(['healthy', 'degraded', 'down']);
            expect($response->json('metrics'))->toBeArray();
        }
    });
});
