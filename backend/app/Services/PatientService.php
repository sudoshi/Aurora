<?php

namespace App\Services;

use App\Contracts\ClinicalDataAdapter;
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

class PatientService
{
    private ClinicalDataAdapter $adapter;

    public function __construct(?ClinicalDataAdapter $adapter = null)
    {
        $this->adapter = $adapter ?? new ManualAdapter;
    }

    /**
     * Get a full patient profile via the adapter.
     */
    public function getProfile(string $patientId): array
    {
        return $this->adapter->getFullProfile($patientId);
    }

    /**
     * Search patients via the adapter.
     */
    public function searchPatients(string $query, int $limit = 20): array
    {
        return $this->adapter->searchPatients($query, $limit);
    }

    /**
     * Create a new patient via manual entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPatient(array $data): ClinicalPatient
    {
        return ClinicalPatient::create($data);
    }

    /**
     * Get aggregate counts per clinical domain for a patient.
     */
    public function getStats(string $patientId): array
    {
        return [
            'conditions' => Condition::where('patient_id', $patientId)->count(),
            'medications' => Medication::where('patient_id', $patientId)->count(),
            'procedures' => Procedure::where('patient_id', $patientId)->count(),
            'measurements' => Measurement::where('patient_id', $patientId)->count(),
            'observations' => Observation::where('patient_id', $patientId)->count(),
            'visits' => Visit::where('patient_id', $patientId)->count(),
            'notes' => ClinicalNote::where('patient_id', $patientId)->count(),
            'imaging_studies' => ImagingStudy::where('patient_id', $patientId)->count(),
            'genomic_variants' => GenomicVariant::where('patient_id', $patientId)->count(),
        ];
    }
}
