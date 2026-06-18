<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Models\User;
use Database\Seeders\SuperuserSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

function imagingTestPatient(array $overrides = []): ClinicalPatient
{
    return ClinicalPatient::create(array_merge([
        'mrn' => 'TCIA-TEST-001',
        'first_name' => 'Imaging',
        'last_name' => 'Patient',
        'source_type' => 'tcia',
        'source_id' => 'TEST',
    ], $overrides));
}

function orthancTestStudy(ClinicalPatient $patient, array $overrides = []): ImagingStudy
{
    return ImagingStudy::create(array_merge([
        'patient_id' => $patient->id,
        'study_uid' => '1.2.840.113619.2.55.3.604688433.123.1700000000.1',
        'modality' => 'CT',
        'study_date' => '2026-01-02',
        'description' => 'CT Chest With Contrast',
        'body_part' => 'Chest',
        'accession_number' => 'ACC-ORTHANC-1',
        'num_series' => 1,
        'num_instances' => 2,
        'dicom_endpoint' => 'orthanc',
        'source_type' => 'tcia',
        'source_id' => 'orthanc_sync_v1',
    ], $overrides));
}

it('returns indexed Orthanc metadata and OHIF WADO fields in the study list', function () {
    $patient = imagingTestPatient();
    $study = orthancTestStudy($patient);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/imaging/studies?per_page=10');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', $study->id)
        ->assertJsonPath('data.0.patient_id', $patient->id)
        ->assertJsonPath('data.0.person_id', $patient->id)
        ->assertJsonPath('data.0.study_uid', $study->study_uid)
        ->assertJsonPath('data.0.study_instance_uid', $study->study_uid)
        ->assertJsonPath('data.0.status', 'indexed')
        ->assertJsonPath('data.0.orthanc_study_id', $study->study_uid)
        ->assertJsonPath('data.0.wadors_uri', '/orthanc/dicom-web')
        ->assertJsonPath('data.0.body_part', 'Chest')
        ->assertJsonPath('data.0.body_part_examined', 'Chest')
        ->assertJsonPath('data.0.num_images', 2)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.page', 1);
});

it('returns normalized Orthanc series fields in study detail', function () {
    $patient = imagingTestPatient();
    $study = orthancTestStudy($patient);

    ImagingSeries::create([
        'imaging_study_id' => $study->id,
        'series_uid' => '1.2.840.113619.2.55.3.604688433.123.1700000000.2',
        'series_number' => 7,
        'modality' => 'CT',
        'description' => 'Axial Soft Tissue',
        'num_instances' => 42,
        'source_type' => 'orthanc',
        'source_id' => 'orthanc-series-1',
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/imaging/studies/{$study->id}");

    $response->assertOk()
        ->assertJsonPath('data.status', 'indexed')
        ->assertJsonPath('data.study_instance_uid', $study->study_uid)
        ->assertJsonPath('data.series.0.series_uid', '1.2.840.113619.2.55.3.604688433.123.1700000000.2')
        ->assertJsonPath('data.series.0.series_instance_uid', '1.2.840.113619.2.55.3.604688433.123.1700000000.2')
        ->assertJsonPath('data.series.0.description', 'Axial Soft Tissue')
        ->assertJsonPath('data.series.0.series_description', 'Axial Soft Tissue')
        ->assertJsonPath('data.series.0.num_instances', 42)
        ->assertJsonPath('data.series.0.num_images', 42);
});

it('indexes study series from Orthanc by StudyInstanceUID', function () {
    config([
        'services.orthanc.base_url' => 'http://orthanc.test',
        'services.orthanc.user' => 'parthenon',
        'services.orthanc.password' => 'secret',
    ]);

    $patient = imagingTestPatient();
    $study = orthancTestStudy($patient, [
        'num_series' => 0,
        'num_instances' => 0,
    ]);

    Http::fake([
        'http://orthanc.test/tools/find' => Http::response(['orthanc-study-1']),
        'http://orthanc.test/studies/orthanc-study-1' => Http::response([
            'Series' => ['orthanc-series-1', 'orthanc-series-2'],
        ]),
        'http://orthanc.test/series/orthanc-series-1' => Http::response([
            'MainDicomTags' => [
                'SeriesInstanceUID' => '1.2.3.4.5.1',
                'SeriesNumber' => '1',
                'Modality' => 'CT',
                'SeriesDescription' => 'Arterial Phase',
            ],
            'Instances' => ['i-1', 'i-2'],
        ]),
        'http://orthanc.test/series/orthanc-series-2' => Http::response([
            'MainDicomTags' => [
                'SeriesInstanceUID' => '1.2.3.4.5.2',
                'SeriesNumber' => '2',
                'Modality' => 'CT',
                'SeriesDescription' => 'Venous Phase',
            ],
            'Instances' => ['i-3'],
        ]),
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/imaging/studies/{$study->id}/index-series");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.indexed', 2)
        ->assertJsonPath('data.updated', 0)
        ->assertJsonPath('data.errors', 0)
        ->assertJsonPath('data.series_total', 2);

    $this->assertDatabaseHas('imaging_series', [
        'imaging_study_id' => $study->id,
        'series_uid' => '1.2.3.4.5.1',
        'series_number' => 1,
        'modality' => 'CT',
        'description' => 'Arterial Phase',
        'num_instances' => 2,
        'source_id' => 'orthanc-series-1',
        'source_type' => 'orthanc',
    ]);

    $this->assertDatabaseHas('imaging_series', [
        'imaging_study_id' => $study->id,
        'series_uid' => '1.2.3.4.5.2',
        'num_instances' => 1,
    ]);

    $study->refresh();
    expect($study->num_series)->toBe(2)
        ->and($study->num_instances)->toBe(3)
        ->and($study->dicom_endpoint)->toBe('orthanc');
});
