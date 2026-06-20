<?php

use App\Jobs\Imaging\ImportLocalDicomJob;
use App\Jobs\Imaging\IndexDicomwebStudiesJob;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingCriteria;
use App\Models\Clinical\ImagingIngestionRun;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingResponseAssessment;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\PatientIdentifier;
use App\Models\User;
use App\Services\Imaging\ImagingIngestionService;
use Database\Seeders\SuperuserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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

function imagingTestMeasurement(ImagingStudy $study, array $overrides = []): ImagingMeasurement
{
    return ImagingMeasurement::create(array_merge([
        'imaging_study_id' => $study->id,
        'measurement_type' => 'RECIST',
        'target_lesion' => true,
        'value_numeric' => 40,
        'unit' => 'mm',
        'measured_by' => 'test',
        'measured_at' => $study->study_date ?? now(),
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

describe('Imaging measurements and timeline contracts', function () {
    it('persists and returns frontend measurement fields without dropping user input', function () {
        $patient = imagingTestPatient([
            'mrn' => 'TCIA-MEASURE-001',
            'sex' => 'Female',
        ]);
        $study = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.measurements.rich',
            'study_date' => '2026-02-01',
            'body_part' => 'Liver',
        ]);
        $series = ImagingSeries::create([
            'imaging_study_id' => $study->id,
            'series_uid' => '1.2.3.measurements.rich.series',
            'series_number' => 4,
            'modality' => 'CT',
            'description' => 'Venous phase',
            'num_instances' => 88,
            'source_type' => 'orthanc',
            'source_id' => 'orthanc-series-rich',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/imaging/studies/{$study->id}/measurements", [
                'measurement_type' => 'longest_diameter',
                'measurement_name' => 'Target lesion 1 longest diameter',
                'value_as_number' => 27.4,
                'unit' => 'mm',
                'body_site' => 'Liver segment 7',
                'laterality' => 'RIGHT',
                'series_id' => $series->id,
                'algorithm_name' => 'manual',
                'confidence' => 0.92,
                'measured_at' => '2026-02-02T12:00:00Z',
                'is_target_lesion' => true,
                'target_lesion_number' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.study_id', $study->id)
            ->assertJsonPath('data.person_id', $patient->id)
            ->assertJsonPath('data.series_id', $series->id)
            ->assertJsonPath('data.measurement_type', 'longest_diameter')
            ->assertJsonPath('data.measurement_name', 'Target lesion 1 longest diameter')
            ->assertJsonPath('data.value_as_number', 27.4)
            ->assertJsonPath('data.body_site', 'Liver segment 7')
            ->assertJsonPath('data.laterality', 'RIGHT')
            ->assertJsonPath('data.algorithm_name', 'manual')
            ->assertJsonPath('data.confidence', 0.92)
            ->assertJsonPath('data.is_target_lesion', true)
            ->assertJsonPath('data.target_lesion_number', 1)
            ->assertJsonPath('data.study.body_part_examined', 'Liver');

        $this->assertDatabaseHas('clinical.imaging_measurements', [
            'imaging_study_id' => $study->id,
            'imaging_series_id' => $series->id,
            'measurement_name' => 'Target lesion 1 longest diameter',
            'body_site' => 'Liver segment 7',
            'laterality' => 'RIGHT',
            'algorithm_name' => 'manual',
            'target_lesion_number' => 1,
        ]);

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/imaging/studies/{$study->id}/measurements");

        $list->assertOk()
            ->assertJsonPath('data.0.measurement_name', 'Target lesion 1 longest diameter')
            ->assertJsonPath('data.0.value_as_number', 27.4)
            ->assertJsonPath('data.0.is_target_lesion', true);
    });

    it('returns the patient imaging timeline in the frontend contract shape', function () {
        $patient = imagingTestPatient([
            'mrn' => 'TCIA-TIMELINE-001',
            'date_of_birth' => '1980-04-05',
            'sex' => 'Female',
            'race' => 'Asian',
        ]);
        $baseline = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.timeline.baseline',
            'study_date' => '2026-01-01',
            'modality' => 'CT',
            'body_part' => 'Chest',
            'num_series' => 2,
            'num_instances' => 120,
        ]);
        orthancTestStudy($patient, [
            'study_uid' => '1.2.3.timeline.followup',
            'study_date' => '2026-02-15',
            'modality' => 'MR',
            'body_part' => 'Brain',
            'num_series' => 1,
            'num_instances' => 45,
        ]);
        imagingTestMeasurement($baseline, [
            'measurement_type' => 'longest_diameter',
            'measurement_name' => 'Chest target lesion',
            'body_site' => 'Chest',
            'laterality' => 'LEFT',
            'target_lesion_number' => 1,
            'value_numeric' => 31.2,
            'measured_at' => '2026-01-01',
        ]);

        DB::table('clinical.drug_eras')->insert([
            'patient_id' => $patient->id,
            'drug_name' => 'Osimertinib',
            'era_start' => '2026-01-10',
            'era_end' => '2026-02-10',
            'gap_days' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/imaging/patients/{$patient->id}/timeline");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.person_id', $patient->id)
            ->assertJsonPath('data.person.person_id', $patient->id)
            ->assertJsonPath('data.person.year_of_birth', 1980)
            ->assertJsonPath('data.person.gender', 'Female')
            ->assertJsonPath('data.person.race', 'Asian')
            ->assertJsonPath('data.studies.0.id', $baseline->id)
            ->assertJsonPath('data.studies.0.study_instance_uid', '1.2.3.timeline.baseline')
            ->assertJsonPath('data.studies.0.body_part_examined', 'Chest')
            ->assertJsonPath('data.measurements.0.measurement_name', 'Chest target lesion')
            ->assertJsonPath('data.measurements.0.value_as_number', 31.2)
            ->assertJsonPath('data.drug_exposures.0.drug_name', 'Osimertinib')
            ->assertJsonPath('data.summary.total_studies', 2)
            ->assertJsonPath('data.summary.total_measurements', 1)
            ->assertJsonPath('data.summary.total_drugs', 1)
            ->assertJsonPath('data.summary.date_range.first', '2026-01-01')
            ->assertJsonPath('data.summary.date_range.last', '2026-02-15')
            ->assertJsonPath('data.summary.imaging_span_days', 45);
    });
});

describe('Imaging criteria', function () {
    it('stores an imaging criterion as a DB record', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/criteria', [
                'name' => 'Baseline chest CT cohort',
                'criteria_type' => 'modality',
                'criteria_definition' => ['modality' => 'CT', 'body_part' => 'Chest'],
                'description' => 'Chest CT studies for baseline review',
                'is_shared' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Baseline chest CT cohort')
            ->assertJsonPath('data.criteria_type', 'modality')
            ->assertJsonPath('data.is_shared', true);

        expect($response->json('data.id'))->toBeInt()->not->toBe(0);

        $this->assertDatabaseHas('clinical.imaging_criteria', [
            'name' => 'Baseline chest CT cohort',
            'criteria_type' => 'modality',
            'created_by' => $this->user->id,
        ]);
    });

    it('validates required imaging criterion fields', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/criteria', []);

        $response->assertStatus(422);
    });

    it('lists persisted imaging criteria', function () {
        ImagingCriteria::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/imaging/criteria');

        $response->assertOk()
            ->assertJsonPath('success', true);

        expect(count($response->json('data')))->toBe(3);
    });

    it('filters persisted imaging criteria by type', function () {
        ImagingCriteria::factory()->create([
            'name' => 'RECIST cohort',
            'criteria_type' => 'recist',
        ]);
        ImagingCriteria::factory()->create([
            'name' => 'Modality cohort',
            'criteria_type' => 'modality',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/imaging/criteria?type=recist');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'RECIST cohort')
            ->assertJsonPath('data.0.criteria_type', 'recist');

        expect($response->json('data'))->toHaveCount(1);
    });

    it('deletes an imaging criterion', function () {
        $criterion = ImagingCriteria::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/imaging/criteria/{$criterion->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clinical.imaging_criteria', [
            'id' => $criterion->id,
        ]);
    });

    it('returns 404 when deleting a missing imaging criterion', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/imaging/criteria/99999');

        $response->assertStatus(404);
    });
});

describe('Population imaging analytics', function () {
    it('returns frontend analytics arrays and legacy distribution maps', function () {
        $firstPatient = imagingTestPatient(['mrn' => 'TCIA-ANALYTICS-001']);
        $secondPatient = imagingTestPatient(['mrn' => 'TCIA-ANALYTICS-002']);

        orthancTestStudy($firstPatient, [
            'study_uid' => '1.2.3.analytics.ct.chest.1',
            'modality' => 'CT',
            'body_part' => 'Chest',
        ]);
        orthancTestStudy($firstPatient, [
            'study_uid' => '1.2.3.analytics.ct.chest.2',
            'modality' => 'CT',
            'body_part' => 'Chest',
        ]);
        orthancTestStudy($secondPatient, [
            'study_uid' => '1.2.3.analytics.mr.brain.1',
            'modality' => 'MR',
            'body_part' => 'Brain',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/imaging/analytics/population');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_studies', 3)
            ->assertJsonPath('data.total_patients', 2)
            ->assertJsonPath('data.by_modality.0.modality', 'CT')
            ->assertJsonPath('data.by_modality.0.n', 2)
            ->assertJsonPath('data.by_modality.0.unique_persons', 1)
            ->assertJsonPath('data.by_modality.1.modality', 'MR')
            ->assertJsonPath('data.by_modality.1.n', 1)
            ->assertJsonPath('data.by_body_part.0.body_part_examined', 'Chest')
            ->assertJsonPath('data.by_body_part.0.n', 2)
            ->assertJsonPath('data.by_body_part.1.body_part_examined', 'Brain')
            ->assertJsonPath('data.by_body_part.1.n', 1)
            ->assertJsonPath('data.top_features', [])
            ->assertJsonPath('data.modality_distribution.CT', 2)
            ->assertJsonPath('data.modality_distribution.MR', 1)
            ->assertJsonPath('data.body_part_distribution.Chest', 2)
            ->assertJsonPath('data.body_part_distribution.Brain', 1);
    });

    it('applies modality filtering to analytics aggregations', function () {
        $patient = imagingTestPatient(['mrn' => 'TCIA-ANALYTICS-003']);

        orthancTestStudy($patient, [
            'study_uid' => '1.2.3.analytics.filtered.ct',
            'modality' => 'CT',
            'body_part' => 'Chest',
        ]);
        orthancTestStudy($patient, [
            'study_uid' => '1.2.3.analytics.filtered.mr',
            'modality' => 'MR',
            'body_part' => 'Brain',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/imaging/analytics/population?modality=MR');

        $response->assertOk()
            ->assertJsonPath('data.total_studies', 1)
            ->assertJsonPath('data.total_patients', 1)
            ->assertJsonPath('data.by_modality.0.modality', 'MR')
            ->assertJsonPath('data.by_modality.0.n', 1)
            ->assertJsonPath('data.by_body_part.0.body_part_examined', 'Brain')
            ->assertJsonPath('data.modality_distribution.MR', 1)
            ->assertJsonMissingPath('data.modality_distribution.CT');
    });
});

describe('Imaging response assessments', function () {
    it('persists a manually-created response assessment', function () {
        $patient = imagingTestPatient(['mrn' => 'TCIA-RESP-001']);
        $baseline = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.baseline.manual',
            'study_date' => '2026-01-01',
        ]);
        $current = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.current.manual',
            'study_date' => '2026-03-01',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/imaging/patients/{$patient->id}/response-assessments", [
                'criteria_type' => 'recist',
                'assessment_date' => '2026-03-02',
                'baseline_study_id' => $baseline->id,
                'current_study_id' => $current->id,
                'response_category' => 'PR',
                'body_site' => 'Chest',
                'baseline_value' => 40,
                'current_value' => 25,
                'percent_change_from_baseline' => -37.5,
                'rationale' => 'Manual RECIST review',
                'is_confirmed' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.person_id', $patient->id)
            ->assertJsonPath('data.criteria_type', 'recist')
            ->assertJsonPath('data.response_category', 'PR')
            ->assertJsonPath('data.is_confirmed', true)
            ->assertJsonPath('data.source_type', 'manual');

        expect($response->json('data.id'))->toBeInt()->not->toBe(0);

        $this->assertDatabaseHas('clinical.imaging_response_assessments', [
            'patient_id' => $patient->id,
            'baseline_study_id' => $baseline->id,
            'current_study_id' => $current->id,
            'response_category' => 'PR',
            'assessed_by' => $this->user->id,
            'source_type' => 'manual',
        ]);
    });

    it('returns stored response assessments before computed fallback history', function () {
        $patient = imagingTestPatient(['mrn' => 'TCIA-RESP-002']);
        $baseline = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.baseline.history',
            'study_date' => '2026-01-01',
        ]);
        $current = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.current.history',
            'study_date' => '2026-03-01',
        ]);

        $assessment = ImagingResponseAssessment::create([
            'patient_id' => $patient->id,
            'criteria_type' => 'recist',
            'assessment_date' => '2026-03-02',
            'baseline_study_id' => $baseline->id,
            'current_study_id' => $current->id,
            'baseline_value' => 40,
            'current_value' => 25,
            'percent_change_from_baseline' => -37.5,
            'response_category' => 'PR',
            'rationale' => 'Stored assessment',
            'source_type' => 'manual',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/imaging/patients/{$patient->id}/response-assessments");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $assessment->id)
            ->assertJsonPath('data.0.person_id', $patient->id)
            ->assertJsonPath('data.0.criteria_type', 'recist')
            ->assertJsonPath('data.0.source_type', 'manual');
    });

    it('persists computed RECIST response assessments idempotently', function () {
        $patient = imagingTestPatient(['mrn' => 'TCIA-RESP-003']);
        $baseline = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.baseline.computed',
            'study_date' => '2026-01-01',
        ]);
        $current = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.current.computed',
            'study_date' => '2026-03-01',
        ]);

        imagingTestMeasurement($baseline, ['value_numeric' => 40, 'measured_at' => '2026-01-01']);
        imagingTestMeasurement($current, ['value_numeric' => 25, 'measured_at' => '2026-03-01']);

        $payload = [
            'current_study_id' => $current->id,
            'baseline_study_id' => $baseline->id,
            'criteria_type' => 'auto',
        ];

        $first = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/imaging/patients/{$patient->id}/compute-response", $payload);
        $second = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/imaging/patients/{$patient->id}/compute-response", $payload);

        $first->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.person_id', $patient->id)
            ->assertJsonPath('data.criteria_type', 'recist')
            ->assertJsonPath('data.response_category', 'PR')
            ->assertJsonPath('data.percent_change_from_baseline', -37.5)
            ->assertJsonPath('data.source_type', 'computed');

        $second->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'));

        expect($first->json('data.id'))->toBeInt()->not->toBe(0);

        expect(ImagingResponseAssessment::where('patient_id', $patient->id)
            ->where('source_type', 'computed')
            ->count())->toBe(1);
    });

    it('returns computed fallback assessments in the frontend response shape when no assessment is stored', function () {
        $patient = imagingTestPatient(['mrn' => 'TCIA-RESP-004']);
        $baseline = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.baseline.fallback',
            'study_date' => '2026-01-01',
        ]);
        $current = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.response.current.fallback',
            'study_date' => '2026-03-01',
        ]);

        imagingTestMeasurement($baseline, ['value_numeric' => 40, 'measured_at' => '2026-01-01']);
        imagingTestMeasurement($current, ['value_numeric' => 25, 'measured_at' => '2026-03-01']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/imaging/patients/{$patient->id}/response-assessments");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', -$current->id)
            ->assertJsonPath('data.0.person_id', $patient->id)
            ->assertJsonPath('data.0.criteria_type', 'recist')
            ->assertJsonPath('data.0.assessment_date', '2026-03-01')
            ->assertJsonPath('data.0.response_category', 'PR')
            ->assertJsonPath('data.0.percent_change_from_baseline', -37.5)
            ->assertJsonPath('data.0.criteria', 'RECIST');
    });
});

describe('Imaging ingestion and feature extraction', function () {
    it('queues DICOMweb indexing and reuses a matching active run', function () {
        Queue::fake();

        $payload = [
            'limit' => 25,
            'modality' => 'CT',
            'index_series' => true,
        ];

        $first = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/studies/index-from-dicomweb', $payload);
        $second = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/studies/index-from-dicomweb', $payload);

        $first->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.operation', 'dicomweb_index')
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.parameters.limit', 25)
            ->assertJsonPath('data.parameters.modality', 'CT');

        $second->assertStatus(202)
            ->assertJsonPath('data.run_id', $first->json('data.run_id'));

        expect(ImagingIngestionRun::where('run_type', 'dicomweb_index')->count())->toBe(1);

        Queue::assertPushed(IndexDicomwebStudiesJob::class, 1);
        Queue::assertPushed(IndexDicomwebStudiesJob::class, function (IndexDicomwebStudiesJob $job) use ($first) {
            return $job->runId === $first->json('data.run_id') && $job->queue === 'imaging';
        });
    });

    it('processes a DICOMweb run with deterministic patient identifier matching and skipped blank PatientID rows', function () {
        config([
            'services.orthanc.base_url' => 'http://orthanc.test',
            'services.orthanc.user' => null,
            'services.orthanc.password' => null,
        ]);

        $patient = imagingTestPatient([
            'mrn' => 'TCIA-DICOMWEB-001',
        ]);

        PatientIdentifier::create([
            'patient_id' => $patient->id,
            'identifier_type' => 'tcia_subject',
            'identifier_value' => 'DICOM-PAT-001',
            'source_system' => 'TCIA',
            'source_type' => 'test',
            'source_id' => 'dicomweb-test',
        ]);

        Http::fake([
            'http://orthanc.test/dicom-web/studies/1.2.3.dicomweb.study.1/series*' => Http::response([
                [
                    '0020000E' => ['vr' => 'UI', 'Value' => ['1.2.3.dicomweb.series.1']],
                    '00200011' => ['vr' => 'IS', 'Value' => [3]],
                    '00080060' => ['vr' => 'CS', 'Value' => ['CT']],
                    '0008103E' => ['vr' => 'LO', 'Value' => ['Venous Phase']],
                    '00201209' => ['vr' => 'IS', 'Value' => [45]],
                ],
            ]),
            'http://orthanc.test/dicom-web/studies*' => Http::response([
                [
                    '0020000D' => ['vr' => 'UI', 'Value' => ['1.2.3.dicomweb.study.1']],
                    '00100020' => ['vr' => 'LO', 'Value' => ['DICOM-PAT-001']],
                    '00100021' => ['vr' => 'LO', 'Value' => ['TCIA']],
                    '00080061' => ['vr' => 'CS', 'Value' => ['CT']],
                    '00080020' => ['vr' => 'DA', 'Value' => ['20260115']],
                    '00081030' => ['vr' => 'LO', 'Value' => ['CT Chest With Contrast']],
                    '00080050' => ['vr' => 'SH', 'Value' => ['ACC-DICOMWEB-1']],
                    '00201206' => ['vr' => 'IS', 'Value' => [1]],
                    '00201208' => ['vr' => 'IS', 'Value' => [45]],
                ],
                [
                    '0020000D' => ['vr' => 'UI', 'Value' => ['1.2.3.dicomweb.study.blank']],
                    '00100020' => ['vr' => 'LO', 'Value' => ['']],
                    '00080061' => ['vr' => 'CS', 'Value' => ['MR']],
                    '00080020' => ['vr' => 'DA', 'Value' => ['20260116']],
                    '00081030' => ['vr' => 'LO', 'Value' => ['MR Brain']],
                ],
            ]),
        ]);

        [$run] = app(ImagingIngestionService::class)->createOrReuseRun(
            'dicomweb_index',
            ['limit' => 50, 'index_series' => true],
            $this->user->id,
        );

        $processed = app(ImagingIngestionService::class)->processDicomwebIndex($run);

        expect($processed->status)->toBe('succeeded')
            ->and($processed->requested_count)->toBe(2)
            ->and($processed->processed_count)->toBe(2)
            ->and($processed->studies_created)->toBe(1)
            ->and($processed->studies_skipped)->toBe(1)
            ->and($processed->series_created)->toBe(1);

        $this->assertDatabaseHas('clinical.imaging_studies', [
            'patient_id' => $patient->id,
            'study_uid' => '1.2.3.dicomweb.study.1',
            'modality' => 'CT',
            'study_date' => '2026-01-15',
            'description' => 'CT Chest With Contrast',
            'accession_number' => 'ACC-DICOMWEB-1',
            'source_type' => 'dicomweb',
        ]);

        $study = ImagingStudy::where('study_uid', '1.2.3.dicomweb.study.1')->firstOrFail();

        $this->assertDatabaseHas('clinical.imaging_series', [
            'imaging_study_id' => $study->id,
            'series_uid' => '1.2.3.dicomweb.series.1',
            'series_number' => 3,
            'num_instances' => 45,
            'source_type' => 'dicomweb',
        ]);

        expect($processed->result['skipped'][0]['reason'])->toBe('blank_patient_id');
    });

    it('rejects local import paths outside configured allowlisted roots', function () {
        config([
            'services.imaging.local_import_roots' => [storage_path('app/dicom-imports')],
            'services.imaging.local_import_command' => '/bin/true',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/import-local/trigger', [
                'path' => '/etc',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    });

    it('queues local import for an allowlisted path', function () {
        Queue::fake();

        $root = storage_path('app/dicom-imports/test-run');
        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }

        config([
            'services.imaging.local_import_roots' => [storage_path('app/dicom-imports')],
            'services.imaging.local_import_command' => '/bin/true',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/import-local/trigger', [
                'path' => $root,
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.operation', 'local_import')
            ->assertJsonPath('data.status', 'queued');

        Queue::assertPushed(ImportLocalDicomJob::class, 1);
    });

    it('persists AI-extracted imaging features and lists them', function () {
        config([
            'services.ai.base_url' => 'http://ai.test',
        ]);

        $patient = imagingTestPatient(['mrn' => 'TCIA-FEATURE-001']);
        $study = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.features.study',
            'body_part' => 'Chest',
        ]);

        Http::fake([
            'http://ai.test/api/ai/imaging/extract-features' => Http::response([
                'study_id' => $study->id,
                'features' => [
                    [
                        'feature_name' => 'Spiculated nodule',
                        'category' => 'morphology',
                        'value' => 'Right upper lobe spiculated nodule',
                        'confidence' => 0.87,
                    ],
                ],
                'feature_count' => 1,
            ]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/imaging/studies/{$study->id}/extract-nlp");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.extracted', 1)
            ->assertJsonPath('data.features.0.feature_name', 'Spiculated nodule')
            ->assertJsonPath('data.features.0.requires_review', true);

        $this->assertDatabaseHas('clinical.imaging_features', [
            'imaging_study_id' => $study->id,
            'patient_id' => $patient->id,
            'feature_type' => 'morphology',
            'feature_name' => 'Spiculated nodule',
            'source_type' => 'ai_feature_extraction',
        ]);

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/imaging/features?study_id={$study->id}");

        $list->assertOk()
            ->assertJsonPath('data.0.feature_name', 'Spiculated nodule')
            ->assertJsonPath('data.0.feature_type', 'morphology')
            ->assertJsonPath('data.0.person_id', $patient->id)
            ->assertJsonPath('meta.total', 1);
    });

    it('persists AI volume measurements when the AI service returns extractable values', function () {
        config([
            'services.ai.base_url' => 'http://ai.test',
        ]);

        $patient = imagingTestPatient(['mrn' => 'TCIA-AI-MEASURE-001']);
        $study = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.ai.measure.study',
            'body_part' => 'Liver',
        ]);

        Http::fake([
            'http://ai.test/api/ai/imaging/volume' => Http::response([
                'study_id' => $study->id,
                'measurement_type' => 'tumor_volume',
                'volume_cm3' => 12.4,
                'longest_diameter_mm' => 31.2,
                'perpendicular_diameter_mm' => null,
                'measurement_count' => 2,
                'interpretation' => 'Derived from available measurements',
            ]),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/imaging/studies/{$study->id}/ai-extract");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.extracted', 2)
            ->assertJsonPath('data.measurements.0.measurement_type', 'tumor_volume')
            ->assertJsonPath('data.requires_review', true);

        $this->assertDatabaseHas('clinical.imaging_measurements', [
            'imaging_study_id' => $study->id,
            'measurement_type' => 'tumor_volume',
            'value_numeric' => 12.4,
            'unit' => 'cm3',
            'source_type' => 'ai_extraction',
        ]);
    });

    it('fails auto-link explicitly instead of returning stub success', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/imaging/studies/auto-link');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.rules.blank_patient_id', 'manual_review');
    });

    it('suggests a real measurement template from study metadata', function () {
        $patient = imagingTestPatient(['mrn' => 'TCIA-TEMPLATE-001']);
        $study = orthancTestStudy($patient, [
            'study_uid' => '1.2.3.template.study',
            'modality' => 'CT',
            'body_part' => 'Chest',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/imaging/studies/{$study->id}/suggest-template");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.template', 'ct-chest-recist')
            ->assertJsonPath('data.fields.0.type', 'longest_diameter')
            ->assertJsonPath('data.fields.2.unit', 'HU');
    });
});
