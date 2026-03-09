<?php

namespace App\Services\Adapters;

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

class ManualAdapter implements ClinicalDataAdapter
{
    public function getPatient(string $patientId): ?array
    {
        $patient = ClinicalPatient::with('identifiers')->find($patientId);

        if (!$patient) {
            return null;
        }

        return $patient->toArray();
    }

    public function getConditions(string $patientId): array
    {
        return Condition::where('patient_id', $patientId)
            ->orderByDesc('onset_date')
            ->get()
            ->toArray();
    }

    public function getMedications(string $patientId): array
    {
        return Medication::where('patient_id', $patientId)
            ->orderByDesc('start_date')
            ->get()
            ->toArray();
    }

    public function getProcedures(string $patientId): array
    {
        return Procedure::where('patient_id', $patientId)
            ->orderByDesc('performed_date')
            ->get()
            ->toArray();
    }

    public function getMeasurements(string $patientId): array
    {
        return Measurement::where('patient_id', $patientId)
            ->orderByDesc('measured_at')
            ->get()
            ->toArray();
    }

    public function getObservations(string $patientId): array
    {
        return Observation::where('patient_id', $patientId)
            ->orderByDesc('observed_at')
            ->get()
            ->toArray();
    }

    public function getVisits(string $patientId): array
    {
        return Visit::where('patient_id', $patientId)
            ->with('clinicalNotes')
            ->orderByDesc('admission_date')
            ->get()
            ->toArray();
    }

    public function getNotes(string $patientId, int $page = 1, int $perPage = 50): array
    {
        $paginator = ClinicalNote::where('patient_id', $patientId)
            ->orderByDesc('authored_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function getImaging(string $patientId): array
    {
        return ImagingStudy::where('patient_id', $patientId)
            ->with(['series', 'imagingMeasurements', 'segmentations'])
            ->orderByDesc('study_date')
            ->get()
            ->toArray();
    }

    public function getGenomics(string $patientId): array
    {
        return GenomicVariant::where('patient_id', $patientId)
            ->orderBy('gene')
            ->get()
            ->toArray();
    }

    public function getFullProfile(string $patientId): array
    {
        $patient = $this->getPatient($patientId);

        if (!$patient) {
            return [];
        }

        return [
            'patient' => $patient,
            'conditions' => $this->getConditions($patientId),
            'medications' => $this->getMedications($patientId),
            'procedures' => $this->getProcedures($patientId),
            'measurements' => $this->getMeasurements($patientId),
            'observations' => $this->getObservations($patientId),
            'visits' => $this->getVisits($patientId),
            'notes' => $this->getNotes($patientId),
            'imaging' => $this->getImaging($patientId),
            'genomics' => $this->getGenomics($patientId),
        ];
    }

    public function searchPatients(string $query, int $limit = 20): array
    {
        $searchTerm = "%{$query}%";

        return ClinicalPatient::where(function ($q) use ($searchTerm, $query) {
            $q->where('first_name', 'ilike', $searchTerm)
              ->orWhere('last_name', 'ilike', $searchTerm)
              ->orWhere('mrn', 'ilike', $searchTerm)
              ->orWhereHas('conditions', function ($sub) use ($searchTerm) {
                  $sub->where('concept_name', 'ilike', $searchTerm);
              });
        })
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
