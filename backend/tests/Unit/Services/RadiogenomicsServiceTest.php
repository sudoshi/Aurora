<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\GenomicVariant;
use App\Services\RadiogenomicsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RadiogenomicsService;
});

// --- getPatientPanel ------------------------------------------------------

describe('RadiogenomicsService::getPatientPanel', function () {
    it('returns empty array for non-existent patient', function () {
        $result = $this->service->getPatientPanel(99999);

        expect($result)->toBe([]);
    });

    it('returns demographics for existing patient', function () {
        $patient = ClinicalPatient::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $result = $this->service->getPatientPanel($patient->id);

        expect($result)->toHaveKey('demographics');
        expect($result['demographics']['first_name'])->toBe('Jane');
        expect($result['demographics']['last_name'])->toBe('Doe');
        expect($result['patient_id'])->toBe($patient->id);
    });

    it('classifies pathogenic variants as actionable and VUS as vus', function () {
        $patient = ClinicalPatient::factory()->create();

        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'BRAF',
            'clinical_significance' => 'pathogenic',
        ]);
        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'TP53',
            'clinical_significance' => 'VUS',
        ]);
        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'EGFR',
            'clinical_significance' => 'likely_pathogenic',
        ]);

        $result = $this->service->getPatientPanel($patient->id);

        // pathogenic + likely_pathogenic = actionable
        expect($result['variants']['pathogenic_count'])->toBe(2);
        expect($result['variants']['vus_count'])->toBe(1);
        expect($result['variants']['total'])->toBe(3);

        // actionable map contains BRAF and EGFR
        expect(array_values($result['variants']['actionable']))->toContain('BRAF', 'EGFR');
        // vus map contains TP53
        expect(array_values($result['variants']['vus']))->toContain('TP53');
    });

    it('counts pathogenic_count and vus_count correctly with mixed variants', function () {
        $patient = ClinicalPatient::factory()->create();

        GenomicVariant::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'clinical_significance' => 'pathogenic',
        ]);
        GenomicVariant::factory()->count(2)->create([
            'patient_id' => $patient->id,
            'clinical_significance' => 'VUS',
        ]);
        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'clinical_significance' => 'benign',
        ]);

        $result = $this->service->getPatientPanel($patient->id);

        expect($result['variants']['pathogenic_count'])->toBe(3);
        expect($result['variants']['vus_count'])->toBe(2);
        expect($result['variants']['total'])->toBe(6);
    });

    it('builds correlations when GeneDrugInteraction records match variant genes', function () {
        $patient = ClinicalPatient::factory()->create();

        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'BRAF',
            'variant' => 'V600E',
            'clinical_significance' => 'pathogenic',
        ]);

        GeneDrugInteraction::factory()->create([
            'gene' => 'BRAF',
            'drug' => 'Vemurafenib',
            'relationship' => 'sensitive',
            'evidence_level' => '1',
        ]);

        $result = $this->service->getPatientPanel($patient->id);

        expect($result['correlations'])->not->toBeEmpty();
        expect($result['correlations'][0]['gene_symbol'])->toBe('BRAF');
        expect($result['correlations'][0]['drug_name'])->toBe('Vemurafenib');
        expect($result['correlations'][0]['relationship'])->toBe('sensitive');
    });

    it('builds recommendations for pathogenic variants with known interactions', function () {
        $patient = ClinicalPatient::factory()->create();

        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'BRAF',
            'variant' => 'V600E',
            'clinical_significance' => 'pathogenic',
        ]);

        GeneDrugInteraction::factory()->create([
            'gene' => 'BRAF',
            'drug' => 'Vemurafenib',
            'relationship' => 'sensitive',
            'evidence_level' => '1',
        ]);

        $result = $this->service->getPatientPanel($patient->id);

        expect($result['recommendations'])->not->toBeEmpty();
        expect($result['recommendations'][0]['gene'])->toBe('BRAF');
        expect($result['recommendations'][0]['drugs_consider'])->toContain('Vemurafenib');
        expect($result['recommendations'][0]['recommendation_type'])->toBe('consider');
    });

    it('returns empty correlations when no interactions exist', function () {
        $patient = ClinicalPatient::factory()->create();

        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'BRAF',
            'clinical_significance' => 'pathogenic',
        ]);

        $result = $this->service->getPatientPanel($patient->id);

        expect($result['correlations'])->toBe([]);
        expect($result['recommendations'])->toBe([]);
    });

    it('returns empty drug_exposures when no drug_eras exist', function () {
        $patient = ClinicalPatient::factory()->create();

        $result = $this->service->getPatientPanel($patient->id);

        expect($result['drug_exposures'])->toBe([]);
    });
});
