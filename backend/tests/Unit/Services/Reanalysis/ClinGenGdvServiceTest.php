<?php

use App\Services\Genomics\Reanalysis\ClinGenGdvService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Fixture CSV: 5 preamble/separator lines + column header + separator + 2 data rows
// Mirrors the real ClinGen GDV download format exactly.
$gdvFixtureCsv = implode("\n", [
    '"CLINGEN GENE DISEASE VALIDITY CURATIONS",""',
    '"FILE CREATED: 2026-06-15",""',
    '"WEBPAGE: https://search.clinicalgenome.org/kb/gene-validity",""',
    '"+++++++++++++","+++++++++++++++"',
    '"GENE SYMBOL","GENE ID (HGNC)","DISEASE LABEL","DISEASE ID (MONDO)","MOI","SOP","CLASSIFICATION","ONLINE REPORT","CLASSIFICATION DATE","GCEP"',
    '"+++++++++++++","+++++++++++++++"',
    '"BRCA1","HGNC:1100","BRCA1-related cancer predisposition","MONDO:0700268","AD","SOP10","Definitive","https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_123","2024-08-29T17:00:00.000Z","Hereditary Cancer Gene Curation Expert Panel"',
    '"ABCB6","HGNC:42","ABCB6-related condition","MONDO:0012345","AR","SOP10","Limited","https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_456","2023-03-10T12:00:00.000Z","Some Expert Panel"',
]);

beforeEach(function () use ($gdvFixtureCsv) {
    Cache::flush();
    config(['services.clingen_gdv.csv_url' => 'https://clingen.test/gdv.csv']);
    $this->service    = new ClinGenGdvService;
    $this->fixtureCsv = $gdvFixtureCsv;
});

it('parses fixture CSV and returns both data rows via fetchAll', function () {
    Http::fake(['https://clingen.test/gdv.csv' => Http::response($this->fixtureCsv, 200)]);

    $rows = $this->service->fetchAll();

    expect($rows)->toHaveCount(2);
    expect($rows[0]['gene_symbol'])->toBe('BRCA1');
    expect($rows[0]['classification'])->toBe('Definitive');
    expect($rows[0]['disease_id'])->toBe('MONDO:0700268');
    expect($rows[1]['gene_symbol'])->toBe('ABCB6');
    expect($rows[1]['classification'])->toBe('Limited');
});

it('byGene returns matching rows case-insensitively', function () {
    Http::fake(['https://clingen.test/gdv.csv' => Http::response($this->fixtureCsv, 200)]);

    $result = $this->service->byGene('BRCA1');

    expect($result)->toHaveCount(1);
    expect($result[0]['classification'])->toBe('Definitive');
    expect($result[0]['gene_hgnc'])->toBe('HGNC:1100');
});

it('byGene is case-insensitive for gene symbol lookup', function () {
    Http::fake(['https://clingen.test/gdv.csv' => Http::response($this->fixtureCsv, 200)]);

    $result = $this->service->byGene('brca1');

    expect($result)->toHaveCount(1);
    expect($result[0]['gene_symbol'])->toBe('BRCA1');
});

it('returns empty array when upstream returns 500', function () {
    Http::fake(['https://clingen.test/gdv.csv' => Http::response('Internal Server Error', 500)]);

    $result = $this->service->fetchAll();

    expect($result)->toBe([]);
});

it('caches the result so the HTTP call is made only once', function () {
    Http::fake(['https://clingen.test/gdv.csv' => Http::response($this->fixtureCsv, 200)]);

    $this->service->fetchAll();
    $this->service->fetchAll(); // second call should hit cache

    Http::assertSentCount(1);
});

it('skips preamble and header lines (no HGNC ids in those rows)', function () {
    Http::fake(['https://clingen.test/gdv.csv' => Http::response($this->fixtureCsv, 200)]);

    $rows = $this->service->fetchAll();

    // Only the 2 real data rows should come through; preamble lines have no HGNC:\d+
    foreach ($rows as $row) {
        expect($row['gene_hgnc'])->toMatch('/^HGNC:\d+/');
    }
});
