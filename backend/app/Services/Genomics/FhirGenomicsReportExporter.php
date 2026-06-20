<?php

namespace App\Services\Genomics;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicVariant;
use DateTimeInterface;

class FhirGenomicsReportExporter
{
    public const GENOMIC_REPORT_PROFILE = 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/genomic-report';

    public const VARIANT_PROFILE = 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/variant';

    private const GENETICS_CATEGORY = [
        'system' => 'http://terminology.hl7.org/CodeSystem/v2-0074',
        'code' => 'GE',
        'display' => 'Genetics',
    ];

    private const GENOMIC_REPORT_LOINC = [
        'system' => 'http://loinc.org',
        'code' => '51969-4',
        'display' => 'Genetic analysis report',
    ];

    /**
     * @return array<string, mixed>
     */
    public function exportForPatient(ClinicalPatient $patient): array
    {
        $patient->loadMissing('identifiers');

        $variants = GenomicVariant::query()
            ->where('patient_id', $patient->id)
            ->orderBy('gene')
            ->orderBy('id')
            ->get();

        $timestamp = now()->toIso8601String();
        $reportId = "aurora-genomics-report-{$patient->id}";
        $variantReferences = $variants
            ->map(fn (GenomicVariant $variant): array => [
                'reference' => "Observation/aurora-genomic-variant-{$variant->id}",
                'display' => $this->variantDisplay($variant),
            ])
            ->values()
            ->all();

        $entries = [
            $this->entry('Patient', (string) $patient->id, $this->patientResource($patient)),
            $this->entry('DiagnosticReport', $reportId, $this->diagnosticReportResource(
                $patient,
                $reportId,
                $variantReferences,
                $variants->count(),
                $timestamp,
            )),
        ];

        foreach ($variants as $variant) {
            $entries[] = $this->entry(
                'Observation',
                "aurora-genomic-variant-{$variant->id}",
                $this->variantObservationResource($patient, $variant),
            );
        }

        return [
            'resourceType' => 'Bundle',
            'id' => "aurora-fhir-genomics-report-{$patient->id}",
            'type' => 'collection',
            'timestamp' => $timestamp,
            'entry' => $entries,
        ];
    }

    public function variantCountForPatient(ClinicalPatient $patient): int
    {
        return GenomicVariant::where('patient_id', $patient->id)->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function patientResource(ClinicalPatient $patient): array
    {
        $identifiers = [];

        if ($patient->mrn) {
            $identifiers[] = [
                'system' => 'urn:aurora:mrn',
                'value' => $patient->mrn,
            ];
        }

        foreach ($patient->identifiers as $identifier) {
            $identifiers[] = $this->withoutEmpty([
                'system' => $identifier->source_system ?: $identifier->identifier_type,
                'type' => ['text' => $identifier->identifier_type],
                'value' => $identifier->identifier_value,
            ]);
        }

        return $this->withoutEmpty([
            'resourceType' => 'Patient',
            'id' => (string) $patient->id,
            'identifier' => $identifiers,
            'name' => [[
                'family' => $patient->last_name,
                'given' => array_values(array_filter([$patient->first_name])),
            ]],
            'gender' => $this->fhirGender($patient->sex),
            'birthDate' => $this->formatDate($patient->date_of_birth),
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $variantReferences
     * @return array<string, mixed>
     */
    private function diagnosticReportResource(
        ClinicalPatient $patient,
        string $reportId,
        array $variantReferences,
        int $variantCount,
        string $timestamp,
    ): array {
        return $this->withoutEmpty([
            'resourceType' => 'DiagnosticReport',
            'id' => $reportId,
            'meta' => [
                'profile' => [self::GENOMIC_REPORT_PROFILE],
            ],
            'status' => 'final',
            'category' => [[
                'coding' => [self::GENETICS_CATEGORY],
                'text' => 'Genetics',
            ]],
            'code' => [
                'coding' => [self::GENOMIC_REPORT_LOINC],
                'text' => 'Aurora genomic variants report',
            ],
            'subject' => $this->patientReference($patient),
            'effectiveDateTime' => $timestamp,
            'issued' => $timestamp,
            'result' => $variantReferences,
            'conclusion' => sprintf(
                'Aurora exported %d locally stored genomic variant%s.',
                $variantCount,
                $variantCount === 1 ? '' : 's',
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function variantObservationResource(ClinicalPatient $patient, GenomicVariant $variant): array
    {
        return $this->withoutEmpty([
            'resourceType' => 'Observation',
            'id' => "aurora-genomic-variant-{$variant->id}",
            'meta' => [
                'profile' => [self::VARIANT_PROFILE],
            ],
            'identifier' => $this->variantIdentifiers($variant),
            'status' => 'final',
            'category' => [[
                'coding' => [self::GENETICS_CATEGORY],
                'text' => 'Genetics',
            ]],
            'code' => [
                'text' => 'Genomic variant',
            ],
            'subject' => $this->patientReference($patient),
            'effectiveDateTime' => $this->formatDateTime($variant->created_at),
            'valueString' => $this->variantDisplay($variant),
            'component' => array_values(array_filter([
                $this->component('gene', 'Gene', $variant->gene),
                $this->component('variant', 'Variant', $variant->variant),
                $this->component('variant_type', 'Variant type', $variant->variant_type),
                $this->component('chromosome', 'Chromosome', $variant->chromosome),
                $this->component('position', 'Position', $variant->position, numeric: true),
                $this->component('reference_allele', 'Reference allele', $variant->ref_allele),
                $this->component('alternate_allele', 'Alternate allele', $variant->alt_allele),
                $this->component('zygosity', 'Zygosity', $variant->zygosity),
                $this->component('allele_frequency', 'Allele frequency', $variant->allele_frequency, numeric: true),
                $this->component('clinical_significance', 'Clinical significance', $variant->clinical_significance),
                $this->component('actionability', 'Actionability', $variant->actionability),
            ])),
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function variantIdentifiers(GenomicVariant $variant): array
    {
        $identifiers = [[
            'system' => 'urn:aurora:genomic-variant',
            'value' => (string) $variant->id,
        ]];

        if ($variant->source_id) {
            $identifiers[] = $this->withoutEmpty([
                'system' => $variant->source_type ? "urn:aurora:source:{$variant->source_type}" : 'urn:aurora:source',
                'value' => $variant->source_id,
            ]);
        }

        return $identifiers;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function component(string $code, string $display, mixed $value, bool $numeric = false): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->withoutEmpty([
            'code' => [
                'coding' => [[
                    'system' => $this->codeSystemUrl(),
                    'code' => $code,
                    'display' => $display,
                ]],
                'text' => $display,
            ],
            'valueString' => $numeric ? null : (string) $value,
            'valueQuantity' => $numeric ? ['value' => (float) $value] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(string $resourceType, string $id, array $resource): array
    {
        return [
            'fullUrl' => $this->resourceUrl($resourceType, $id),
            'resource' => $resource,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function patientReference(ClinicalPatient $patient): array
    {
        return [
            'reference' => "Patient/{$patient->id}",
            'display' => trim("{$patient->first_name} {$patient->last_name}") ?: $patient->mrn,
        ];
    }

    private function variantDisplay(GenomicVariant $variant): string
    {
        return trim(implode(' ', array_filter([
            $variant->gene,
            $variant->variant,
            $variant->clinical_significance ? "({$variant->clinical_significance})" : null,
        ]))) ?: "Variant {$variant->id}";
    }

    private function resourceUrl(string $resourceType, string $id): string
    {
        return sprintf('%s/fhir/%s/%s', $this->baseUrl(), $resourceType, $id);
    }

    private function codeSystemUrl(): string
    {
        return $this->baseUrl().'/fhir/CodeSystem/aurora-genomics-components';
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('app.url', 'http://localhost'), '/');
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
