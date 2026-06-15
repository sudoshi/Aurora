<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;
use App\Services\RareDisease\InvalidPhenopacketException;
use App\Services\RareDisease\PhenopacketImporter;
use Illuminate\Foundation\Testing\DatabaseTruncation;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->importer = new PhenopacketImporter;
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

function samplePacket(): array
{
    return [
        'phenotypicFeatures' => [
            ['type' => ['id' => 'HP:0001250', 'label' => 'Seizure'], 'excluded' => false, 'severity' => ['id' => 'HP:0012828', 'label' => '']],
            ['type' => ['id' => 'HP:0001251', 'label' => 'Ataxia'], 'excluded' => true],
        ],
    ];
}

it('imports observed and excluded phenotype features', function () {
    $result = $this->importer->importInto($this->odyssey, samplePacket(), $this->user->id);

    expect($result)->toBe(['imported' => 2, 'skipped' => 0]);
    expect($this->odyssey->phenotypeFeatures()->count())->toBe(2);

    $seizure = $this->odyssey->phenotypeFeatures()->where('hpo_id', 'HP:0001250')->first();
    expect($seizure->excluded)->toBeFalse();
    expect($seizure->severity_hpo_id)->toBe('HP:0012828');

    $ataxia = $this->odyssey->phenotypeFeatures()->where('hpo_id', 'HP:0001251')->first();
    expect($ataxia->excluded)->toBeTrue();
});

it('is idempotent — re-importing the same packet skips existing terms', function () {
    $this->importer->importInto($this->odyssey, samplePacket(), $this->user->id);
    $result = $this->importer->importInto($this->odyssey->fresh(), samplePacket(), $this->user->id);

    expect($result)->toBe(['imported' => 0, 'skipped' => 2]);
    expect($this->odyssey->phenotypeFeatures()->count())->toBe(2);
});

it('throws when phenotypicFeatures is not an array', function () {
    $this->importer->importInto($this->odyssey, ['phenotypicFeatures' => 'nope'], $this->user->id);
})->throws(InvalidPhenopacketException::class);

it('throws on a malformed HPO id', function () {
    $this->importer->importInto($this->odyssey, [
        'phenotypicFeatures' => [['type' => ['id' => 'seizure', 'label' => 'x']]],
    ], $this->user->id);
})->throws(InvalidPhenopacketException::class);
