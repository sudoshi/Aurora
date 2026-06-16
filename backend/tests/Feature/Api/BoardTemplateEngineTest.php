<?php

use App\Models\CaseTemplate;
use Database\Seeders\SpecialtyTemplateSeeder;

// Harness quirk: under DatabaseTruncation the in-test `$this->seed(...)` runs the
// seeder in a separate console-kernel instance whose connection snapshot predates
// this test's truncate pass, so on the 2nd+ test in a file its writes resolve to
// stale UPDATEs and the seeded rows are invisible to reads on the test connection
// (count returns 0). Running the seeder directly on the live test connection in
// beforeEach is deterministic; the idempotent in-body `$this->seed(...)` calls then
// no-op against rows that are already present.
beforeEach(function () {
    (new SpecialtyTemplateSeeder)->run();
});

it('seeds four templates idempotently with engine fields', function () {
    $this->seed(SpecialtyTemplateSeeder::class);
    $this->seed(SpecialtyTemplateSeeder::class); // run twice — must not duplicate

    expect(CaseTemplate::count())->toBe(4);

    $rare = CaseTemplate::where('slug', 'rare-disease-diagnostic-odyssey')->first();
    expect($rare->time_model)->toBe('diagnostic_odyssey');
    expect($rare->state_machine['initial'])->toBe('referral');
    expect(collect($rare->state_machine['states']))->toContain('reanalysis');

    $onc = CaseTemplate::where('slug', 'oncology-tumor-board')->first();
    expect($onc->time_model)->toBe('episodic');
    expect($onc->state_machine)->toBeNull();
    expect($onc->is_active)->toBeTrue();
    expect($onc->data_schema)->toBeArray();

    $surg = CaseTemplate::where('slug', 'complex-surgical-planning')->first();
    expect($surg->time_model)->toBe('episode_of_care');
    expect($surg->candidacy_rubric)->toBeArray();
});

it('creates a case bound to a template with the template initial state', function () {
    $this->seed(\Database\Seeders\SpecialtyTemplateSeeder::class);
    $user = \App\Models\User::factory()->create();
    $tpl = \App\Models\CaseTemplate::where('slug', 'rare-disease-diagnostic-odyssey')->first();

    $res = $this->actingAs($user)->postJson('/api/cases', [
        'title' => 'Undiagnosed myopathy',
        'specialty' => 'rare_disease',
        'case_type' => 'rare_disease',
        'template_id' => $tpl->id,
        'structured_data' => ['hpo_terms' => 'HP:0003198'],
    ]);

    $res->assertCreated();
    $case = \App\Models\ClinicalCase::first();
    expect($case->template_id)->toBe($tpl->id);
    expect($case->state)->toBe('referral');
    expect($case->structured_data)->toBe(['hpo_terms' => 'HP:0003198']);
});

it('surfaces soft validation warnings in meta without rejecting', function () {
    $this->seed(\Database\Seeders\SpecialtyTemplateSeeder::class);
    $user = \App\Models\User::factory()->create();
    $tpl = \App\Models\CaseTemplate::where('slug', 'complex-surgical-planning')->first();

    $res = $this->actingAs($user)->postJson('/api/cases', [
        'title' => 'Whipple candidacy',
        'specialty' => 'surgical',
        'case_type' => 'surgical_review',
        'template_id' => $tpl->id,
        'structured_data' => [],
    ]);

    $res->assertCreated();
    expect($res->json('meta.warnings.0'))->toContain('procedure');
});

it('lists only active templates when ?active=1', function () {
    $this->seed(\Database\Seeders\SpecialtyTemplateSeeder::class);
    \App\Models\CaseTemplate::where('slug', 'complex-medical-case-review')->update(['is_active' => false]);
    $user = \App\Models\User::factory()->create();

    $res = $this->actingAs($user)->getJson('/api/case-templates?active=1');
    $res->assertOk();
    $slugs = collect($res->json('data'))->pluck('slug');
    expect($slugs)->not->toContain('complex-medical-case-review');
    expect($slugs)->toContain('oncology-tumor-board');
});
