<?php

use App\Services\Genomics\Acmg\AcmgClassifier;
use App\Services\Genomics\Acmg\AcmgStrength;

beforeEach(fn () => $this->classifier = new AcmgClassifier);

function crit(string $code, AcmgStrength $s): array
{
    return ['code' => $code, 'strength' => $s];
}

it('sums signed points and classifies pathogenic at >=10', function () {
    $r = $this->classifier->classify([
        crit('PVS1', AcmgStrength::VeryStrong),
        crit('PM2', AcmgStrength::Supporting),
        crit('PP3', AcmgStrength::Supporting),
    ]);
    expect($r['points'])->toBe(10);
    expect($r['classification'])->toBe('pathogenic');
});

it('classifies likely pathogenic in 6..9', function () {
    $r = $this->classifier->classify([crit('PS1', AcmgStrength::Strong), crit('PM1', AcmgStrength::Moderate)]);
    expect($r['points'])->toBe(6);
    expect($r['classification'])->toBe('likely_pathogenic');
});

it('classifies VUS in 0..5', function () {
    $r = $this->classifier->classify([crit('PM1', AcmgStrength::Moderate)]);
    expect($r['classification'])->toBe('vus');
});

it('classifies likely benign in -1..-6', function () {
    $r = $this->classifier->classify([crit('BS1', AcmgStrength::Strong), crit('BP4', AcmgStrength::Supporting)]);
    expect($r['points'])->toBe(-5);
    expect($r['classification'])->toBe('likely_benign');
});

it('classifies benign at <=-7', function () {
    $r = $this->classifier->classify([crit('BS1', AcmgStrength::Strong), crit('BS2', AcmgStrength::Strong)]);
    expect($r['points'])->toBe(-8);
    expect($r['classification'])->toBe('benign');
});

it('treats BA1 as a stand-alone benign override regardless of points', function () {
    $r = $this->classifier->classify([
        crit('BA1', AcmgStrength::Strong),
        crit('PVS1', AcmgStrength::VeryStrong),
    ]);
    expect($r['classification'])->toBe('benign');
    expect($r['standalone_benign'])->toBeTrue();
});

it('honors strength modulation (PVS1 applied at Moderate = +2)', function () {
    $r = $this->classifier->classify([crit('PVS1', AcmgStrength::Moderate)]);
    expect($r['points'])->toBe(2);
});
