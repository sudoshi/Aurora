<?php

namespace App\Services\Adapters;

use DateTimeInterface;

class FhirAdapter extends LocalStandardsAdapter
{
    private const RESOURCE_BY_DOMAIN = [
        'patient' => 'Patient',
        'conditions' => 'Condition',
        'medications' => 'MedicationStatement',
        'procedures' => 'Procedure',
        'measurements' => 'Observation',
        'observations' => 'Observation',
        'visits' => 'Encounter',
        'notes' => 'DocumentReference',
        'imaging' => 'ImagingStudy',
        'genomics' => 'Observation',
    ];

    protected function decorateRecord(string $domain, array $record): array
    {
        $resourceType = self::RESOURCE_BY_DOMAIN[$domain] ?? 'Basic';

        return array_merge($record, [
            'adapter' => 'fhir',
            'standard' => 'FHIR R4',
            'fhir_resource_type' => $resourceType,
            'fhir_id' => $this->resourceId($resourceType, $record),
            'subject_reference' => $domain === 'patient' ? null : $this->patientReference($record),
            'fhir' => $this->buildResource($domain, $resourceType, $record),
        ]);
    }

    private function buildResource(string $domain, string $resourceType, array $record): array
    {
        return match ($domain) {
            'patient' => $this->patientResource($record),
            'conditions' => $this->conditionResource($record),
            'medications' => $this->medicationStatementResource($record),
            'procedures' => $this->procedureResource($record),
            'measurements' => $this->observationResource($record, 'measurement'),
            'observations' => $this->observationResource($record, 'observation'),
            'visits' => $this->encounterResource($record),
            'notes' => $this->documentReferenceResource($record),
            'imaging' => $this->imagingStudyResource($record),
            'genomics' => $this->genomicObservationResource($record),
            default => $this->basicResource($resourceType, $record),
        };
    }

    private function patientResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'Patient',
            'id' => $this->stringValue($record, 'id'),
            'identifier' => $this->patientIdentifiers($record),
            'name' => [[
                'family' => $this->stringValue($record, 'last_name'),
                'given' => array_values(array_filter([
                    $this->stringValue($record, 'first_name'),
                ])),
            ]],
            'gender' => $this->fhirGender($this->stringValue($record, 'sex')),
            'birthDate' => $this->formatDate($this->firstValue($record, ['date_of_birth'])),
            'deceasedDateTime' => $this->formatDateTime($this->firstValue($record, ['deceased_at'])),
        ]);
    }

    private function conditionResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'Condition',
            'id' => $this->stringValue($record, 'id'),
            'clinicalStatus' => $this->codeableConcept($this->stringValue($record, 'status', 'type_name')),
            'code' => $this->codeableConcept(
                $this->stringValue($record, 'concept_name'),
                $this->coding($record, 'concept_code', 'concept_name', 'vocabulary')
            ),
            'subject' => ['reference' => $this->patientReference($record)],
            'onsetDateTime' => $this->formatDateTime($this->firstValue($record, ['onset_date', 'start_date'])),
            'abatementDateTime' => $this->formatDateTime($this->firstValue($record, ['resolution_date', 'end_date'])),
            'bodySite' => $this->stringValue($record, 'body_site')
                ? [$this->codeableConcept($this->stringValue($record, 'body_site'))]
                : null,
        ]);
    }

    private function medicationStatementResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'MedicationStatement',
            'id' => $this->stringValue($record, 'id'),
            'status' => $this->medicationStatus($this->stringValue($record, 'status', 'type_name')),
            'medicationCodeableConcept' => $this->codeableConcept(
                $this->stringValue($record, 'drug_name', 'concept_name'),
                $this->coding($record, 'concept_code', 'drug_name', 'vocabulary')
            ),
            'subject' => ['reference' => $this->patientReference($record)],
            'effectivePeriod' => $this->period(
                $this->firstValue($record, ['start_date']),
                $this->firstValue($record, ['end_date'])
            ),
            'dosage' => $this->dosage($record),
        ]);
    }

    private function procedureResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'Procedure',
            'id' => $this->stringValue($record, 'id'),
            'status' => $this->procedureStatus($this->stringValue($record, 'status', 'type_name')),
            'code' => $this->codeableConcept(
                $this->stringValue($record, 'procedure_name', 'concept_name'),
                $this->coding($record, 'concept_code', 'procedure_name', 'vocabulary')
            ),
            'subject' => ['reference' => $this->patientReference($record)],
            'performedDateTime' => $this->formatDateTime($this->firstValue($record, ['performed_date', 'start_date'])),
            'bodySite' => $this->stringValue($record, 'body_site')
                ? [$this->codeableConcept($this->stringValue($record, 'body_site'))]
                : null,
        ]);
    }

    private function observationResource(array $record, string $sourceDomain): array
    {
        $valueNumeric = $this->firstValue($record, ['value_numeric']);
        $valueText = $this->stringValue($record, 'value_text', 'value_as_string');

        return $this->withoutEmpty([
            'resourceType' => 'Observation',
            'id' => $this->stringValue($record, 'id'),
            'status' => 'final',
            'category' => [[
                'text' => $sourceDomain,
            ]],
            'code' => $this->codeableConcept(
                $this->stringValue($record, 'measurement_name', 'observation_name', 'concept_name'),
                $this->coding($record, 'concept_code', 'measurement_name', 'vocabulary')
            ),
            'subject' => ['reference' => $this->patientReference($record)],
            'effectiveDateTime' => $this->formatDateTime($this->firstValue($record, ['measured_at', 'observed_at', 'start_date'])),
            'valueQuantity' => $valueNumeric !== null ? $this->withoutEmpty([
                'value' => (float) $valueNumeric,
                'unit' => $this->stringValue($record, 'unit'),
            ]) : null,
            'valueString' => $valueNumeric === null ? $valueText : null,
            'interpretation' => $this->stringValue($record, 'abnormal_flag')
                ? [['text' => $this->stringValue($record, 'abnormal_flag')]]
                : null,
            'referenceRange' => $this->referenceRange($record),
        ]);
    }

    private function encounterResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'Encounter',
            'id' => $this->stringValue($record, 'id', 'visit_id'),
            'status' => $this->encounterStatus($this->firstValue($record, ['discharge_date', 'end_date'])),
            'class' => [
                'code' => $this->stringValue($record, 'visit_type', 'type_name', 'concept_name'),
                'display' => $this->stringValue($record, 'visit_type', 'type_name', 'concept_name'),
            ],
            'subject' => ['reference' => $this->patientReference($record)],
            'period' => $this->period(
                $this->firstValue($record, ['admission_date', 'start_date']),
                $this->firstValue($record, ['discharge_date', 'end_date'])
            ),
            'serviceProvider' => $this->stringValue($record, 'facility')
                ? ['display' => $this->stringValue($record, 'facility')]
                : null,
        ]);
    }

    private function documentReferenceResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'DocumentReference',
            'id' => $this->stringValue($record, 'id'),
            'status' => 'current',
            'type' => $this->codeableConcept($this->stringValue($record, 'note_type')),
            'subject' => ['reference' => $this->patientReference($record)],
            'date' => $this->formatDateTime($this->firstValue($record, ['authored_at'])),
            'description' => $this->stringValue($record, 'title', 'note_type'),
            'content' => [[
                'attachment' => [
                    'contentType' => 'text/plain',
                    'title' => $this->stringValue($record, 'title', 'note_type'),
                ],
            ]],
        ]);
    }

    private function imagingStudyResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'ImagingStudy',
            'id' => $this->stringValue($record, 'id'),
            'identifier' => $this->stringValue($record, 'study_uid')
                ? [[
                    'system' => 'urn:dicom:uid',
                    'value' => $this->stringValue($record, 'study_uid'),
                ]]
                : null,
            'status' => 'available',
            'subject' => ['reference' => $this->patientReference($record)],
            'started' => $this->formatDateTime($this->firstValue($record, ['study_date'])),
            'modality' => $this->stringValue($record, 'modality')
                ? [[
                    'code' => $this->stringValue($record, 'modality'),
                    'display' => $this->stringValue($record, 'modality'),
                ]]
                : null,
            'description' => $this->stringValue($record, 'description'),
            'numberOfSeries' => isset($record['series']) && is_array($record['series']) ? count($record['series']) : null,
        ]);
    }

    private function genomicObservationResource(array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'Observation',
            'id' => $this->stringValue($record, 'id'),
            'status' => 'final',
            'category' => [[
                'text' => 'genomics',
            ]],
            'code' => $this->codeableConcept(
                trim(sprintf(
                    '%s %s',
                    $this->stringValue($record, 'gene', 'gene_symbol') ?? '',
                    $this->stringValue($record, 'variant') ?? ''
                )) ?: 'Genomic variant'
            ),
            'subject' => ['reference' => $this->patientReference($record)],
            'effectiveDateTime' => $this->formatDateTime($this->firstValue($record, ['created_at'])),
            'component' => array_values(array_filter([
                $this->component('Gene', $this->stringValue($record, 'gene', 'gene_symbol')),
                $this->component('Variant', $this->stringValue($record, 'variant')),
                $this->component('Clinical significance', $this->stringValue($record, 'clinical_significance')),
                $this->component('Allele frequency', $this->firstValue($record, ['allele_frequency']), $this->stringValue($record, 'allele_frequency') !== null),
            ])),
        ]);
    }

    private function basicResource(string $resourceType, array $record): array
    {
        return $this->withoutEmpty([
            'resourceType' => $resourceType,
            'id' => $this->stringValue($record, 'id'),
        ]);
    }

    private function patientIdentifiers(array $record): array
    {
        $identifiers = [];

        if ($mrn = $this->stringValue($record, 'mrn')) {
            $identifiers[] = [
                'system' => 'urn:aurora:mrn',
                'value' => $mrn,
            ];
        }

        foreach (($record['identifiers'] ?? []) as $identifier) {
            if (! is_array($identifier) || empty($identifier['identifier_value'])) {
                continue;
            }

            $identifiers[] = $this->withoutEmpty([
                'system' => $identifier['source_system'] ?? $identifier['identifier_type'] ?? null,
                'type' => isset($identifier['identifier_type'])
                    ? ['text' => $identifier['identifier_type']]
                    : null,
                'value' => $identifier['identifier_value'],
            ]);
        }

        return $identifiers;
    }

    private function dosage(array $record): ?array
    {
        $parts = array_values(array_filter([
            $this->stringValue($record, 'dose_value'),
            $this->stringValue($record, 'dose_unit'),
            $this->stringValue($record, 'frequency'),
        ]));

        if ($parts === []) {
            return null;
        }

        return [[
            'text' => implode(' ', $parts),
            'route' => $this->stringValue($record, 'route')
                ? $this->codeableConcept($this->stringValue($record, 'route'))
                : null,
        ]];
    }

    private function referenceRange(array $record): ?array
    {
        $low = $this->firstValue($record, ['reference_range_low']);
        $high = $this->firstValue($record, ['reference_range_high']);

        if ($low === null && $high === null) {
            return null;
        }

        return [[
            'low' => $low !== null ? ['value' => (float) $low, 'unit' => $this->stringValue($record, 'unit')] : null,
            'high' => $high !== null ? ['value' => (float) $high, 'unit' => $this->stringValue($record, 'unit')] : null,
        ]];
    }

    private function component(string $label, mixed $value, bool $numeric = false): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->withoutEmpty([
            'code' => ['text' => $label],
            'valueString' => $numeric ? null : (string) $value,
            'valueQuantity' => $numeric ? ['value' => (float) $value] : null,
        ]);
    }

    private function codeableConcept(?string $text, ?array $coding = null): array
    {
        return $this->withoutEmpty([
            'coding' => $coding ? [$coding] : null,
            'text' => $text,
        ]);
    }

    private function coding(array $record, string $codeKey, string $displayKey, string $vocabularyKey): ?array
    {
        $code = $this->stringValue($record, $codeKey);
        $display = $this->stringValue($record, $displayKey);

        if (! $code && ! $display) {
            return null;
        }

        return $this->withoutEmpty([
            'system' => $this->codingSystem($this->stringValue($record, $vocabularyKey)),
            'code' => $code,
            'display' => $display,
        ]);
    }

    private function codingSystem(?string $vocabulary): ?string
    {
        return match (strtolower((string) $vocabulary)) {
            'icd10', 'icd-10', 'icd10cm', 'icd-10-cm' => 'http://hl7.org/fhir/sid/icd-10-cm',
            'snomed', 'snomedct', 'snomed-ct' => 'http://snomed.info/sct',
            'loinc' => 'http://loinc.org',
            'rxnorm' => 'http://www.nlm.nih.gov/research/umls/rxnorm',
            default => null,
        };
    }

    private function period(mixed $start, mixed $end): ?array
    {
        if ($start === null && $end === null) {
            return null;
        }

        return $this->withoutEmpty([
            'start' => $this->formatDateTime($start),
            'end' => $this->formatDateTime($end),
        ]);
    }

    private function resourceId(string $resourceType, array $record): ?string
    {
        $id = $this->stringValue($record, 'id');

        return $id ? "{$resourceType}/{$id}" : null;
    }

    private function patientReference(array $record): ?string
    {
        $patientId = $this->stringValue($record, 'patient_id');

        return $patientId ? "Patient/{$patientId}" : null;
    }

    private function fhirGender(?string $sex): ?string
    {
        return match (strtolower((string) $sex)) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            'other', 'o' => 'other',
            'unknown', 'unk', 'u' => 'unknown',
            default => null,
        };
    }

    private function medicationStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'active', 'current' => 'active',
            'completed', 'resolved', 'finished' => 'completed',
            'stopped', 'cancelled', 'canceled', 'inactive' => 'stopped',
            default => 'unknown',
        };
    }

    private function procedureStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'completed', 'resolved', 'done' => 'completed',
            'active', 'in-progress', 'in_progress' => 'in-progress',
            'stopped', 'cancelled', 'canceled' => 'stopped',
            default => 'unknown',
        };
    }

    private function encounterStatus(mixed $dischargeDate): string
    {
        return $dischargeDate ? 'finished' : 'in-progress';
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function firstValue(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $record) && $record[$key] !== null && $record[$key] !== '') {
                return $record[$key];
            }
        }

        return null;
    }

    private function stringValue(array $record, string ...$keys): ?string
    {
        $value = $this->firstValue($record, $keys);

        return $value === null ? null : (string) $value;
    }

    private function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }

    private function formatDateTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return str_replace(' ', 'T', (string) $value);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function withoutEmpty(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->withoutEmpty($item);
            }

            if ($item === null || $item === '' || $item === []) {
                unset($value[$key]);

                continue;
            }

            $value[$key] = $item;
        }

        return $value;
    }
}
