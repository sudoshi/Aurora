<?php

use App\Services\Genomics\Acmg\AcmgAutoEvidence;
use App\Services\Genomics\Acmg\GeneSpecificationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->auto = new AcmgAutoEvidence(new GeneSpecificationResolver));

it('proposes BA1 for common variants', function () {
    $c = $this->auto->fromFrequency('MYH7', 0.08);
    expect(collect($c)->pluck('code'))->toContain('BA1');
});

it('proposes BS1 between the BS1 and BA1 thresholds', function () {
    $c = $this->auto->fromFrequency('MYH7', 0.02);
    $codes = collect($c)->pluck('code');
    expect($codes)->toContain('BS1');
    expect($codes)->not->toContain('BA1');
});

it('proposes PM2_Supporting for absent/rare variants', function () {
    $c = $this->auto->fromFrequency('MYH7', 0.0);
    $pm2 = collect($c)->firstWhere('code', 'PM2');
    expect($pm2)->not->toBeNull();
    expect($pm2['strength'])->toBe('supporting');
    expect($pm2['data_source'])->toBe('auto:gnomad');
});

it('maps REVEL to calibrated PP3 strengths (Pejaver 2022)', function () {
    expect(collect($this->auto->fromInSilico(0.95))->firstWhere('code', 'PP3')['strength'])->toBe('strong');
    expect(collect($this->auto->fromInSilico(0.80))->firstWhere('code', 'PP3')['strength'])->toBe('moderate');
    expect(collect($this->auto->fromInSilico(0.70))->firstWhere('code', 'PP3')['strength'])->toBe('supporting');
});

it('maps REVEL to calibrated BP4 strengths', function () {
    expect(collect($this->auto->fromInSilico(0.01))->firstWhere('code', 'BP4')['strength'])->toBe('strong');
    expect(collect($this->auto->fromInSilico(0.10))->firstWhere('code', 'BP4')['strength'])->toBe('moderate');
    expect(collect($this->auto->fromInSilico(0.25))->firstWhere('code', 'BP4')['strength'])->toBe('supporting');
});

it('proposes nothing in-silico for the intermediate gray zone', function () {
    expect($this->auto->fromInSilico(0.45))->toBe([]);
});
