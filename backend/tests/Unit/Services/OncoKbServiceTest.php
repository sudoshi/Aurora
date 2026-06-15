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

    it('calls parseAndUpsertTreatments and returns upserted count when response has treatments', function () {
        config(['services.oncokb.token' => 'test-token-123']);

        Http::fake([
            'oncokb.org/*' => Http::response([
                'treatments' => [
                    [
                        'drugs' => [['drugName' => 'Vemurafenib']],
                        'level' => 'LEVEL_1',
                        'description' => 'FDA-approved for BRAF V600E melanoma',
                        'levelAssociatedCancerType' => ['name' => 'Melanoma'],
                    ],
                ],
            ], 200),
        ]);

        GeneDrugInteraction::factory()->create(['gene' => 'BRAF', 'drug' => 'placeholder']);

        $service = new OncoKbService;
        $result = $service->syncInteractions();

        expect($result['synced'])->toBe(1);
        expect($result['upserted'])->toBe(1);

        // Verify the new record was created
        $record = GeneDrugInteraction::where('gene', 'BRAF')
            ->where('drug', 'vemurafenib')
            ->first();
        expect($record)->not->toBeNull();
        expect($record->evidence_level)->toBe('1');
        expect($record->relationship)->toBe('sensitive');
        expect($record->source)->toBe('oncokb');
    });
});

// --- parseAndUpsertTreatments ------------------------------------------------

describe('OncoKbService::parseAndUpsertTreatments', function () {
    it('creates GeneDrugInteraction with LEVEL_1 as sensitive', function () {
        $service = new OncoKbService;
        $treatments = [
            [
                'drugs' => [['drugName' => 'Vemurafenib']],
                'level' => 'LEVEL_1',
                'description' => 'FDA-approved for BRAF V600E melanoma',
                'levelAssociatedCancerType' => ['name' => 'Melanoma'],
            ],
        ];

        $result = $service->parseAndUpsertTreatments('BRAF', $treatments);

        expect($result['upserted'])->toBe(1);
        expect($result['skipped'])->toBe(0);

        $record = GeneDrugInteraction::where('gene', 'BRAF')
            ->where('drug', 'vemurafenib')
            ->first();
        expect($record)->not->toBeNull();
        expect($record->evidence_level)->toBe('1');
        expect($record->relationship)->toBe('sensitive');
        expect($record->source)->toBe('oncokb');
        expect($record->variant_pattern)->toBe('*');
        expect($record->indication)->toBe('Melanoma');
    });

    it('creates GeneDrugInteraction with LEVEL_R1 as resistant', function () {
        $service = new OncoKbService;
        $treatments = [
            [
                'drugs' => [['drugName' => 'Cetuximab']],
                'level' => 'LEVEL_R1',
                'description' => 'Resistance',
                'levelAssociatedCancerType' => ['name' => 'Colorectal Cancer'],
            ],
        ];

        $result = $service->parseAndUpsertTreatments('KRAS', $treatments);

        expect($result['upserted'])->toBe(1);

        $record = GeneDrugInteraction::where('gene', 'KRAS')
            ->where('drug', 'cetuximab')
            ->first();
        expect($record)->not->toBeNull();
        expect($record->evidence_level)->toBe('R1');
        expect($record->relationship)->toBe('resistant');
    });

    it('joins combo drug names with plus sign', function () {
        $service = new OncoKbService;
        $treatments = [
            [
                'drugs' => [
                    ['drugName' => 'Dabrafenib'],
                    ['drugName' => 'Trametinib'],
                ],
                'level' => 'LEVEL_1',
                'description' => 'Combo therapy',
                'levelAssociatedCancerType' => ['name' => 'Melanoma'],
            ],
        ];

        $result = $service->parseAndUpsertTreatments('BRAF', $treatments);

        expect($result['upserted'])->toBe(1);

        $record = GeneDrugInteraction::where('gene', 'BRAF')
            ->where('drug', 'dabrafenib + trametinib')
            ->first();
        expect($record)->not->toBeNull();
    });

    it('normalizes drug names by trimming and lowercasing', function () {
        $service = new OncoKbService;
        $treatments = [
            [
                'drugs' => [['drugName' => '  Erlotinib  ']],
                'level' => 'LEVEL_2A',
                'description' => 'Test',
                'levelAssociatedCancerType' => ['name' => 'NSCLC'],
            ],
        ];

        $result = $service->parseAndUpsertTreatments('EGFR', $treatments);

        $record = GeneDrugInteraction::where('gene', 'EGFR')
            ->where('drug', 'erlotinib')
            ->first();
        expect($record)->not->toBeNull();
        expect($record->evidence_level)->toBe('2A');
    });

    it('skips treatments with unknown evidence levels', function () {
        $service = new OncoKbService;
        $treatments = [
            [
                'drugs' => [['drugName' => 'SomeDrug']],
                'level' => 'LEVEL_UNKNOWN',
                'description' => 'Unknown',
                'levelAssociatedCancerType' => ['name' => 'Cancer'],
            ],
        ];

        $result = $service->parseAndUpsertTreatments('TP53', $treatments);

        expect($result['upserted'])->toBe(0);
        expect($result['skipped'])->toBe(1);

        expect(GeneDrugInteraction::where('gene', 'TP53')->where('drug', 'somedrug')->exists())->toBeFalse();
    });

    it('maps all 8 OncoKB evidence levels correctly', function () {
        $service = new OncoKbService;

        $levelMap = [
            'LEVEL_1' => '1',
            'LEVEL_2A' => '2A',
            'LEVEL_2B' => '2B',
            'LEVEL_3A' => '3A',
            'LEVEL_3B' => '3B',
            'LEVEL_4' => '4',
            'LEVEL_R1' => 'R1',
            'LEVEL_R2' => 'R2',
        ];

        foreach ($levelMap as $oncoKbLevel => $expectedLevel) {
            $treatments = [
                [
                    'drugs' => [['drugName' => "Drug-{$oncoKbLevel}"]],
                    'level' => $oncoKbLevel,
                    'description' => 'Test',
                    'levelAssociatedCancerType' => ['name' => 'TestCancer'],
                ],
            ];

            $result = $service->parseAndUpsertTreatments('TESTGENE', $treatments);
            expect($result['upserted'])->toBe(1, "Failed for level {$oncoKbLevel}");

            $record = GeneDrugInteraction::where('gene', 'TESTGENE')
                ->where('drug', strtolower("drug-{$oncoKbLevel}"))
                ->first();
            expect($record->evidence_level)->toBe($expectedLevel, "Level mapping failed for {$oncoKbLevel}");
        }
    });
});
