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
