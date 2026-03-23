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

        if (! $patient) {
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

        if (! $patient) {
            return [];
        }

        return [
            'patient' => $patient,
            'conditions' => $this->normalizeConditions($this->getConditions($patientId)),
            'medications' => $this->normalizeMedications($this->getMedications($patientId)),
            'procedures' => $this->normalizeProcedures($this->getProcedures($patientId)),
            'measurements' => $this->normalizeMeasurements($this->getMeasurements($patientId)),
            'observations' => $this->normalizeObservations($this->getObservations($patientId)),
            'visits' => $this->normalizeVisits($this->getVisits($patientId)),
            'notes' => $this->getNotes($patientId),
            'imaging' => $this->getImaging($patientId),
            'genomics' => $this->getGenomics($patientId),
            'condition_eras' => [],
            'drug_eras' => [],
            'observation_periods' => [],
        ];
    }

    // ── Normalization helpers ────────────────────────────────────────────

    private function normalizeConditions(array $rows): array
    {
        return array_map(fn (array $r) => [
            'id' => $r['id'],
            'domain' => 'condition',
            'concept_name' => $r['concept_name'],
            'concept_code' => $r['concept_code'] ?? null,
            'start_date' => $r['onset_date'],
            'end_date' => $r['resolution_date'] ?? null,
            'type_name' => $r['status'] ?? null,
            'aurora_domain' => $r['domain'] ?? null,
        ], $rows);
    }

    private function normalizeMedications(array $rows): array
    {
        return array_map(fn (array $r) => [
            'id' => $r['id'],
            'domain' => 'medication',
            'concept_name' => $r['drug_name'],
            'concept_code' => $r['concept_code'] ?? null,
            'start_date' => $r['start_date'] ?? $r['created_at'],
            'end_date' => $r['end_date'] ?? null,
            'drug_name' => $r['drug_name'],
            'route' => $r['route'] ?? null,
            'dose_value' => isset($r['dose_value']) ? (float) $r['dose_value'] : null,
            'dose_unit' => $r['dose_unit'] ?? null,
            'frequency' => $r['frequency'] ?? null,
            'type_name' => $r['status'] ?? null,
        ], $rows);
    }

    private function normalizeProcedures(array $rows): array
    {
        return array_map(fn (array $r) => [
            'id' => $r['id'],
            'domain' => 'procedure',
            'concept_name' => $r['procedure_name'],
            'concept_code' => $r['concept_code'] ?? null,
            'start_date' => $r['performed_date'],
            'end_date' => null,
            'type_name' => $r['status'] ?? null,
        ], $rows);
    }

    private function normalizeMeasurements(array $rows): array
    {
        return array_map(fn (array $r) => [
            'id' => $r['id'],
            'domain' => 'measurement',
            'concept_name' => $r['measurement_name'],
            'concept_code' => $r['concept_code'] ?? null,
            'start_date' => $r['measured_at'],
            'end_date' => null,
            'value_numeric' => isset($r['value_numeric']) ? (float) $r['value_numeric'] : null,
            'value_as_string' => $r['value_text'] ?? null,
            'unit' => $r['unit'] ?? null,
            'reference_range_low' => isset($r['reference_range_low']) ? (float) $r['reference_range_low'] : null,
            'reference_range_high' => isset($r['reference_range_high']) ? (float) $r['reference_range_high'] : null,
            'abnormal_flag' => $r['abnormal_flag'] ?? null,
        ], $rows);
    }

    private function normalizeObservations(array $rows): array
    {
        return array_map(fn (array $r) => [
            'id' => $r['id'],
            'domain' => 'observation',
            'concept_name' => $r['observation_name'],
            'concept_code' => $r['concept_code'] ?? null,
            'start_date' => $r['observed_at'],
            'end_date' => null,
            'value_as_string' => $r['value_text'] ?? null,
            'value_numeric' => isset($r['value_numeric']) ? (float) $r['value_numeric'] : null,
            'unit' => $r['unit'] ?? null,
        ], $rows);
    }

    private function normalizeVisits(array $rows): array
    {
        return array_map(fn (array $r) => [
            'id' => $r['id'],
            'domain' => 'visit',
            'concept_name' => $r['visit_type'] ?? 'Visit',
            'concept_code' => null,
            'start_date' => $r['admission_date'],
            'end_date' => $r['discharge_date'] ?? null,
            'type_name' => $r['visit_type'] ?? null,
            'visit_id' => $r['id'],
        ], $rows);
    }

    public function searchPatients(string $query, int $limit = 20): array
    {
        $searchTerm = "%{$query}%";

        return ClinicalPatient::where(function ($q) use ($searchTerm) {
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
