<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\GenomicVariant;
use App\Models\ClinicalCase;
use App\Models\User;

describe('Model Factories', function () {
    it('creates a valid User', function () {
        $user = User::factory()->create();
        expect($user)->toBeInstanceOf(User::class);
        expect($user->id)->toBeGreaterThan(0);
        expect($user->is_active)->toBeTrue();
    });

    it('creates a valid ClinicalPatient', function () {
        $patient = ClinicalPatient::factory()->create();
        expect($patient)->toBeInstanceOf(ClinicalPatient::class);
        expect($patient->id)->toBeGreaterThan(0);
        expect($patient->mrn)->toBeString();
        expect($patient->first_name)->toBeString();
    });

    it('creates a valid ClinicalCase with relationships', function () {
        $case = ClinicalCase::factory()->create();
        expect($case)->toBeInstanceOf(ClinicalCase::class);
        expect($case->id)->toBeGreaterThan(0);
        expect($case->specialty)->toBeString();
        expect($case->case_type)->toBeString();
    });

    it('creates a valid GeneDrugInteraction', function () {
        $interaction = GeneDrugInteraction::factory()->create();
        expect($interaction)->toBeInstanceOf(GeneDrugInteraction::class);
        expect($interaction->gene)->toBeString();
        expect($interaction->drug)->toBeString();
        expect($interaction->evidence_level)->toBeString();
    });

    it('creates a valid GenomicVariant with patient', function () {
        $variant = GenomicVariant::factory()->create();
        expect($variant)->toBeInstanceOf(GenomicVariant::class);
        expect($variant->gene)->toBeString();
        expect($variant->patient)->toBeInstanceOf(ClinicalPatient::class);
    });
});

it('creates a DiagnosticOdyssey via factory', function () {
    $odyssey = \App\Models\DiagnosticOdyssey::factory()->create();
    expect($odyssey->id)->toBeInt();
    expect($odyssey->status)->toBe('referral');
});

it('creates a PhenotypeFeature via factory', function () {
    $feature = \App\Models\PhenotypeFeature::factory()->create();
    expect($feature->id)->toBeInt();
    expect($feature->hpo_id)->toStartWith('HP:');
});

it('creates a VariantClassification via factory', function () {
    $c = \App\Models\Clinical\VariantClassification::factory()->create();
    expect($c->id)->toBeInt();
    expect($c->computed_classification)->toBeString();
});

it('creates a ClassificationCriterion via factory', function () {
    $crit = \App\Models\Clinical\ClassificationCriterion::factory()->create();
    expect($crit->id)->toBeInt();
    expect($crit->code)->toBeString();
});

it('creates a VariantCanonicalId via factory', function () {
    $v = \App\Models\Clinical\VariantCanonicalId::factory()->create();
    expect($v->id)->toBeInt();
});

it('creates a KbChangeAlert via factory', function () {
    $a = \App\Models\Clinical\KbChangeAlert::factory()->create();
    expect($a->id)->toBeInt();
    expect($a->severity)->toBeString();
});

it('creates a ClinGenGeneValidity via factory', function () {
    $v = \App\Models\Clinical\ClinGenGeneValidity::factory()->create();
    expect($v->exists)->toBeTrue();
    expect($v->id)->toBeInt();
    expect($v->gene_symbol)->toBeString();
    expect($v->classification)->toBe('Definitive');
});

it('creates an MmePeer via factory', fn () => expect(\App\Models\MmePeer::factory()->create()->exists)->toBeTrue());

it('creates an MmeMatch via factory', fn () => expect(\App\Models\MmeMatch::factory()->create()->exists)->toBeTrue());
