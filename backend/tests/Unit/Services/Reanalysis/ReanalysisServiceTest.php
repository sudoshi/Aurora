<?php

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\Clinical\VariantCanonicalId;
use App\Models\PatientTask;
use App\Models\User;
use App\Services\Genomics\Reanalysis\ReanalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->service = app(ReanalysisService::class));

function seedReclassified(string $from, string $to, string $reviewStatus = 'criteria provided, multiple submitters, no conflicts'): GenomicVariant
{
    User::factory()->create(); // system actor for created_by
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A',
    ]);
    VariantCanonicalId::factory()->create([
        'genomic_variant_id' => $variant->id, 'clinvar_variation_id' => '55555',
        'baseline_significance' => $from, 'baseline_review_status' => 'criteria provided, single submitter',
        'baselined_at' => now()->subMonths(6),
    ]);
    ClinVarVariant::create([
        'variation_id' => '55555', 'chromosome' => '17', 'position' => 43045712,
        'reference_allele' => 'G', 'alternate_allele' => 'A', 'genome_build' => 'GRCh38',
        'gene_symbol' => 'TESTGENEX', 'clinical_significance' => $to, 'review_status' => $reviewStatus, 'is_pathogenic' => true,
    ]);

    return $variant;
}

it('raises a high-severity alert + a patient task on a VUS->Pathogenic reclassification', function () {
    $variant = seedReclassified('Uncertain significance', 'Pathogenic');

    $count = $this->service->run();

    expect($count)->toBe(1);
    $alert = KbChangeAlert::first();
    expect($alert->severity)->toBe('high');
    expect($alert->from_bucket)->toBe('vus');
    expect($alert->to_bucket)->toBe('pathogenic');
    expect($alert->task_id)->not->toBeNull();
    expect(PatientTask::find($alert->task_id)->patient_id)->toBe($variant->patient_id);
    expect(VariantCanonicalId::first()->baseline_significance)->toBe('Pathogenic');
});

it('does not alert on non-bucket-crossing churn', function () {
    seedReclassified('Pathogenic', 'Likely pathogenic');
    expect($this->service->run())->toBe(0);
    expect(KbChangeAlert::count())->toBe(0);
});

it('is idempotent — a second run with no new change creates no duplicate', function () {
    seedReclassified('Uncertain significance', 'Pathogenic');
    $this->service->run();
    $this->service->run();
    expect(KbChangeAlert::count())->toBe(1);
});
