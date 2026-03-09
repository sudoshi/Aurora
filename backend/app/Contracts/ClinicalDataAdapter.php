<?php

namespace App\Contracts;

interface ClinicalDataAdapter
{
    public function getPatient(string $patientId): ?array;

    public function getConditions(string $patientId): array;

    public function getMedications(string $patientId): array;

    public function getProcedures(string $patientId): array;

    public function getMeasurements(string $patientId): array;

    public function getObservations(string $patientId): array;

    public function getVisits(string $patientId): array;

    public function getNotes(string $patientId, int $page = 1, int $perPage = 50): array;

    public function getImaging(string $patientId): array;

    public function getGenomics(string $patientId): array;

    public function getFullProfile(string $patientId): array;

    public function searchPatients(string $query, int $limit = 20): array;
}
