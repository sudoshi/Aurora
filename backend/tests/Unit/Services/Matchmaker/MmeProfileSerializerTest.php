<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Services\Matchmaker\MmeProfileSerializer;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('serializes an odyssey into an MME patient profile', function () {
    $odyssey = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $odyssey->id, 'hpo_id' => 'HP:0001250', 'hpo_label' => 'Seizure', 'excluded' => false]);
    PhenotypeFeature::factory()->create(['odyssey_id' => $odyssey->id, 'hpo_id' => 'HP:0001263', 'excluded' => true]);
    GenomicVariant::factory()->create(['patient_id' => $odyssey->patient_id, 'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A']);

    $profile = app(MmeProfileSerializer::class)->serialize($odyssey);

    expect($profile['patient']['id'])->toBe('aurora-odyssey-'.$odyssey->id);
    expect($profile['patient']['contact']['name'])->not->toBe('');
    expect($profile['patient']['features'])->toHaveCount(2);
    expect(collect($profile['patient']['features'])->firstWhere('id', 'HP:0001250')['observed'])->toBe('yes');
    expect(collect($profile['patient']['features'])->firstWhere('id', 'HP:0001263')['observed'])->toBe('no');
    expect($profile['patient']['genomicFeatures'][0]['gene']['id'])->toBe('TESTGENEX');
    expect($profile['patient']['genomicFeatures'][0]['variant']['referenceName'])->toBe('17');
    expect($profile['patient']['genomicFeatures'][0]['variant']['start'])->toBe(43045711); // 0-based
});
