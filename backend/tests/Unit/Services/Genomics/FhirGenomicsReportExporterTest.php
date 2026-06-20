<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\PatientIdentifier;
use App\Services\Genomics\FhirGenomicsReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.url' => 'https://aurora.test']);

    $this->exporter = new FhirGenomicsReportExporter;
});

it('exports a FHIR R4 collection bundle with a genomic report and variant observations', function () {
    $patient = ClinicalPatient::factory()->create([
        'mrn' => 'MRN-FHIR-GX-01',
        'first_name' => 'Fiona',
        'last_name' => 'Genomics',
        'date_of_birth' => '1977-03-20',
        'sex' => 'female',
    ]);

    PatientIdentifier::create([
        'patient_id' => $patient->id,
        'identifier_type' => 'sample',
        'identifier_value' => 'SAMPLE-FHIR-GX-01',
        'source_system' => 'lab',
    ]);

    $variant = GenomicVariant::factory()->create([
        'patient_id' => $patient->id,
        'gene' => 'EGFR',
        'variant' => 'L858R',
        'variant_type' => 'SNV',
        'chromosome' => '7',
        'position' => 55259515,
        'ref_allele' => 'T',
        'alt_allele' => 'G',
        'zygosity' => 'heterozygous',
        'allele_frequency' => 0.42,
        'clinical_significance' => 'pathogenic',
        'actionability' => 'actionable',
        'source_type' => 'upload',
        'source_id' => '12',
    ]);

    $bundle = $this->exporter->exportForPatient($patient);
    $entries = collect($bundle['entry']);
    $patientResource = $entries->firstWhere('resource.resourceType', 'Patient')['resource'];
    $report = $entries->firstWhere('resource.resourceType', 'DiagnosticReport')['resource'];
    $observation = $entries
        ->filter(fn (array $entry): bool => ($entry['resource']['resourceType'] ?? null) === 'Observation')
        ->first()['resource'];

    expect($bundle['resourceType'])->toBe('Bundle');
    expect($bundle['type'])->toBe('collection');
    expect($entries)->toHaveCount(3);
    expect($patientResource['identifier'])->toHaveCount(2);
    expect($patientResource['gender'])->toBe('female');
    expect($report['meta']['profile'][0])->toBe(FhirGenomicsReportExporter::GENOMIC_REPORT_PROFILE);
    expect($report['category'][0]['coding'][0]['code'])->toBe('GE');
    expect($report['code']['coding'][0]['code'])->toBe('51969-4');
    expect($report['result'][0]['reference'])->toBe("Observation/aurora-genomic-variant-{$variant->id}");
    expect($observation['meta']['profile'][0])->toBe(FhirGenomicsReportExporter::VARIANT_PROFILE);
    expect($observation['valueString'])->toBe('EGFR L858R (pathogenic)');
    expect($observation['identifier'][1]['system'])->toBe('urn:aurora:source:upload');

    $components = collect($observation['component'])->keyBy('code.coding.0.code');
    expect($components['gene']['valueString'])->toBe('EGFR');
    expect($components['position']['valueQuantity']['value'])->toBe(55259515.0);
    expect($components['allele_frequency']['valueQuantity']['value'])->toBe(0.42);
    expect($components['clinical_significance']['valueString'])->toBe('pathogenic');
    expect($components['actionability']['valueString'])->toBe('actionable');
});

it('exports an empty genomic report for patients without stored variants', function () {
    $patient = ClinicalPatient::factory()->create();

    $bundle = $this->exporter->exportForPatient($patient);
    $report = collect($bundle['entry'])->firstWhere('resource.resourceType', 'DiagnosticReport')['resource'];

    expect($bundle['entry'])->toHaveCount(2);
    expect($report)->not->toHaveKey('result');
    expect($report['conclusion'])->toContain('0 locally stored genomic variants');
    expect($this->exporter->variantCountForPatient($patient))->toBe(0);
});
