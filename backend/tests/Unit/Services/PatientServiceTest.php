<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\Medication;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->patientService = new PatientService;

    $this->patient = ClinicalPatient::create([
        'mrn' => 'MRN-PATSVC-01',
        'first_name' => 'Service',
        'last_name' => 'Test',
        'date_of_birth' => '1985-03-15',
        'sex' => 'Female',
    ]);
});

// ─── getStats ────────────────────────────────────────────────────────

describe('PatientService::getStats', function () {
    it('returns all 9 domain counts', function () {
        $stats = $this->patientService->getStats((string) $this->patient->id);

        expect($stats)->toBeArray()->toHaveKeys([
            'conditions', 'medications', 'procedures', 'measurements',
            'observations', 'visits', 'notes', 'imaging_studies', 'genomic_variants',
        ]);
    });

    it('returns all zeros for patient with no clinical data', function () {
        $stats = $this->patientService->getStats((string) $this->patient->id);

        foreach ($stats as $domain => $count) {
            expect($count)->toBe(0, "Expected {$domain} to be 0");
        }
    });

    it('returns correct counts when clinical records are seeded', function () {
        // Seed 2 conditions
        Condition::create([
            'patient_id' => $this->patient->id,
            'concept_name' => 'NSCLC',
            'status' => 'active',
        ]);
        Condition::create([
            'patient_id' => $this->patient->id,
            'concept_name' => 'Hypertension',
            'status' => 'active',
        ]);

        // Seed 1 medication
        Medication::create([
            'patient_id' => $this->patient->id,
            'drug_name' => 'Osimertinib',
            'status' => 'active',
        ]);

        // Seed 1 genomic variant
        GenomicVariant::create([
            'patient_id' => $this->patient->id,
            'gene' => 'EGFR',
            'variant' => 'L858R',
            'variant_type' => 'SNV',
        ]);

        $stats = $this->patientService->getStats((string) $this->patient->id);

        expect($stats['conditions'])->toBe(2);
        expect($stats['medications'])->toBe(1);
        expect($stats['procedures'])->toBe(0);
        expect($stats['measurements'])->toBe(0);
        expect($stats['observations'])->toBe(0);
        expect($stats['visits'])->toBe(0);
        expect($stats['notes'])->toBe(0);
        expect($stats['imaging_studies'])->toBe(0);
        expect($stats['genomic_variants'])->toBe(1);
    });
});

// ─── createPatient ───────────────────────────────────────────────────

describe('PatientService::createPatient', function () {
    it('creates a ClinicalPatient record in the database', function () {
        $patient = $this->patientService->createPatient([
            'mrn' => 'MRN-NEW-001',
            'first_name' => 'Created',
            'last_name' => 'Patient',
            'date_of_birth' => '2000-01-01',
            'sex' => 'Male',
        ]);

        expect($patient)->toBeInstanceOf(ClinicalPatient::class);
        expect($patient->exists)->toBeTrue();

        $found = ClinicalPatient::where('mrn', 'MRN-NEW-001')->first();
        expect($found)->not->toBeNull();
        expect($found->first_name)->toBe('Created');
    });

    it('returns a ClinicalPatient model instance', function () {
        $patient = $this->patientService->createPatient([
            'mrn' => 'MRN-NEW-002',
            'first_name' => 'Another',
            'last_name' => 'Patient',
        ]);

        expect($patient)->toBeInstanceOf(ClinicalPatient::class);
        expect($patient->id)->not->toBeNull();
    });
});

// ─── getProfile ──────────────────────────────────────────────────────

describe('PatientService::getProfile', function () {
    it('delegates to adapter and returns profile array with all domains', function () {
        // Seed some clinical data
        Condition::create([
            'patient_id' => $this->patient->id,
            'concept_name' => 'Melanoma',
            'status' => 'active',
        ]);

        $profile = $this->patientService->getProfile((string) $this->patient->id);

        expect($profile)->toBeArray()->toHaveKeys([
            'patient', 'conditions', 'medications', 'procedures',
            'measurements', 'observations', 'visits', 'notes',
            'imaging', 'genomics',
        ]);
        expect($profile['patient']['mrn'])->toBe('MRN-PATSVC-01');
        expect($profile['conditions'])->toHaveCount(1);
    });

    it('returns empty array for non-existent patient', function () {
        $profile = $this->patientService->getProfile('99999');

        expect($profile)->toBeArray()->toBeEmpty();
    });
});
