<?php

namespace App\Services\Adapters;

use App\Contracts\ClinicalDataAdapter;

class OmopAdapter implements ClinicalDataAdapter
{
    public function getPatient(string $patientId): ?array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getConditions(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getMedications(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getProcedures(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getMeasurements(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getObservations(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getVisits(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getNotes(string $patientId, int $page = 1, int $perPage = 50): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getImaging(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getGenomics(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function getFullProfile(string $patientId): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }

    public function searchPatients(string $query, int $limit = 20): array
    {
        throw new \RuntimeException('OMOP adapter not yet implemented');
    }
}
