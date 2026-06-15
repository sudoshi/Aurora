<?php

use App\Models\Clinical\ClinGenGeneValidity;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    Cache::flush();
    // clean leaked clinical rows (clinical schema not truncated between tests)
    KbChangeAlert::where('source', 'clingen_gdv')->delete();
    ClinGenGeneValidity::where('gene_symbol', 'TESTGENEX')->delete();
    GenomicVariant::where('gene', 'TESTGENEX')->delete();
    config(['services.clingen_gdv.csv_url' => 'https://clingen.test/gdv.csv']);
});

function fakeGdvCsv(string $classification): void
{
    $csv = '"GENE SYMBOL","GENE ID (HGNC)","DISEASE LABEL","DISEASE ID (MONDO)","MOI","SOP","CLASSIFICATION","ONLINE REPORT","CLASSIFICATION DATE","GCEP"'."\n"
        .'"TESTGENEX","HGNC:99999","Test disease","MONDO:0000001","AD","SOP10","'.$classification.'","https://x/","2024-08-29T17:00:00.000Z","Test GCEP"'."\n";
    Http::fake(['clingen.test/*' => Http::response($csv, 200)]);
}

it('raises a high-severity gdv alert when a gene is upgraded Limited -> Definitive', function () {
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX']);
    ClinGenGeneValidity::create(['gene_symbol' => 'TESTGENEX', 'disease_label' => 'Test disease', 'classification' => 'Limited', 'baseline_classification' => 'Limited']);
    fakeGdvCsv('Definitive');

    $this->artisan('genomics:reanalyze-gene-validity')->assertSuccessful();

    $alert = KbChangeAlert::where('source', 'clingen_gdv')->where('patient_id', $variant->patient_id)->first();
    expect($alert)->not->toBeNull();
    expect($alert->from_bucket)->toBe('Limited');
    expect($alert->to_bucket)->toBe('Definitive');
    expect($alert->severity)->toBe('high');
    expect(ClinGenGeneValidity::where('gene_symbol', 'TESTGENEX')->first()->baseline_classification)->toBe('Definitive');
});

it('is idempotent — a second run raises no new alert', function () {
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX']);
    ClinGenGeneValidity::create(['gene_symbol' => 'TESTGENEX', 'disease_label' => 'Test disease', 'classification' => 'Limited', 'baseline_classification' => 'Limited']);
    fakeGdvCsv('Definitive');
    $this->artisan('genomics:reanalyze-gene-validity')->assertSuccessful();
    $this->artisan('genomics:reanalyze-gene-validity')->assertSuccessful();
    expect(KbChangeAlert::where('source', 'clingen_gdv')->where('patient_id', $variant->patient_id)->count())->toBe(1);
});

it('raises nothing when the classification is unchanged', function () {
    GenomicVariant::factory()->create(['gene' => 'TESTGENEX']);
    ClinGenGeneValidity::create(['gene_symbol' => 'TESTGENEX', 'disease_label' => 'Test disease', 'classification' => 'Definitive', 'baseline_classification' => 'Definitive']);
    fakeGdvCsv('Definitive');
    $this->artisan('genomics:reanalyze-gene-validity')->assertSuccessful();
    expect(KbChangeAlert::where('source', 'clingen_gdv')->count())->toBe(0);
});
