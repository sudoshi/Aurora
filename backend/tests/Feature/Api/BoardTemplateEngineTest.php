<?php

use App\Models\CaseTemplate;
use Database\Seeders\SpecialtyTemplateSeeder;

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
