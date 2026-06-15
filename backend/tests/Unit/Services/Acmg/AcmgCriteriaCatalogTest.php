<?php

use App\Services\Genomics\Acmg\AcmgCriteriaCatalog;
use App\Services\Genomics\Acmg\AcmgStrength;

it('maps strengths to Tavtigian points', function () {
    expect(AcmgStrength::VeryStrong->points())->toBe(8);
    expect(AcmgStrength::Strong->points())->toBe(4);
    expect(AcmgStrength::Moderate->points())->toBe(2);
    expect(AcmgStrength::Supporting->points())->toBe(1);
});

it('catalogs the active ACMG codes and excludes deprecated PP5/BP6', function () {
    expect(AcmgCriteriaCatalog::exists('PVS1'))->toBeTrue();
    expect(AcmgCriteriaCatalog::exists('BP7'))->toBeTrue();
    expect(AcmgCriteriaCatalog::exists('PP5'))->toBeFalse();
    expect(AcmgCriteriaCatalog::exists('BP6'))->toBeFalse();
    expect(AcmgCriteriaCatalog::all())->toHaveCount(26);
});

it('classifies code category and standalone flag', function () {
    expect(AcmgCriteriaCatalog::category('PVS1'))->toBe('pathogenic');
    expect(AcmgCriteriaCatalog::category('BA1'))->toBe('benign');
    expect(AcmgCriteriaCatalog::isStandalone('BA1'))->toBeTrue();
    expect(AcmgCriteriaCatalog::isStandalone('BS1'))->toBeFalse();
});

it('defaults PM2 to supporting per SVI 2020', function () {
    expect(AcmgCriteriaCatalog::defaultStrength('PM2'))->toBe(AcmgStrength::Supporting);
    expect(AcmgCriteriaCatalog::defaultStrength('PVS1'))->toBe(AcmgStrength::VeryStrong);
});
