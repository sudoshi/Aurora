<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\PatientIdentifier;
use App\Services\Genomics\FhirGenomicsReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * FHIR R4 structural conformance for the genomics Bundle export.
 *
 * This is a focused, infra-free structural validator (the full HL7 Java
 * validator is out of scope). It asserts the emitted Bundle and its resources
 * satisfy R4 required-element + cardinality + value-domain invariants for the
 * one real outbound FHIR surface: FhirGenomicsReportExporter.
 *
 * Per the D2 de-identification decision, the internal FHIR export stays
 * identified for internal use; de-id is intentionally NOT asserted here.
 */

// R4 Bundle.type value set (http://hl7.org/fhir/R4/valueset-bundle-type.html)
const FHIR_BUNDLE_TYPES = [
    'document', 'message', 'transaction', 'transaction-response',
    'batch', 'batch-response', 'history', 'searchset', 'collection',
];

// R4 DiagnosticReport.status value set
const FHIR_DIAGNOSTIC_REPORT_STATUSES = [
    'registered', 'partial', 'preliminary', 'final', 'amended',
    'corrected', 'appended', 'cancelled', 'entered-in-error', 'unknown',
];

// R4 Observation.status value set
const FHIR_OBSERVATION_STATUSES = [
    'registered', 'preliminary', 'final', 'amended',
    'corrected', 'cancelled', 'entered-in-error', 'unknown',
];

/**
 * A reference is structurally valid if it is a non-empty string in one of the
 * accepted R4 forms: ResourceType/id (relative), urn:uuid:..., or an absolute URL.
 */
function fhirReferenceIsWellFormed(string $reference): bool
{
    if ($reference === '') {
        return false;
    }

    if (str_starts_with($reference, 'urn:uuid:')) {
        return true;
    }

    if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
        return true;
    }

    // Relative reference: ResourceType/id
    return (bool) preg_match('#^[A-Z][A-Za-z]+/[A-Za-z0-9\-\.]{1,64}$#', $reference);
}

function assertCodeableConcept(array $cc, string $context, bool $requireCoding = true): void
{
    expect($cc)->toBeArray("{$context}: CodeableConcept must be an object");

    $hasCoding = isset($cc['coding']) && is_array($cc['coding']) && $cc['coding'] !== [];
    $hasText = isset($cc['text']) && $cc['text'] !== '';

    // R4: a CodeableConcept must carry at least coding or text.
    expect($hasCoding || $hasText)->toBeTrue("{$context}: CodeableConcept must have coding[] or text");

    if ($requireCoding) {
        expect($hasCoding)->toBeTrue("{$context}: CodeableConcept must have at least one coding");
    }

    foreach ($cc['coding'] ?? [] as $i => $coding) {
        // Coding.system, when present, must be an absolute URL/URI, not a bare token.
        if (isset($coding['system'])) {
            expect($coding['system'])->toBeString("{$context}: coding[{$i}].system must be a string");
            expect(filter_var($coding['system'], FILTER_VALIDATE_URL) !== false
                || str_starts_with($coding['system'], 'urn:'))
                ->toBeTrue("{$context}: coding[{$i}].system must be a URL/URI, got '{$coding['system']}'");
        }
        // A coding that carries a code SHOULD also carry a system (terminology binding).
        if (isset($coding['code']) && $coding['code'] !== '') {
            expect(isset($coding['system']) && $coding['system'] !== '')
                ->toBeTrue("{$context}: coding[{$i}] has code but no system");
        }
    }
}

beforeEach(function () {
    config(['app.url' => 'https://aurora.test']);

    $this->exporter = new FhirGenomicsReportExporter;
});

function seedConformancePatient(): ClinicalPatient
{
    $patient = ClinicalPatient::factory()->create([
        'mrn' => 'MRN-FHIR-CONF-01',
        'first_name' => 'Conform',
        'last_name' => 'Tester',
        'date_of_birth' => '1980-06-15',
        'sex' => 'female',
    ]);

    PatientIdentifier::create([
        'patient_id' => $patient->id,
        'identifier_type' => 'sample',
        'identifier_value' => 'SAMPLE-CONF-01',
        'source_system' => 'lab',
    ]);

    GenomicVariant::factory()->create([
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

    GenomicVariant::factory()->create([
        'patient_id' => $patient->id,
        'gene' => 'TP53',
        'variant' => 'R175H',
        'variant_type' => 'SNV',
        'clinical_significance' => 'likely_pathogenic',
        'actionability' => null,
        'source_type' => null,
        'source_id' => null,
    ]);

    return $patient;
}

it('emits a Bundle with a valid type and resourceType on every entry resource', function () {
    $patient = seedConformancePatient();

    $bundle = $this->exporter->exportForPatient($patient);

    expect($bundle['resourceType'] ?? null)->toBe('Bundle');
    expect($bundle['type'] ?? null)->toBeIn(FHIR_BUNDLE_TYPES);
    expect($bundle['entry'] ?? null)->toBeArray()->not->toBeEmpty();

    foreach ($bundle['entry'] as $i => $entry) {
        expect($entry['resource'] ?? null)->toBeArray("entry[{$i}] must have a resource");
        expect($entry['resource']['resourceType'] ?? null)
            ->toBeString("entry[{$i}].resource.resourceType must be present");

        // collection bundle entries SHOULD carry a fullUrl; if present it must be a URL.
        if (isset($entry['fullUrl'])) {
            expect(filter_var($entry['fullUrl'], FILTER_VALIDATE_URL) !== false
                || str_starts_with($entry['fullUrl'], 'urn:'))
                ->toBeTrue("entry[{$i}].fullUrl must be a URL/URN");
        }
    }
});

it('emits a DiagnosticReport satisfying R4 required elements', function () {
    $patient = seedConformancePatient();

    $bundle = $this->exporter->exportForPatient($patient);
    $entries = collect($bundle['entry']);
    $report = $entries->firstWhere('resource.resourceType', 'DiagnosticReport')['resource'];

    // status (1..1) in value set
    expect($report['status'] ?? null)->toBeIn(FHIR_DIAGNOSTIC_REPORT_STATUSES);

    // code (1..1) CodeableConcept with coding system+code
    assertCodeableConcept($report['code'] ?? [], 'DiagnosticReport.code');

    // subject reference present and well-formed
    expect($report['subject']['reference'] ?? null)->toBeString('DiagnosticReport.subject.reference required');
    expect(fhirReferenceIsWellFormed($report['subject']['reference']))
        ->toBeTrue('DiagnosticReport.subject.reference must be well-formed');

    // category, when present, is a CodeableConcept[]
    foreach ($report['category'] ?? [] as $i => $category) {
        assertCodeableConcept($category, "DiagnosticReport.category[{$i}]");
    }

    // result references must resolve to Observations in the bundle
    $observationIds = $entries
        ->filter(fn (array $e): bool => ($e['resource']['resourceType'] ?? null) === 'Observation')
        ->map(fn (array $e): string => $e['resource']['id'])
        ->all();

    foreach ($report['result'] ?? [] as $i => $result) {
        expect($result['reference'] ?? null)->toBeString("DiagnosticReport.result[{$i}].reference required");
        expect(fhirReferenceIsWellFormed($result['reference']))
            ->toBeTrue("DiagnosticReport.result[{$i}].reference must be well-formed");

        // Reference is ResourceType/id form -> the id must exist in the bundle (no dangling refs).
        [$type, $id] = explode('/', $result['reference'], 2);
        expect($type)->toBe('Observation', "DiagnosticReport.result[{$i}] must reference an Observation");
        expect(in_array($id, $observationIds, true))
            ->toBeTrue("DiagnosticReport.result[{$i}] references missing Observation {$id}");
    }
});

it('emits variant Observations satisfying R4 required elements and well-formed components', function () {
    $patient = seedConformancePatient();

    $bundle = $this->exporter->exportForPatient($patient);
    $observations = collect($bundle['entry'])
        ->filter(fn (array $e): bool => ($e['resource']['resourceType'] ?? null) === 'Observation')
        ->map(fn (array $e): array => $e['resource']);

    expect($observations)->not->toBeEmpty();

    foreach ($observations as $obs) {
        $ctx = "Observation/{$obs['id']}";

        // status (1..1) in value set
        expect($obs['status'] ?? null)->toBeIn(FHIR_OBSERVATION_STATUSES);

        // code (1..1) CodeableConcept with coding carrying system + code
        assertCodeableConcept($obs['code'] ?? [], "{$ctx}.code");

        // subject reference present and well-formed
        expect($obs['subject']['reference'] ?? null)->toBeString("{$ctx}.subject.reference required");
        expect(fhirReferenceIsWellFormed($obs['subject']['reference']))
            ->toBeTrue("{$ctx}.subject.reference must be well-formed");

        // category[] are CodeableConcepts
        foreach ($obs['category'] ?? [] as $i => $category) {
            assertCodeableConcept($category, "{$ctx}.category[{$i}]");
        }

        // identifiers: system must be a URI, value non-empty
        foreach ($obs['identifier'] ?? [] as $i => $identifier) {
            expect($identifier['system'] ?? null)->toBeString("{$ctx}.identifier[{$i}].system required");
            expect(filter_var($identifier['system'], FILTER_VALIDATE_URL) !== false
                || str_starts_with($identifier['system'], 'urn:'))
                ->toBeTrue("{$ctx}.identifier[{$i}].system must be a URI");
            expect($identifier['value'] ?? null)->not->toBe('', "{$ctx}.identifier[{$i}].value required");
        }

        // components: each has a CodeableConcept code + exactly one value[x]; codings carry system+code
        foreach ($obs['component'] ?? [] as $i => $component) {
            assertCodeableConcept($component['code'] ?? [], "{$ctx}.component[{$i}].code");

            $valueKeys = array_values(array_filter(
                array_keys($component),
                fn (string $k): bool => str_starts_with($k, 'value'),
            ));
            expect(count($valueKeys))->toBe(1, "{$ctx}.component[{$i}] must have exactly one value[x]");

            if (in_array('valueQuantity', $valueKeys, true)) {
                expect($component['valueQuantity']['value'] ?? null)
                    ->toBeNumeric("{$ctx}.component[{$i}].valueQuantity.value must be numeric");
            }
        }
    }
});

it('emits a Patient resource that is structurally valid (identified per D2)', function () {
    $patient = seedConformancePatient();

    $bundle = $this->exporter->exportForPatient($patient);
    $patientResource = collect($bundle['entry'])->firstWhere('resource.resourceType', 'Patient')['resource'];

    expect($patientResource['resourceType'])->toBe('Patient');
    expect($patientResource['id'] ?? null)->toBeString('Patient.id required');

    if (isset($patientResource['gender'])) {
        expect($patientResource['gender'])->toBeIn(['male', 'female', 'other', 'unknown']);
    }

    if (isset($patientResource['birthDate'])) {
        expect($patientResource['birthDate'])->toMatch('/^\d{4}(-\d{2}(-\d{2})?)?$/');
    }

    foreach ($patientResource['identifier'] ?? [] as $i => $identifier) {
        expect($identifier['system'] ?? null)->toBeString("Patient.identifier[{$i}].system required");
        expect($identifier['value'] ?? null)->not->toBe('', "Patient.identifier[{$i}].value required");
    }
});

it('uses real terminology systems (URLs) for variant report codings', function () {
    $patient = seedConformancePatient();

    $bundle = $this->exporter->exportForPatient($patient);
    $entries = collect($bundle['entry']);

    $report = $entries->firstWhere('resource.resourceType', 'DiagnosticReport')['resource'];
    expect($report['code']['coding'][0]['system'])->toBe('http://loinc.org');

    $observation = $entries
        ->filter(fn (array $e): bool => ($e['resource']['resourceType'] ?? null) === 'Observation')
        ->first()['resource'];

    // The variant Observation claims the genomics-reporting variant profile, so its
    // code must carry the LOINC variant-assessment coding (real URL system).
    expect($observation['code']['coding'][0]['system'])->toBe('http://loinc.org');
    expect($observation['code']['coding'][0]['code'])->toBe('69548-6');
});

it('produces a structurally conformant Bundle even with no variants', function () {
    $patient = ClinicalPatient::factory()->create();

    $bundle = $this->exporter->exportForPatient($patient);

    expect($bundle['type'])->toBeIn(FHIR_BUNDLE_TYPES);

    $report = collect($bundle['entry'])->firstWhere('resource.resourceType', 'DiagnosticReport')['resource'];
    expect($report['status'])->toBeIn(FHIR_DIAGNOSTIC_REPORT_STATUSES);
    assertCodeableConcept($report['code'], 'DiagnosticReport.code (empty bundle)');
    expect($report['subject']['reference'] ?? null)->toBeString();
    expect($report)->not->toHaveKey('result');
});
