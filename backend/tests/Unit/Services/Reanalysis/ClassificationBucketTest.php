<?php

use App\Services\Genomics\Reanalysis\ClassificationBucket;

it('normalizes ClinVar significance strings to ordered buckets', function () {
    expect(ClassificationBucket::normalize('Pathogenic'))->toBe('pathogenic');
    expect(ClassificationBucket::normalize('Likely pathogenic'))->toBe('likely_pathogenic');
    expect(ClassificationBucket::normalize('Pathogenic/Likely pathogenic'))->toBe('pathogenic');
    expect(ClassificationBucket::normalize('Uncertain significance'))->toBe('vus');
    expect(ClassificationBucket::normalize('Conflicting interpretations of pathogenicity'))->toBe('conflicting');
    expect(ClassificationBucket::normalize('Conflicting classifications of pathogenicity'))->toBe('conflicting');
    expect(ClassificationBucket::normalize('Likely benign'))->toBe('likely_benign');
    expect(ClassificationBucket::normalize('Benign/Likely benign'))->toBe('likely_benign');
    expect(ClassificationBucket::normalize('Benign'))->toBe('benign');
    expect(ClassificationBucket::normalize('not provided'))->toBe('unknown');
    expect(ClassificationBucket::normalize(null))->toBe('unknown');
});

it('ranks buckets and flags the actionable ones', function () {
    expect(ClassificationBucket::rank('pathogenic'))->toBeGreaterThan(ClassificationBucket::rank('vus'));
    expect(ClassificationBucket::rank('vus'))->toBeGreaterThan(ClassificationBucket::rank('benign'));
    expect(ClassificationBucket::isActionable('pathogenic'))->toBeTrue();
    expect(ClassificationBucket::isActionable('likely_pathogenic'))->toBeTrue();
    expect(ClassificationBucket::isActionable('vus'))->toBeFalse();
    expect(ClassificationBucket::isActionable('benign'))->toBeFalse();
});

it('maps review_status to star tiers', function () {
    expect(ClassificationBucket::stars('practice guideline'))->toBe(4);
    expect(ClassificationBucket::stars('reviewed by expert panel'))->toBe(3);
    expect(ClassificationBucket::stars('criteria provided, multiple submitters, no conflicts'))->toBe(2);
    expect(ClassificationBucket::stars('criteria provided, single submitter'))->toBe(1);
    expect(ClassificationBucket::stars('criteria provided, conflicting interpretations'))->toBe(1);
    expect(ClassificationBucket::stars('no assertion criteria provided'))->toBe(0);
    expect(ClassificationBucket::stars(null))->toBe(0);
});
