<?php

namespace App\Services\Adapters;

use App\Contracts\ClinicalDataAdapter;
use Illuminate\Contracts\Support\Arrayable;

abstract class LocalStandardsAdapter implements ClinicalDataAdapter
{
    public function __construct(
        protected ?ManualAdapter $manualAdapter = null,
    ) {
        $this->manualAdapter ??= new ManualAdapter;
    }

    public function getPatient(string $patientId): ?array
    {
        $patient = $this->manualAdapter->getPatient($patientId);

        return $patient ? $this->decoratePatient($patient) : null;
    }

    public function getConditions(string $patientId): array
    {
        return $this->decorateCollection('conditions', $this->manualAdapter->getConditions($patientId));
    }

    public function getMedications(string $patientId): array
    {
        return $this->decorateCollection('medications', $this->manualAdapter->getMedications($patientId));
    }

    public function getProcedures(string $patientId): array
    {
        return $this->decorateCollection('procedures', $this->manualAdapter->getProcedures($patientId));
    }

    public function getMeasurements(string $patientId): array
    {
        return $this->decorateCollection('measurements', $this->manualAdapter->getMeasurements($patientId));
    }

    public function getObservations(string $patientId): array
    {
        return $this->decorateCollection('observations', $this->manualAdapter->getObservations($patientId));
    }

    public function getVisits(string $patientId): array
    {
        return $this->decorateCollection('visits', $this->manualAdapter->getVisits($patientId));
    }

    public function getNotes(string $patientId, int $page = 1, int $perPage = 50): array
    {
        return $this->decorateNotes($this->manualAdapter->getNotes($patientId, $page, $perPage));
    }

    public function getImaging(string $patientId): array
    {
        return $this->decorateCollection('imaging', $this->manualAdapter->getImaging($patientId));
    }

    public function getGenomics(string $patientId): array
    {
        return $this->decorateCollection('genomics', $this->manualAdapter->getGenomics($patientId));
    }

    public function getFullProfile(string $patientId): array
    {
        $profile = $this->manualAdapter->getFullProfile($patientId);

        if ($profile === []) {
            return [];
        }

        $profile['patient'] = $this->decoratePatient($profile['patient']);

        foreach ([
            'conditions',
            'medications',
            'procedures',
            'measurements',
            'observations',
            'visits',
            'imaging',
            'genomics',
        ] as $domain) {
            $profile[$domain] = $this->decorateCollection($domain, $profile[$domain] ?? []);
        }

        $profile['notes'] = $this->decorateNotes($profile['notes'] ?? []);

        return $profile;
    }

    public function searchPatients(string $query, int $limit = 20): array
    {
        return $this->decorateCollection('patient', $this->manualAdapter->searchPatients($query, $limit));
    }

    /**
     * @param  array<int, mixed>  $records
     * @return array<int, array<string, mixed>>
     */
    protected function decorateCollection(string $domain, array $records): array
    {
        return array_map(
            fn (mixed $record): array => $this->decorateRecord($domain, $this->recordToArray($record)),
            $records
        );
    }

    /**
     * @param  array<string, mixed>  $notes
     * @return array<string, mixed>
     */
    protected function decorateNotes(array $notes): array
    {
        if (! array_key_exists('data', $notes) || ! is_array($notes['data'])) {
            return $notes;
        }

        $notes['data'] = $this->decorateCollection('notes', $notes['data']);

        return $notes;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    protected function decoratePatient(array $record): array
    {
        return $this->decorateRecord('patient', $record);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    abstract protected function decorateRecord(string $domain, array $record): array;

    /**
     * @return array<string, mixed>
     */
    private function recordToArray(mixed $record): array
    {
        if (is_array($record)) {
            return $record;
        }

        if ($record instanceof Arrayable) {
            return $record->toArray();
        }

        return (array) $record;
    }
}
