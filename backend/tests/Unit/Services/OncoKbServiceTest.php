<?php

use App\Models\Clinical\GeneDrugInteraction;
use App\Services\Genomics\OncoKbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// --- syncInteractions -----------------------------------------------------

describe('OncoKbService::syncInteractions', function () {
    it('returns skipped no_token when token not configured', function () {
        config(['services.oncokb.token' => null]);
        $service = new OncoKbService;

        $result = $service->syncInteractions();

        expect($result)->toHaveKey('skipped', 'no_token');
        expect($result['synced'])->toBe(0);
        expect($result['errors'])->toBe(0);
    });

    it('calls OncoKB API for each distinct gene and updates sync timestamp', function () {
        config(['services.oncokb.token' => 'test-token-123']);

        Http::fake([
            'oncokb.org/*' => Http::response(['data' => []], 200),
        ]);

        GeneDrugInteraction::factory()->create(['gene' => 'BRAF']);
        GeneDrugInteraction::factory()->create(['gene' => 'EGFR']);
        // Duplicate gene should not cause extra API call
        GeneDrugInteraction::factory()->create(['gene' => 'BRAF']);

        $service = new OncoKbService;
        $result = $service->syncInteractions();

        expect($result['synced'])->toBe(2);
        expect($result['errors'])->toBe(0);
        expect($result)->not->toHaveKey('skipped');

        // Verify timestamps updated
        $brafRecord = GeneDrugInteraction::where('gene', 'BRAF')->first();
        expect($brafRecord->oncokb_last_synced_at)->not->toBeNull();

        $egfrRecord = GeneDrugInteraction::where('gene', 'EGFR')->first();
        expect($egfrRecord->oncokb_last_synced_at)->not->toBeNull();
    });

    it('counts errors when API returns failure status', function () {
        config(['services.oncokb.token' => 'test-token-123']);

        Http::fake([
            'oncokb.org/*' => Http::response([], 500),
        ]);

        GeneDrugInteraction::factory()->create(['gene' => 'BRAF']);

        $service = new OncoKbService;
        $result = $service->syncInteractions();

        expect($result['synced'])->toBe(0);
        expect($result['errors'])->toBe(1);
    });

    it('handles exceptions gracefully and increments error count', function () {
        config(['services.oncokb.token' => 'test-token-123']);

        Http::fake([
            'oncokb.org/*' => function () {
                throw new \RuntimeException('Connection timeout');
            },
        ]);

        GeneDrugInteraction::factory()->create(['gene' => 'TP53']);

        $service = new OncoKbService;
        $result = $service->syncInteractions();

        expect($result['synced'])->toBe(0);
        expect($result['errors'])->toBe(1);
    });

    it('returns synced 0 errors 0 when no genes exist', function () {
        config(['services.oncokb.token' => 'test-token-123']);

        Http::fake();

        $service = new OncoKbService;
        $result = $service->syncInteractions();

        expect($result['synced'])->toBe(0);
        expect($result['errors'])->toBe(0);
    });
});
