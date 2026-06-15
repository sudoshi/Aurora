<?php

use App\Services\Genomics\Reanalysis\GeneValidityClassification;

describe('GeneValidityClassification::rank', function () {
    it('returns 6 for Definitive', fn () => expect(GeneValidityClassification::rank('Definitive'))->toBe(6));
    it('returns 5 for Strong', fn () => expect(GeneValidityClassification::rank('Strong'))->toBe(5));
    it('returns 4 for Moderate', fn () => expect(GeneValidityClassification::rank('Moderate'))->toBe(4));
    it('returns 3 for Limited', fn () => expect(GeneValidityClassification::rank('Limited'))->toBe(3));
    it('returns 2 for Disputed', fn () => expect(GeneValidityClassification::rank('Disputed'))->toBe(2));
    it('returns 1 for Refuted', fn () => expect(GeneValidityClassification::rank('Refuted'))->toBe(1));
    it('returns 0 for No Known Disease Relationship', fn () => expect(GeneValidityClassification::rank('No Known Disease Relationship'))->toBe(0));
    it('returns 0 for Animal Model Only', fn () => expect(GeneValidityClassification::rank('Animal Model Only'))->toBe(0));
    it('returns 0 for null', fn () => expect(GeneValidityClassification::rank(null))->toBe(0));
    it('returns 0 for empty string', fn () => expect(GeneValidityClassification::rank(''))->toBe(0));
    it('is case-insensitive', fn () => expect(GeneValidityClassification::rank('DEFINITIVE'))->toBe(6));
    it('trims whitespace', fn () => expect(GeneValidityClassification::rank('  Strong  '))->toBe(5));
});

describe('GeneValidityClassification::severity', function () {
    it('returns null when $to is null', function () {
        expect(GeneValidityClassification::severity('Limited', null))->toBeNull();
    });

    it('returns null when $to is empty string', function () {
        expect(GeneValidityClassification::severity('Limited', ''))->toBeNull();
    });

    it('returns null when $from equals $to (unchanged)', function () {
        expect(GeneValidityClassification::severity('Definitive', 'Definitive'))->toBeNull();
    });

    it('returns null on case-insensitive match (unchanged)', function () {
        expect(GeneValidityClassification::severity('definitive', 'Definitive'))->toBeNull();
    });

    it('returns null when $from is null (first observation)', function () {
        expect(GeneValidityClassification::severity(null, 'Definitive'))->toBeNull();
    });

    it('returns null when $from is empty string (first observation)', function () {
        expect(GeneValidityClassification::severity('', 'Limited'))->toBeNull();
    });

    it('returns high when moving into Refuted from a non-disputed classification', function () {
        expect(GeneValidityClassification::severity('Definitive', 'Refuted'))->toBe('high');
    });

    it('returns high when moving into Disputed from a strong classification', function () {
        expect(GeneValidityClassification::severity('Strong', 'Disputed'))->toBe('high');
    });

    it('returns high when moving into Disputed from Moderate', function () {
        expect(GeneValidityClassification::severity('Moderate', 'Disputed'))->toBe('high');
    });

    it('returns high when upgrading from Limited to Definitive', function () {
        expect(GeneValidityClassification::severity('Limited', 'Definitive'))->toBe('high');
    });

    it('returns high when upgrading from No Known Disease Relationship to Definitive', function () {
        expect(GeneValidityClassification::severity('No Known Disease Relationship', 'Definitive'))->toBe('high');
    });

    it('returns medium when upgrading from Moderate to Strong (Moderate rank=4 is not weak ≤3)', function () {
        // Moderate has rank 4, so from-rank > 3 — does NOT meet the weak→strong 'high' threshold.
        expect(GeneValidityClassification::severity('Moderate', 'Strong'))->toBe('medium');
    });

    it('returns medium for Definitive to Strong (both strong ranks, not into Disputed/Refuted)', function () {
        expect(GeneValidityClassification::severity('Definitive', 'Strong'))->toBe('medium');
    });

    it('returns medium for Moderate to Limited (downgrade within mid-range)', function () {
        expect(GeneValidityClassification::severity('Moderate', 'Limited'))->toBe('medium');
    });

    it('returns medium for Limited to Moderate (upgrade within weak range)', function () {
        expect(GeneValidityClassification::severity('Limited', 'Moderate'))->toBe('medium');
    });

    it('returns medium when moving from Disputed to Refuted (both in {1,2})', function () {
        // Both are already in the disputed/refuted bucket — still a real change, but not "newly disputed"
        expect(GeneValidityClassification::severity('Disputed', 'Refuted'))->toBe('medium');
    });
});
