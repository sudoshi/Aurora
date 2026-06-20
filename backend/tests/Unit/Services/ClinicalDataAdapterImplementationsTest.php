<?php

use App\Models\Clinical\ClinicalNote;
use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\Measurement;
use App\Models\Clinical\Medication;
use App\Models\Clinical\Observation;
use App\Models\Clinical\Procedure;
use App\Models\Clinical\Visit;
use App\Services\Adapters\FhirAdapter;
use App\Services\Adapters\OmopAdapter;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['clinical.adapter' => 'manual']);

    $this->patient = ClinicalPatient::create([
        'mrn' => 'MRN-INTEROP-01',
        'first_name' => 'Interoperable',
        'last_name' => 'Patient',
        'date_of_birth' => '1982-04-14',
        'sex' => 'Female',
        'race' => 'Unknown',
        'ethnicity' => 'Unknown',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'outpatient',
        'facility' => 'Aurora Clinic',
        'admission_date' => '2026-01-10 09:00:00',
        'discharge_date' => '2026-01-10 10:30:00',
    ]);

    Condition::create([
        'patient_id' => $this->patient->id,
        'concept_name' => 'Malignant neoplasm of lung',
        'concept_code' => 'C34.90',
        'vocabulary' => 'ICD10CM',
        'domain' => 'oncology',
        'status' => 'active',
        'onset_date' => '2025-11-01',
    ]);

    Medication::create([
        'patient_id' => $this->patient->id,
        'drug_name' => 'Osimertinib',
        'concept_code' => '1597891',
        'vocabulary' => 'RxNorm',
        'route' => 'oral',
        'dose_value' => 80,
        'dose_unit' => 'mg',
        'frequency' => 'daily',
        'start_date' => '2025-11-05',
        'status' => 'active',
    ]);

    Procedure::create([
        'patient_id' => $this->patient->id,
        'procedure_name' => 'Lung biopsy',
        'concept_code' => '0BBF3ZX',
        'vocabulary' => 'ICD10PCS',
        'performed_date' => '2025-10-25',
    ]);

    Measurement::create([
        'patient_id' => $this->patient->id,
        'measurement_name' => 'CEA',
        'concept_code' => '2039-6',
        'vocabulary' => 'LOINC',
        'value_numeric' => 3.5,
        'unit' => 'ng/mL',
        'reference_range_low' => 0,
        'reference_range_high' => 5,
        'measured_at' => '2026-01-10 09:15:00',
    ]);

    Observation::create([
        'patient_id' => $this->patient->id,
        'observation_name' => 'Smoking status',
        'value_text' => 'Former smoker',
        'observed_at' => '2026-01-10 09:20:00',
    ]);

    ClinicalNote::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'note_type' => 'progress',
        'title' => 'Oncology follow-up',
        'content' => 'Patient tolerating therapy.',
        'authored_at' => '2026-01-10 09:30:00',
    ]);

    ImagingStudy::create([
        'patient_id' => $this->patient->id,
        'study_uid' => '1.2.826.0.1.3680043.8.498.1001',
        'modality' => 'CT',
        'study_date' => '2026-01-09',
        'description' => 'CT chest',
    ]);

    GenomicVariant::create([
        'patient_id' => $this->patient->id,
        'gene' => 'EGFR',
        'variant' => 'L858R',
        'variant_type' => 'SNV',
        'allele_frequency' => 0.42,
        'clinical_significance' => 'pathogenic',
    ]);
});

it('projects local clinical data through FHIR R4 resources without breaking profile consumers', function () {
    $adapter = new FhirAdapter;

    $profile = $adapter->getFullProfile((string) $this->patient->id);
    $search = $adapter->searchPatients('Interoperable');

    expect($profile)->toHaveKeys([
        'patient', 'conditions', 'medications', 'procedures',
        'measurements', 'observations', 'visits', 'notes',
        'imaging', 'genomics',
    ]);

    expect($profile['patient']['mrn'])->toBe('MRN-INTEROP-01');
    expect($profile['patient']['adapter'])->toBe('fhir');
    expect($profile['patient']['fhir']['resourceType'])->toBe('Patient');
    expect($profile['patient']['fhir']['gender'])->toBe('female');
    expect($profile['conditions'][0]['concept_name'])->toBe('Malignant neoplasm of lung');
    expect($profile['conditions'][0]['fhir_resource_type'])->toBe('Condition');
    expect($profile['medications'][0]['fhir']['resourceType'])->toBe('MedicationStatement');
    expect($profile['procedures'][0]['fhir']['resourceType'])->toBe('Procedure');
    expect($profile['measurements'][0]['fhir']['resourceType'])->toBe('Observation');
    expect($profile['observations'][0]['fhir']['valueString'])->toBe('Former smoker');
    expect($profile['visits'][0]['fhir']['resourceType'])->toBe('Encounter');
    expect($profile['notes']['data'][0]['fhir']['resourceType'])->toBe('DocumentReference');
    expect($profile['imaging'][0]['fhir_resource_type'])->toBe('ImagingStudy');
    expect($profile['genomics'][0]['fhir']['category'][0]['text'])->toBe('genomics');
    expect($search[0]['fhir_resource_type'])->toBe('Patient');
});

it('projects local clinical data through OMOP CDM source records without breaking profile consumers', function () {
    $adapter = new OmopAdapter;

    $profile = $adapter->getFullProfile((string) $this->patient->id);
    $search = $adapter->searchPatients('Interoperable');

    expect($profile['patient']['mrn'])->toBe('MRN-INTEROP-01');
    expect($profile['patient']['adapter'])->toBe('omop');
    expect($profile['patient']['omop_table'])->toBe('person');
    expect($profile['patient']['omop']['person_source_value'])->toBe('MRN-INTEROP-01');
    expect($profile['patient']['omop']['year_of_birth'])->toBe(1982);
    expect($profile['conditions'][0]['omop_table'])->toBe('condition_occurrence');
    expect($profile['conditions'][0]['omop']['condition_source_value'])->toBe('C34.90');
    expect($profile['medications'][0]['omop_table'])->toBe('drug_exposure');
    expect($profile['procedures'][0]['omop_table'])->toBe('procedure_occurrence');
    expect($profile['measurements'][0]['omop_table'])->toBe('measurement');
    expect($profile['observations'][0]['omop_table'])->toBe('observation');
    expect($profile['visits'][0]['omop_table'])->toBe('visit_occurrence');
    expect($profile['notes']['data'][0]['omop_table'])->toBe('note');
    expect($profile['imaging'][0]['omop']['qualifier_source_value'])->toBe('imaging_study');
    expect($profile['genomics'][0]['omop']['qualifier_source_value'])->toBe('genomic_variant');
    expect($search[0]['omop_table'])->toBe('person');
});

it('selects the configured clinical data adapter in PatientService', function () {
    config(['clinical.adapter' => 'fhir']);

    $fhirProfile = (new PatientService)->getProfile((string) $this->patient->id);

    config(['clinical.adapter' => 'omop']);

    $omopProfile = (new PatientService)->getProfile((string) $this->patient->id);

    expect($fhirProfile['patient']['adapter'])->toBe('fhir');
    expect($omopProfile['patient']['adapter'])->toBe('omop');
});

it('fails fast for an unsupported configured clinical data adapter', function () {
    config(['clinical.adapter' => 'unknown']);

    expect(fn () => new PatientService)->toThrow(\InvalidArgumentException::class);
});
