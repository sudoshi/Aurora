<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\User;
use App\Services\RareDisease\PhenopacketExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->exporter = new PhenopacketExporter;
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

it('exports a v2-shaped phenopacket with subject and schema version', function () {
    $packet = $this->exporter->export($this->odyssey);

    expect($packet['id'])->toBe('aurora-odyssey-'.$this->odyssey->id);
    // Pseudonymous subject id (D2) — never the internal patient_id.
    expect($packet['subject']['id'])->toBe('aurora-subject-'.$this->odyssey->id);
    expect($packet['subject']['id'])->not->toBe((string) $this->patient->id);
    expect($packet['metaData']['phenopacketSchemaVersion'])->toBe('2.0');
    expect($packet['metaData']['resources'][0]['namespacePrefix'])->toBe('HP');
});

it('maps observed and excluded phenotype features', function () {
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'hpo_id' => 'HP:0001250',
        'hpo_label' => 'Seizure',
        'excluded' => false,
        'severity_hpo_id' => 'HP:0012828',
        'recorded_by' => $this->user->id,
    ]);
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'hpo_id' => 'HP:0001251',
        'hpo_label' => 'Ataxia',
        'excluded' => true,
        'recorded_by' => $this->user->id,
    ]);

    $packet = $this->exporter->export($this->odyssey->fresh());
    $features = collect($packet['phenotypicFeatures']);

    expect($features)->toHaveCount(2);
    $seizure = $features->firstWhere('type.id', 'HP:0001250');
    expect($seizure['excluded'])->toBeFalse();
    expect($seizure['severity']['id'])->toBe('HP:0012828');
    $ataxia = $features->firstWhere('type.id', 'HP:0001251');
    expect($ataxia['excluded'])->toBeTrue();
});

it('emits frequency as a bare OntologyClass per Phenopackets v2', function () {
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'hpo_id' => 'HP:0001250',
        'hpo_label' => 'Seizure',
        'frequency_hpo_id' => 'HP:0040283', // Occasional
        'recorded_by' => $this->user->id,
    ]);

    $packet = $this->exporter->export($this->odyssey->fresh());
    $feature = collect($packet['phenotypicFeatures'])->firstWhere('type.id', 'HP:0001250');

    // v2: frequency is a bare OntologyClass {id,label}, NOT wrapped in an ontologyClass envelope.
    expect($feature['frequency']['id'])->toBe('HP:0040283');
    expect($feature['frequency'])->not->toHaveKey('ontologyClass');
});
