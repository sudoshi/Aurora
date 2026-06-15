<?php

use App\Services\Genomics\Reanalysis\ReanalysisTransition;

it('flags an upgrade to actionable as high severity', function () {
    expect(ReanalysisTransition::severity('vus', 'pathogenic', 1, 2))->toBe('high');
    expect(ReanalysisTransition::severity('likely_benign', 'likely_pathogenic', 1, 2))->toBe('high');
    expect(ReanalysisTransition::severity('conflicting', 'pathogenic', 1, 2))->toBe('high');
});

it('flags a downgrade of a reported actionable variant as high severity', function () {
    expect(ReanalysisTransition::severity('pathogenic', 'vus', 2, 2))->toBe('high');
    expect(ReanalysisTransition::severity('likely_pathogenic', 'benign', 2, 2))->toBe('high');
});

it('flags vus->benign as medium (de-prioritize)', function () {
    expect(ReanalysisTransition::severity('vus', 'likely_benign', 1, 1))->toBe('medium');
    expect(ReanalysisTransition::severity('vus', 'benign', 1, 1))->toBe('medium');
});

it('flags a same-bucket star increase to >=3 as medium', function () {
    expect(ReanalysisTransition::severity('pathogenic', 'pathogenic', 1, 3))->toBe('medium');
    expect(ReanalysisTransition::severity('vus', 'vus', 0, 3))->toBe('medium');
});

it('suppresses non-bucket-crossing churn and star decreases', function () {
    expect(ReanalysisTransition::severity('pathogenic', 'likely_pathogenic', 2, 2))->toBeNull();
    expect(ReanalysisTransition::severity('benign', 'likely_benign', 2, 2))->toBeNull();
    expect(ReanalysisTransition::severity('pathogenic', 'pathogenic', 3, 1))->toBeNull();
    expect(ReanalysisTransition::severity('vus', 'vus', 1, 2))->toBeNull();
    expect(ReanalysisTransition::severity('vus', 'vus', 2, 2))->toBeNull();
});
