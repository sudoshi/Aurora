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
use App\Services\Adapters\ManualAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adapter = new ManualAdapter();

    $this->patient = ClinicalPatient::create([
        'mrn' => 'MRN-ADAPTER-01',
        'first_name' => 'Adapter',
        'last_name' => 'Test',
        'date_of_birth' => '1990-05-20',
        'sex' => 'Male',
    ]);
});

describe('ManualAdapter::getPatient', function () {
    it('returns patient array when found', function () {
        $result = $this->adapter->getPatient((string) $this->patient->id);

        expect($result)->toBeArray();
        expect($result['mrn'])->toBe('MRN-ADAPTER-01');
        expect($result['first_name'])->toBe('Adapter');
    });

    it('returns null when patient not found', function () {
        $result = $this->adapter->getPatient('99999');

        expect($result)->toBeNull();
    });
});

describe('ManualAdapter::getConditions', function () {
    it('returns conditions array', function () {
        Condition::create([
            'patient_id' => $this->patient->id,
            'concept_name' => 'NSCLC',
            'concept_code' => 'C34.1',
            'vocabulary' => 'ICD10',
            'domain' => 'oncology',
            'status' => 'active',
        ]);

        $result = $this->adapter->getConditions((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['concept_name'])->toBe('NSCLC');
    });

    it('returns empty array when no conditions', function () {
        $result = $this->adapter->getConditions((string) $this->patient->id);

        expect($result)->toBeArray()->toBeEmpty();
    });
});

describe('ManualAdapter::getMedications', function () {
    it('returns medications array', function () {
        Medication::create([
            'patient_id' => $this->patient->id,
            'drug_name' => 'Osimertinib',
            'status' => 'active',
        ]);

        $result = $this->adapter->getMedications((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['drug_name'])->toBe('Osimertinib');
    });
});

describe('ManualAdapter::getProcedures', function () {
    it('returns procedures array', function () {
        Procedure::create([
            'patient_id' => $this->patient->id,
            'procedure_name' => 'Lobectomy',
            'performed_date' => '2025-06-15',
        ]);

        $result = $this->adapter->getProcedures((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['procedure_name'])->toBe('Lobectomy');
    });
});

describe('ManualAdapter::getMeasurements', function () {
    it('returns measurements array', function () {
        Measurement::create([
            'patient_id' => $this->patient->id,
            'measurement_name' => 'CEA',
            'value_numeric' => 3.5,
            'unit' => 'ng/mL',
            'measured_at' => '2025-10-01 10:00:00',
        ]);

        $result = $this->adapter->getMeasurements((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['measurement_name'])->toBe('CEA');
    });
});

describe('ManualAdapter::getObservations', function () {
    it('returns observations array', function () {
        Observation::create([
            'patient_id' => $this->patient->id,
            'observation_name' => 'Smoking Status',
            'value_text' => 'Former smoker',
            'observed_at' => '2025-10-01 10:00:00',
        ]);

        $result = $this->adapter->getObservations((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['observation_name'])->toBe('Smoking Status');
    });
});

describe('ManualAdapter::getVisits', function () {
    it('returns visits array', function () {
        Visit::create([
            'patient_id' => $this->patient->id,
            'visit_type' => 'outpatient',
            'facility' => 'Main Campus',
            'admission_date' => '2025-10-01 09:00:00',
        ]);

        $result = $this->adapter->getVisits((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['visit_type'])->toBe('outpatient');
    });
});

describe('ManualAdapter::getNotes', function () {
    it('returns paginated notes structure', function () {
        ClinicalNote::create([
            'patient_id' => $this->patient->id,
            'note_type' => 'progress',
            'content' => 'Patient doing well.',
            'authored_at' => '2025-10-01 10:00:00',
        ]);

        $result = $this->adapter->getNotes((string) $this->patient->id);

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['data', 'total', 'page', 'per_page', 'last_page']);
        expect($result['total'])->toBe(1);
    });
});

describe('ManualAdapter::getImaging', function () {
    it('returns imaging studies array', function () {
        ImagingStudy::create([
            'patient_id' => $this->patient->id,
            'study_uid' => '1.2.3.4.5',
            'modality' => 'CT',
            'study_date' => '2025-09-15',
            'description' => 'Chest CT',
        ]);

        $result = $this->adapter->getImaging((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['modality'])->toBe('CT');
    });
});

describe('ManualAdapter::getGenomics', function () {
    it('returns genomic variants array', function () {
        GenomicVariant::create([
            'patient_id' => $this->patient->id,
            'gene' => 'EGFR',
            'variant' => 'L858R',
            'variant_type' => 'SNV',
            'clinical_significance' => 'pathogenic',
        ]);

        $result = $this->adapter->getGenomics((string) $this->patient->id);

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['gene'])->toBe('EGFR');
    });
});

describe('ManualAdapter::getFullProfile', function () {
    it('aggregates all domains', function () {
        Condition::create([
            'patient_id' => $this->patient->id,
            'concept_name' => 'Melanoma',
            'status' => 'active',
        ]);

        GenomicVariant::create([
            'patient_id' => $this->patient->id,
            'gene' => 'BRAF',
            'variant' => 'V600E',
            'variant_type' => 'SNV',
        ]);

        $result = $this->adapter->getFullProfile((string) $this->patient->id);

        expect($result)->toBeArray();
        expect($result)->toHaveKeys([
            'patient', 'conditions', 'medications', 'procedures',
            'measurements', 'observations', 'visits', 'notes',
            'imaging', 'genomics',
        ]);
        expect($result['patient']['mrn'])->toBe('MRN-ADAPTER-01');
        expect($result['conditions'])->toHaveCount(1);
        expect($result['genomics'])->toHaveCount(1);
    });

    it('returns empty array for non-existent patient', function () {
        $result = $this->adapter->getFullProfile('99999');

        expect($result)->toBeArray()->toBeEmpty();
    });
});

describe('ManualAdapter::searchPatients', function () {
    it('finds patients by first name', function () {
        ClinicalPatient::create([
            'mrn' => 'MRN-SEARCH-A',
            'first_name' => 'Zelda',
            'last_name' => 'Princess',
        ]);

        $result = $this->adapter->searchPatients('Zelda');

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['first_name'])->toBe('Zelda');
    });

    it('finds patients by MRN', function () {
        $result = $this->adapter->searchPatients('MRN-ADAPTER-01');

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['mrn'])->toBe('MRN-ADAPTER-01');
    });

    it('finds patients by condition concept name', function () {
        Condition::create([
            'patient_id' => $this->patient->id,
            'concept_name' => 'Glioblastoma',
            'status' => 'active',
        ]);

        $result = $this->adapter->searchPatients('Glioblastoma');

        expect($result)->toBeArray()->toHaveCount(1);
        expect($result[0]['mrn'])->toBe('MRN-ADAPTER-01');
    });

    it('returns empty array when no match', function () {
        $result = $this->adapter->searchPatients('ZZZZNOTEXIST');

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('respects limit parameter', function () {
        for ($i = 0; $i < 5; $i++) {
            ClinicalPatient::create([
                'mrn' => "MRN-LIMIT-{$i}",
                'first_name' => 'Limit',
                'last_name' => "Test{$i}",
            ]);
        }

        $result = $this->adapter->searchPatients('Limit', 3);

        expect($result)->toBeArray()->toHaveCount(3);
    });
});
