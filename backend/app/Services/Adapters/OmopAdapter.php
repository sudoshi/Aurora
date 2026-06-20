<?php

namespace App\Services\Adapters;

use DateTimeInterface;

class OmopAdapter extends LocalStandardsAdapter
{
    private const TABLE_BY_DOMAIN = [
        'patient' => 'person',
        'conditions' => 'condition_occurrence',
        'medications' => 'drug_exposure',
        'procedures' => 'procedure_occurrence',
        'measurements' => 'measurement',
        'observations' => 'observation',
        'visits' => 'visit_occurrence',
        'notes' => 'note',
        'imaging' => 'observation',
        'genomics' => 'observation',
    ];

    protected function decorateRecord(string $domain, array $record): array
    {
        return array_merge($record, [
            'adapter' => 'omop',
            'standard' => 'OMOP CDM v5.4',
            'omop_table' => self::TABLE_BY_DOMAIN[$domain] ?? 'observation',
            'omop' => $this->buildCdmRecord($domain, $record),
        ]);
    }

    private function buildCdmRecord(string $domain, array $record): array
    {
        return match ($domain) {
            'patient' => $this->personRecord($record),
            'conditions' => $this->conditionOccurrenceRecord($record),
            'medications' => $this->drugExposureRecord($record),
            'procedures' => $this->procedureOccurrenceRecord($record),
            'measurements' => $this->measurementRecord($record),
            'observations' => $this->observationRecord($record),
            'visits' => $this->visitOccurrenceRecord($record),
            'notes' => $this->noteRecord($record),
            'imaging' => $this->imagingObservationRecord($record),
            'genomics' => $this->genomicsObservationRecord($record),
            default => $this->sourceRecord($record),
        };
    }

    private function personRecord(array $record): array
    {
        $birthDate = $this->formatDate($this->firstValue($record, ['date_of_birth']));

        return $this->withoutEmpty([
            'person_id' => $this->intValue($record, 'id'),
            'person_source_value' => $this->stringValue($record, 'mrn'),
            'gender_source_value' => $this->stringValue($record, 'sex'),
            'race_source_value' => $this->stringValue($record, 'race'),
            'ethnicity_source_value' => $this->stringValue($record, 'ethnicity'),
            'year_of_birth' => $birthDate ? (int) substr($birthDate, 0, 4) : null,
            'month_of_birth' => $birthDate ? (int) substr($birthDate, 5, 2) : null,
            'day_of_birth' => $birthDate ? (int) substr($birthDate, 8, 2) : null,
            'birth_datetime' => $birthDate,
            'source_id' => $this->stringValue($record, 'source_id'),
            'source_type' => $this->stringValue($record, 'source_type'),
        ]);
    }

    private function conditionOccurrenceRecord(array $record): array
    {
        return $this->withoutEmpty([
            'condition_occurrence_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'condition_source_value' => $this->sourceValue($record, 'concept_code', 'concept_name'),
            'condition_source_concept_name' => $this->stringValue($record, 'concept_name'),
            'condition_start_date' => $this->formatDate($this->firstValue($record, ['onset_date', 'start_date'])),
            'condition_end_date' => $this->formatDate($this->firstValue($record, ['resolution_date', 'end_date'])),
            'condition_status_source_value' => $this->stringValue($record, 'status', 'type_name'),
            'condition_type_source_value' => $this->stringValue($record, 'source_type'),
            'source_vocabulary' => $this->stringValue($record, 'vocabulary'),
            'aurora_domain' => $this->stringValue($record, 'domain', 'aurora_domain'),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function drugExposureRecord(array $record): array
    {
        return $this->withoutEmpty([
            'drug_exposure_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'drug_source_value' => $this->sourceValue($record, 'concept_code', 'drug_name', 'concept_name'),
            'drug_source_concept_name' => $this->stringValue($record, 'drug_name', 'concept_name'),
            'drug_exposure_start_date' => $this->formatDate($this->firstValue($record, ['start_date', 'created_at'])),
            'drug_exposure_end_date' => $this->formatDate($this->firstValue($record, ['end_date'])),
            'route_source_value' => $this->stringValue($record, 'route'),
            'dose_unit_source_value' => $this->stringValue($record, 'dose_unit'),
            'quantity' => $this->floatValue($record, 'dose_value'),
            'sig' => $this->stringValue($record, 'frequency'),
            'drug_type_source_value' => $this->stringValue($record, 'status', 'type_name'),
            'source_vocabulary' => $this->stringValue($record, 'vocabulary'),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function procedureOccurrenceRecord(array $record): array
    {
        return $this->withoutEmpty([
            'procedure_occurrence_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'procedure_source_value' => $this->sourceValue($record, 'concept_code', 'procedure_name', 'concept_name'),
            'procedure_source_concept_name' => $this->stringValue($record, 'procedure_name', 'concept_name'),
            'procedure_date' => $this->formatDate($this->firstValue($record, ['performed_date', 'start_date'])),
            'procedure_type_source_value' => $this->stringValue($record, 'status', 'type_name'),
            'source_vocabulary' => $this->stringValue($record, 'vocabulary'),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function measurementRecord(array $record): array
    {
        return $this->withoutEmpty([
            'measurement_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'measurement_source_value' => $this->sourceValue($record, 'concept_code', 'measurement_name', 'concept_name'),
            'measurement_source_concept_name' => $this->stringValue($record, 'measurement_name', 'concept_name'),
            'measurement_date' => $this->formatDate($this->firstValue($record, ['measured_at', 'start_date'])),
            'measurement_datetime' => $this->formatDateTime($this->firstValue($record, ['measured_at', 'start_date'])),
            'value_as_number' => $this->floatValue($record, 'value_numeric'),
            'value_as_string' => $this->stringValue($record, 'value_text', 'value_as_string'),
            'unit_source_value' => $this->stringValue($record, 'unit'),
            'range_low' => $this->floatValue($record, 'reference_range_low'),
            'range_high' => $this->floatValue($record, 'reference_range_high'),
            'operator_source_value' => $this->stringValue($record, 'abnormal_flag'),
            'source_vocabulary' => $this->stringValue($record, 'vocabulary'),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function observationRecord(array $record): array
    {
        return $this->withoutEmpty([
            'observation_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'observation_source_value' => $this->sourceValue($record, 'concept_code', 'observation_name', 'concept_name'),
            'observation_source_concept_name' => $this->stringValue($record, 'observation_name', 'concept_name'),
            'observation_date' => $this->formatDate($this->firstValue($record, ['observed_at', 'start_date'])),
            'observation_datetime' => $this->formatDateTime($this->firstValue($record, ['observed_at', 'start_date'])),
            'value_as_number' => $this->floatValue($record, 'value_numeric'),
            'value_as_string' => $this->stringValue($record, 'value_text', 'value_as_string'),
            'unit_source_value' => $this->stringValue($record, 'unit'),
            'source_vocabulary' => $this->stringValue($record, 'vocabulary'),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function visitOccurrenceRecord(array $record): array
    {
        return $this->withoutEmpty([
            'visit_occurrence_id' => $this->intValue($record, 'id', 'visit_id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'visit_source_value' => $this->stringValue($record, 'visit_type', 'type_name', 'concept_name'),
            'care_site_source_value' => $this->stringValue($record, 'facility'),
            'visit_start_date' => $this->formatDate($this->firstValue($record, ['admission_date', 'start_date'])),
            'visit_start_datetime' => $this->formatDateTime($this->firstValue($record, ['admission_date', 'start_date'])),
            'visit_end_date' => $this->formatDate($this->firstValue($record, ['discharge_date', 'end_date'])),
            'visit_end_datetime' => $this->formatDateTime($this->firstValue($record, ['discharge_date', 'end_date'])),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function noteRecord(array $record): array
    {
        return $this->withoutEmpty([
            'note_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'visit_occurrence_id' => $this->intValue($record, 'visit_id'),
            'note_date' => $this->formatDate($this->firstValue($record, ['authored_at'])),
            'note_datetime' => $this->formatDateTime($this->firstValue($record, ['authored_at'])),
            'note_type_source_value' => $this->stringValue($record, 'note_type'),
            'note_title' => $this->stringValue($record, 'title', 'note_type'),
            'note_text' => $this->stringValue($record, 'content'),
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function imagingObservationRecord(array $record): array
    {
        return $this->withoutEmpty([
            'observation_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'observation_source_value' => $this->sourceValue($record, 'study_uid', 'description', 'modality'),
            'observation_source_concept_name' => $this->stringValue($record, 'description', 'modality'),
            'observation_date' => $this->formatDate($this->firstValue($record, ['study_date'])),
            'value_as_string' => $this->stringValue($record, 'modality'),
            'qualifier_source_value' => 'imaging_study',
            'source_id' => $this->stringValue($record, 'source_id', 'study_uid'),
        ]);
    }

    private function genomicsObservationRecord(array $record): array
    {
        return $this->withoutEmpty([
            'observation_id' => $this->intValue($record, 'id'),
            'person_id' => $this->intValue($record, 'patient_id'),
            'observation_source_value' => $this->sourceValue($record, 'variant', 'gene', 'gene_symbol'),
            'observation_source_concept_name' => trim(sprintf(
                '%s %s',
                $this->stringValue($record, 'gene', 'gene_symbol') ?? '',
                $this->stringValue($record, 'variant') ?? ''
            )) ?: null,
            'observation_date' => $this->formatDate($this->firstValue($record, ['created_at'])),
            'value_as_number' => $this->floatValue($record, 'allele_frequency'),
            'value_as_string' => $this->stringValue($record, 'clinical_significance', 'variant_type'),
            'qualifier_source_value' => 'genomic_variant',
            'source_id' => $this->stringValue($record, 'source_id'),
        ]);
    }

    private function sourceRecord(array $record): array
    {
        return $this->withoutEmpty([
            'source_id' => $this->stringValue($record, 'source_id', 'id'),
            'source_type' => $this->stringValue($record, 'source_type'),
            'person_id' => $this->intValue($record, 'patient_id'),
        ]);
    }

    private function sourceValue(array $record, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if ($value = $this->stringValue($record, $key)) {
                return $value;
            }
        }

        return null;
    }

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

    private function intValue(array $record, string ...$keys): ?int
    {
        $value = $this->firstValue($record, $keys);

        return is_numeric($value) ? (int) $value : null;
    }

    private function floatValue(array $record, string ...$keys): ?float
    {
        $value = $this->firstValue($record, $keys);

        return is_numeric($value) ? (float) $value : null;
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
