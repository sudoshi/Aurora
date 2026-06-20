<?php

use App\Models\User;
use App\Models\UserAuditLog;

it('records a phi.access audit row when a clinician reads patient data', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/patients')
        ->assertOk();

    $log = UserAuditLog::query()
        ->where('user_id', $user->id)
        ->where('action', 'phi.access')
        ->where('feature', 'patients')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->metadata['method'] ?? null)->toBe('GET');
    expect($log->metadata['path'] ?? null)->toBe('api/patients');
});

it('does not record a phi.access audit row for non-PHI endpoints', function () {
    $this->getJson('/api/health')->assertOk();

    expect(UserAuditLog::where('action', 'phi.access')->count())->toBe(0);
});

it('does not record a phi.access audit row for unauthenticated requests', function () {
    $this->getJson('/api/patients')->assertUnauthorized();

    expect(UserAuditLog::where('action', 'phi.access')->count())->toBe(0);
});

it('records a phi.write audit row when a clinician creates a patient', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/patients', [
        'mrn' => 'MRN-'.uniqid(),
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    expect($response->status())->toBeLessThan(400);

    $log = UserAuditLog::query()
        ->where('user_id', $user->id)
        ->where('action', 'phi.write')
        ->where('feature', 'patients')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->metadata['method'] ?? null)->toBe('POST');
});

it('does not record an audit row for a 4xx response', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/patients/99999999/profile')
        ->assertNotFound();

    expect(UserAuditLog::where('action', 'phi.access')->count())->toBe(0);
});

it('does not break the response when the audit write throws', function () {
    $user = User::factory()->create();

    // Force the audit insert to fail for the duration of this test only.
    UserAuditLog::saving(function () {
        throw new \RuntimeException('db down');
    });

    try {
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/patients');

        expect($response->status())->toBeLessThan(500);
        $response->assertOk();
    } finally {
        // Don't let the saving hook leak into other tests.
        UserAuditLog::flushEventListeners();
    }
});
