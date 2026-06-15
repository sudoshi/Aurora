<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\User;
use App\Services\Genomics\Acmg\AcmgAutoEvidence;
use App\Services\Genomics\Acmg\AcmgClassifier;
use App\Services\Genomics\Acmg\ClassificationService;
use App\Services\Genomics\Acmg\GeneSpecificationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $resolver = new GeneSpecificationResolver;
    $this->service = new ClassificationService(new AcmgClassifier, new AcmgAutoEvidence($resolver), $resolver);
    $this->user = User::factory()->create();
    $this->variant = GenomicVariant::factory()->create(['gene_symbol' => 'BRCA1']);
});

it('creates a classification and auto-populates criteria from supplied evidence', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0, 'revel' => 0.95]);

    expect($c->criteria()->pluck('code')->all())->toEqualCanonicalizing(['PM2', 'PP3']);
    expect($c->computed_points)->toBe(5);
    expect($c->computed_classification)->toBe('vus');
    expect($c->status)->toBe('computed');
    expect($c->criteria()->where('code', 'PP3')->first()->set_by)->toBe('auto');
});

it('recomputes after a curator adds a criterion', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0]);
    $this->service->addCriterion($c, 'PVS1', 'very_strong', $this->user->id, rationale: 'Canonical splice');
    $c = $this->service->recompute($c->fresh('criteria'));

    expect($c->computed_points)->toBe(9);
    expect($c->computed_classification)->toBe('likely_pathogenic');
});

it('confirms with a human sign-off and records an override reason when final differs', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0]);
    $c = $this->service->confirm($c, 'likely_pathogenic', $this->user->id, 'Strong segregation in 3 families (PP1_Strong)');

    expect($c->status)->toBe('confirmed');
    expect($c->final_classification)->toBe('likely_pathogenic');
    expect($c->override_reason)->toBe('Strong segregation in 3 families (PP1_Strong)');
    expect($c->confirmed_by)->toBe($this->user->id);
    expect($c->confirmed_at)->not->toBeNull();
});

it('rejects an unknown ACMG code', function () {
    $c = $this->service->create($this->variant, $this->user->id, []);
    $this->service->addCriterion($c, 'PXX9', 'strong', $this->user->id);
})->throws(InvalidArgumentException::class);

it('requires an override reason when final differs from computed', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0]); // computed VUS
    $this->service->confirm($c, 'pathogenic', $this->user->id);
})->throws(InvalidArgumentException::class);
