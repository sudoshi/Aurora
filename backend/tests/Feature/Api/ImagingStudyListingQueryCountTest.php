<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Models\User;
use Database\Seeders\SuperuserSeeder;
use Illuminate\Support\Facades\DB;

/*
 * Regression coverage for the imaging study-listing N+1.
 *
 * formatStudy() used to issue measurement/segmentation count() queries per
 * study; listing N studies therefore ran O(N) extra queries. The listing now
 * eager-loads counts via withCount(), so the query count must stay bounded and
 * NOT scale with the number of studies returned.
 */

beforeEach(function () {
    app(SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

function queryCountPatient(): ClinicalPatient
{
    return ClinicalPatient::create([
        'mrn' => 'QC-'.uniqid(),
        'first_name' => 'QueryCount',
        'last_name' => 'Patient',
        'source_type' => 'tcia',
        'source_id' => 'QC',
    ]);
}

function queryCountStudy(ClinicalPatient $patient, int $n): ImagingStudy
{
    $study = ImagingStudy::create([
        'patient_id' => $patient->id,
        'study_uid' => '1.2.840.113619.2.55.3.604688433.123.1700000000.'.$n,
        'modality' => 'CT',
        'study_date' => '2026-01-'.str_pad((string) (($n % 27) + 1), 2, '0', STR_PAD_LEFT),
        'description' => "CT Study {$n}",
        'body_part' => 'Chest',
        'accession_number' => "ACC-QC-{$n}",
        'num_series' => 1,
        'num_instances' => 2,
        'dicom_endpoint' => 'orthanc',
        'source_type' => 'tcia',
        'source_id' => "qc_{$n}",
    ]);

    // Two measurements + one series per study, so per-study counts are non-zero
    // and an N+1 would be clearly visible in the query log.
    foreach ([30, 50] as $i => $value) {
        ImagingMeasurement::create([
            'imaging_study_id' => $study->id,
            'measurement_type' => 'RECIST',
            'target_lesion' => true,
            'value_numeric' => $value,
            'unit' => 'mm',
            'measured_by' => 'test',
            'measured_at' => $study->study_date,
        ]);
    }

    ImagingSeries::create([
        'imaging_study_id' => $study->id,
        'series_uid' => $study->study_uid.'.series.'.$n,
        'series_number' => 1,
        'modality' => 'CT',
        'description' => 'Axial',
        'num_instances' => 2,
        'source_id' => "qc_series_{$n}",
        'source_type' => 'tcia',
    ]);

    return $study;
}

it('returns correct count fields for a single study', function () {
    $patient = queryCountPatient();
    $study = queryCountStudy($patient, 1);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/imaging/studies?per_page=50');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', $study->id)
        ->assertJsonPath('data.0.measurement_count', 2)
        ->assertJsonPath('data.0.measurements_count', 2)
        ->assertJsonPath('data.0.segmentation_count', 0)
        ->assertJsonPath('meta.total', 1);
});

it('does not scale the query count with the number of studies', function () {
    $patient = queryCountPatient();

    // Baseline: 1 study.
    queryCountStudy($patient, 1);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $oneStudyResponse = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/imaging/studies?per_page=50');
    $oneStudyQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    $oneStudyResponse->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.measurement_count', 2);

    // Now 5 studies total (4 more), each with measurements + a series.
    foreach (range(2, 5) as $n) {
        queryCountStudy($patient, $n);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $fiveStudyResponse = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/imaging/studies?per_page=50');
    $fiveStudyQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    $fiveStudyResponse->assertOk()
        ->assertJsonPath('meta.total', 5);

    // Every study row must carry the eager-loaded counts.
    foreach ($fiveStudyResponse->json('data') as $row) {
        expect($row['measurement_count'])->toBe(2);
        expect($row['measurements_count'])->toBe(2);
        expect($row['segmentation_count'])->toBe(0);
    }

    // The whole request stays comfortably under a fixed ceiling regardless of
    // study count. With withCount(), the listing is a constant handful of
    // queries (auth + pagination count + page select + withCount subqueries)
    // plus one audit-row insert from the audit.phi middleware. The N+1 version
    // would have added ~3 queries per study.
    expect($fiveStudyQueries)->toBeLessThan(15);

    // The query count must NOT grow ~per-study. Going 1 -> 5 studies adds 4
    // studies; an N+1 would add ~12 queries. Allow a small constant slack only.
    expect($fiveStudyQueries - $oneStudyQueries)->toBeLessThanOrEqual(2);
});
