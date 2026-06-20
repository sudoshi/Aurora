<?php

namespace App\Services\Genomics;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicUpload;
use App\Models\Clinical\GenomicUploadVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\PatientIdentifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenomicUploadIngestionService
{
    private const BATCH_SIZE = 500;

    private const IDENTIFIER_TYPES = [
        'genomic_sample',
        'sample_id',
        'tumor_sample_barcode',
        'normal_sample_barcode',
        'mrn',
        'external_id',
        'source_id',
    ];

    /**
     * Parse and match an uploaded file. Queue jobs call this method; it catches
     * deterministic parser failures so the upload record reflects a failed state.
     *
     * @return array<string, mixed>
     */
    public function processUpload(GenomicUpload $upload): array
    {
        try {
            $parse = $this->parseUpload($upload);

            if (($parse['parsed'] ?? 0) === 0) {
                return ['parse' => $parse, 'match' => null];
            }

            $match = $this->matchPersons($upload->refresh());

            return ['parse' => $parse, 'match' => $match];
        } catch (Throwable $e) {
            $upload->refresh()->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'last_result' => [
                    'operation' => 'parse_upload',
                    'status' => 'failed',
                    'errors' => [$e->getMessage()],
                ],
            ]);

            return [
                'parse' => [
                    'parsed' => 0,
                    'skipped' => 0,
                    'duplicates' => 0,
                    'errors' => [$e->getMessage()],
                ],
                'match' => null,
            ];
        }
    }

    /**
     * @return array{parsed: int, skipped: int, duplicates: int, errors: array<int, string>}
     */
    public function parseUpload(GenomicUpload $upload): array
    {
        $upload->update([
            'status' => 'parsing',
            'total_variants' => 0,
            'mapped_variants' => 0,
            'unmapped_variants' => 0,
            'parsed_at' => null,
            'matched_at' => null,
            'imported_at' => null,
            'clinvar_annotated_at' => null,
            'error_message' => null,
            'last_result' => [
                'operation' => 'parse_upload',
                'status' => 'running',
            ],
        ]);

        if (! Storage::disk('local')->exists($upload->stored_path)) {
            throw new RuntimeException('Uploaded genomic file is missing from storage.');
        }

        $format = $this->normalizeFormat($upload->file_format);

        if ($format === 'fhir_genomics') {
            throw new RuntimeException('FHIR Genomics upload parsing is not implemented yet.');
        }

        $path = Storage::disk('local')->path($upload->stored_path);

        GenomicUploadVariant::where('genomic_upload_id', $upload->id)->delete();

        $result = [
            'parsed' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];
        $seen = [];
        $batch = [];

        $rows = $format === 'vcf'
            ? $this->parseVcfRows($upload, $path, $result)
            : $this->parseDelimitedRows($upload, $path, $result);

        foreach ($rows as $row) {
            $key = $row['variant_key'];
            if (isset($seen[$key])) {
                $result['duplicates']++;

                continue;
            }
            $seen[$key] = true;
            $batch[] = $row;
            $result['parsed']++;

            if (count($batch) >= self::BATCH_SIZE) {
                $this->flushBatch($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->flushBatch($batch);
        }

        $status = $result['parsed'] > 0 ? 'review' : 'failed';
        $errorMessage = $result['parsed'] > 0 ? null : 'No importable variants were parsed from the upload.';

        $upload->refresh()->update([
            'status' => $status,
            'total_variants' => $result['parsed'],
            'mapped_variants' => 0,
            'unmapped_variants' => $result['parsed'],
            'parsed_at' => now(),
            'error_message' => $errorMessage,
            'last_result' => [
                'operation' => 'parse_upload',
                'status' => $status === 'failed' ? 'failed' : 'succeeded',
                'result' => $result,
            ],
        ]);

        return $result;
    }

    /**
     * @return array{candidates: int, matched: int, unmatched: int, review_required: int}
     */
    public function matchPersons(GenomicUpload $upload): array
    {
        $query = GenomicUploadVariant::where('genomic_upload_id', $upload->id);
        $candidateCount = (clone $query)->count();

        if ($candidateCount === 0) {
            return [
                'candidates' => 0,
                'matched' => 0,
                'unmatched' => 0,
                'review_required' => 0,
            ];
        }

        $query->orderBy('id')->chunkById(500, function ($variants): void {
            foreach ($variants as $variant) {
                $match = $this->resolvePatient($variant->sample_id);
                $variant->update([
                    'patient_id' => $match['patient_id'],
                    'mapping_status' => $match['status'],
                    'mapping_message' => $match['message'],
                ]);
            }
        });

        $matched = GenomicUploadVariant::where('genomic_upload_id', $upload->id)
            ->where('mapping_status', 'matched')
            ->count();
        $unmatched = GenomicUploadVariant::where('genomic_upload_id', $upload->id)
            ->where('mapping_status', 'unmatched')
            ->count();
        $review = GenomicUploadVariant::where('genomic_upload_id', $upload->id)
            ->where('mapping_status', 'review')
            ->count();

        $upload->refresh()->update([
            'status' => $matched > 0 && ($unmatched + $review) === 0 ? 'mapped' : 'review',
            'mapped_variants' => $matched,
            'unmapped_variants' => $unmatched + $review,
            'matched_at' => now(),
            'last_result' => [
                'operation' => 'match_persons',
                'status' => 'succeeded',
                'result' => [
                    'candidates' => $candidateCount,
                    'matched' => $matched,
                    'unmatched' => $unmatched,
                    'review_required' => $review,
                ],
            ],
        ]);

        return [
            'candidates' => $candidateCount,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'review_required' => $review,
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function importUpload(GenomicUpload $upload): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        $staged = GenomicUploadVariant::where('genomic_upload_id', $upload->id)
            ->whereNotNull('patient_id')
            ->whereIn('mapping_status', ['matched', 'imported'])
            ->orderBy('id')
            ->get();

        foreach ($staged as $variant) {
            try {
                $attributes = [
                    'patient_id' => $variant->patient_id,
                    'source_type' => 'upload',
                    'source_id' => (string) $upload->id,
                    'chromosome' => $variant->chromosome,
                    'position' => $variant->position,
                    'ref_allele' => $variant->reference_allele,
                    'alt_allele' => $variant->alternate_allele,
                ];

                $values = [
                    'gene' => $variant->gene_symbol ?: 'UNKNOWN',
                    'variant' => $variant->variant ?: $variant->reference_allele.'>'.$variant->alternate_allele,
                    'variant_type' => $variant->variant_type,
                    'zygosity' => $variant->zygosity,
                    'allele_frequency' => $variant->allele_frequency,
                    'clinical_significance' => $variant->clinical_significance,
                    'updated_at' => now(),
                ];

                $existing = GenomicVariant::where($attributes)->first();
                if ($existing) {
                    $existing->update($values);
                    $updated++;
                } else {
                    GenomicVariant::create(array_merge($attributes, $values));
                    $created++;
                }

                $variant->update([
                    'mapping_status' => 'imported',
                    'mapping_message' => 'Imported into clinical.genomic_variants.',
                ]);
            } catch (Throwable $e) {
                $errors[] = "Variant {$variant->id}: {$e->getMessage()}";
            }
        }

        $skipped = GenomicUploadVariant::where('genomic_upload_id', $upload->id)
            ->where(function ($query) {
                $query->whereNull('patient_id')
                    ->orWhereNotIn('mapping_status', ['matched', 'imported']);
            })
            ->count();

        $result = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];

        $upload->refresh()->update([
            'status' => 'imported',
            'mapped_variants' => GenomicUploadVariant::where('genomic_upload_id', $upload->id)
                ->where('mapping_status', 'imported')
                ->count(),
            'unmapped_variants' => 0,
            'imported_at' => now(),
            'error_message' => $errors === [] ? null : implode("\n", array_slice($errors, 0, 10)),
            'last_result' => [
                'operation' => 'import_to_omop',
                'status' => $errors === [] ? 'succeeded' : 'completed_with_errors',
                'result' => $result,
            ],
        ]);

        return $result;
    }

    /**
     * @return array{eligible: int, annotated: int, already_annotated: int, missing_reference: int}
     */
    public function annotateClinVar(GenomicUpload $upload): array
    {
        $uploadId = (string) $upload->id;

        $eligible = GenomicVariant::where('source_type', 'upload')
            ->where('source_id', $uploadId)
            ->count();
        $alreadyAnnotated = GenomicVariant::where('source_type', 'upload')
            ->where('source_id', $uploadId)
            ->whereNotNull('clinical_significance')
            ->count();
        $needsAnnotation = GenomicVariant::where('source_type', 'upload')
            ->where('source_id', $uploadId)
            ->whereNull('clinical_significance')
            ->count();

        $annotated = DB::update('
            UPDATE clinical.genomic_variants gv
            SET
                clinical_significance = cv.clinical_significance,
                clinvar_disease = cv.disease_name,
                clinvar_review_status = cv.review_status,
                updated_at = NOW()
            FROM clinical.clinvar_variants cv
            WHERE gv.source_type = ?
              AND gv.source_id = ?
              AND gv.chromosome = cv.chromosome
              AND gv.position = cv.position
              AND gv.ref_allele = cv.reference_allele
              AND gv.alt_allele = cv.alternate_allele
              AND gv.clinical_significance IS NULL
        ', ['upload', $uploadId]);

        $result = [
            'eligible' => $eligible,
            'annotated' => $annotated,
            'already_annotated' => $alreadyAnnotated,
            'missing_reference' => max(0, $needsAnnotation - $annotated),
        ];

        $upload->refresh()->update([
            'clinvar_annotated_at' => now(),
            'last_result' => [
                'operation' => 'annotate_clinvar',
                'status' => 'succeeded',
                'result' => $result,
            ],
        ]);

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     */
    private function flushBatch(array $batch): void
    {
        GenomicUploadVariant::upsert(
            $batch,
            ['genomic_upload_id', 'variant_key'],
            [
                'patient_id',
                'sample_id',
                'mapping_status',
                'mapping_message',
                'chromosome',
                'position',
                'reference_allele',
                'alternate_allele',
                'genome_build',
                'gene_symbol',
                'variant',
                'variant_type',
                'zygosity',
                'allele_frequency',
                'clinical_significance',
                'hgvs_c',
                'hgvs_p',
                'raw_payload',
                'updated_at',
            ],
        );
    }

    /**
     * @return array{patient_id: int|null, status: string, message: string}
     */
    private function resolvePatient(?string $sampleId): array
    {
        $sampleId = $this->blankToNull($sampleId);

        if ($sampleId === null) {
            return [
                'patient_id' => null,
                'status' => 'unmatched',
                'message' => 'No sample identifier was available for deterministic matching.',
            ];
        }

        $typedMatches = PatientIdentifier::where('identifier_value', $sampleId)
            ->whereIn('identifier_type', self::IDENTIFIER_TYPES)
            ->pluck('patient_id')
            ->unique()
            ->values();

        if ($typedMatches->count() === 1) {
            return [
                'patient_id' => (int) $typedMatches[0],
                'status' => 'matched',
                'message' => 'Matched exact genomic sample identifier.',
            ];
        }

        if ($typedMatches->count() > 1) {
            return [
                'patient_id' => null,
                'status' => 'review',
                'message' => 'Sample identifier matched multiple patients.',
            ];
        }

        $anyIdentifierMatches = PatientIdentifier::where('identifier_value', $sampleId)
            ->pluck('patient_id')
            ->unique()
            ->values();

        if ($anyIdentifierMatches->count() === 1) {
            return [
                'patient_id' => (int) $anyIdentifierMatches[0],
                'status' => 'matched',
                'message' => 'Matched exact patient identifier.',
            ];
        }

        if ($anyIdentifierMatches->count() > 1) {
            return [
                'patient_id' => null,
                'status' => 'review',
                'message' => 'Sample identifier matched multiple patients.',
            ];
        }

        $patient = ClinicalPatient::where('mrn', $sampleId)->first();

        if ($patient) {
            return [
                'patient_id' => (int) $patient->id,
                'status' => 'matched',
                'message' => 'Matched exact patient MRN.',
            ];
        }

        return [
            'patient_id' => null,
            'status' => 'unmatched',
            'message' => 'No exact patient identifier match was found.',
        ];
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function parseVcfRows(GenomicUpload $upload, string $path, array &$result): \Generator
    {
        $sampleNames = [];
        $sawHeader = false;

        foreach ($this->readLines($path, $upload->original_filename) as $lineNumber => $line) {
            $line = rtrim($line, "\r\n");

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '##')) {
                continue;
            }

            if (str_starts_with($line, '#CHROM')) {
                $sawHeader = true;
                $columns = explode("\t", $line);
                $sampleNames = array_slice($columns, 9);

                continue;
            }

            if (! $sawHeader) {
                $this->addError($result, "Line {$lineNumber}: VCF header row is missing.");
                $result['skipped']++;

                continue;
            }

            $fields = explode("\t", $line);
            if (count($fields) < 8) {
                $this->addError($result, "Line {$lineNumber}: VCF row has fewer than 8 columns.");
                $result['skipped']++;

                continue;
            }

            [$chrom, $pos, $id, $ref, $altList, $qual, $filter, $infoString] = array_slice($fields, 0, 8);
            $position = filter_var($pos, FILTER_VALIDATE_INT);
            if ($position === false || (int) $position <= 0) {
                $this->addError($result, "Line {$lineNumber}: invalid VCF position.");
                $result['skipped']++;

                continue;
            }

            $info = $this->parseInfo($infoString);
            $alts = array_values(array_filter(array_map('trim', explode(',', $altList)), fn ($alt) => $alt !== '' && $alt !== '.'));
            if ($alts === []) {
                $result['skipped']++;

                continue;
            }

            $formatKeys = isset($fields[8]) ? explode(':', $fields[8]) : [];
            $sampleValues = array_slice($fields, 9);

            foreach ($alts as $altIndex => $alt) {
                if ($sampleValues !== []) {
                    foreach ($sampleValues as $sampleIndex => $sampleValue) {
                        $sampleData = $this->parseFormatSample($formatKeys, $sampleValue);
                        if (! $this->genotypeContainsAlt($sampleData['GT'] ?? null, $altIndex + 1)) {
                            continue;
                        }

                        yield $this->stageRow($upload, [
                            'sample_id' => $sampleNames[$sampleIndex] ?? $upload->sample_id,
                            'chromosome' => $chrom,
                            'position' => (int) $position,
                            'reference_allele' => $ref,
                            'alternate_allele' => $alt,
                            'gene_symbol' => $this->geneFromInfo($info),
                            'variant' => $this->variantLabel($id, $ref, $alt, $info),
                            'variant_type' => $this->variantType($ref, $alt, $info),
                            'zygosity' => $this->zygosityFromGt($sampleData['GT'] ?? null),
                            'allele_frequency' => $this->alleleFrequency($info, $sampleData, $altIndex),
                            'clinical_significance' => $this->clinicalSignificance($info),
                            'hgvs_c' => $this->hgvs($info, 'c'),
                            'hgvs_p' => $this->hgvs($info, 'p'),
                            'raw_payload' => [
                                'id' => $id,
                                'qual' => $qual,
                                'filter' => $filter,
                                'info' => $info,
                                'format' => $sampleData,
                            ],
                        ]);
                    }
                } else {
                    yield $this->stageRow($upload, [
                        'sample_id' => $upload->sample_id,
                        'chromosome' => $chrom,
                        'position' => (int) $position,
                        'reference_allele' => $ref,
                        'alternate_allele' => $alt,
                        'gene_symbol' => $this->geneFromInfo($info),
                        'variant' => $this->variantLabel($id, $ref, $alt, $info),
                        'variant_type' => $this->variantType($ref, $alt, $info),
                        'zygosity' => null,
                        'allele_frequency' => $this->alleleFrequency($info, [], $altIndex),
                        'clinical_significance' => $this->clinicalSignificance($info),
                        'hgvs_c' => $this->hgvs($info, 'c'),
                        'hgvs_p' => $this->hgvs($info, 'p'),
                        'raw_payload' => [
                            'id' => $id,
                            'qual' => $qual,
                            'filter' => $filter,
                            'info' => $info,
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function parseDelimitedRows(GenomicUpload $upload, string $path, array &$result): \Generator
    {
        $headers = null;
        $delimiter = null;

        foreach ($this->readLines($path, $upload->original_filename) as $lineNumber => $line) {
            $line = rtrim($line, "\r\n");
            if ($line === '' || str_starts_with($line, '#version')) {
                continue;
            }

            if ($headers === null) {
                $delimiter = str_contains($line, "\t") ? "\t" : ',';
                $headers = array_map(fn ($header) => $this->normalizeHeader($header), str_getcsv($line, $delimiter));

                continue;
            }

            $values = str_getcsv($line, $delimiter ?? ',');
            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = $values[$index] ?? null;
            }

            $chromosome = $this->field($record, ['chromosome', 'chrom', 'chr']);
            $position = $this->field($record, ['start_position', 'position', 'pos']);
            $reference = $this->field($record, ['reference_allele', 'ref', 'ref_allele']);
            $alternate = $this->field($record, ['tumor_seq_allele2', 'alternate_allele', 'alt', 'alt_allele']);

            if ($chromosome === null || $position === null || $reference === null || $alternate === null) {
                $this->addError($result, "Line {$lineNumber}: missing chromosome, position, reference, or alternate allele.");
                $result['skipped']++;

                continue;
            }

            $positionInt = filter_var($position, FILTER_VALIDATE_INT);
            if ($positionInt === false || (int) $positionInt <= 0) {
                $this->addError($result, "Line {$lineNumber}: invalid variant position.");
                $result['skipped']++;

                continue;
            }

            yield $this->stageRow($upload, [
                'sample_id' => $this->field($record, ['tumor_sample_barcode', 'sample_id', 'sample', 'patient_id']) ?? $upload->sample_id,
                'chromosome' => $chromosome,
                'position' => (int) $positionInt,
                'reference_allele' => $reference,
                'alternate_allele' => $alternate,
                'gene_symbol' => $this->field($record, ['hugo_symbol', 'gene_symbol', 'gene']),
                'variant' => $this->field($record, ['hgvsp_short', 'hgvsp', 'protein_change', 'variant']),
                'variant_type' => $this->field($record, ['variant_type', 'variant_classification']),
                'zygosity' => $this->field($record, ['zygosity']),
                'allele_frequency' => $this->delimitedAlleleFrequency($record),
                'clinical_significance' => $this->field($record, ['clinical_significance', 'clinvar_significance']),
                'hgvs_c' => $this->field($record, ['hgvsc', 'hgvs_c']),
                'hgvs_p' => $this->field($record, ['hgvsp_short', 'hgvsp', 'hgvs_p']),
                'raw_payload' => $record,
            ]);
        }

        if ($headers === null) {
            throw new RuntimeException('Delimited genomic upload did not contain a header row.');
        }
    }

    /**
     * @return \Generator<int, string>
     */
    private function readLines(string $path, string $originalFilename): \Generator
    {
        $isGzip = str_ends_with(strtolower($path), '.gz') || str_ends_with(strtolower($originalFilename), '.gz');

        if ($isGzip) {
            $handle = @gzopen($path, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Unable to open gzip genomic upload.');
            }

            try {
                $lineNumber = 0;
                while (! gzeof($handle)) {
                    $line = gzgets($handle);
                    if ($line === false) {
                        break;
                    }
                    $lineNumber++;
                    yield $lineNumber => $line;
                }
            } finally {
                gzclose($handle);
            }

            return;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open genomic upload.');
        }

        try {
            $lineNumber = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                yield $lineNumber => $line;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stageRow(GenomicUpload $upload, array $data): array
    {
        $chromosome = $this->normalizeChromosome((string) $data['chromosome']);
        $reference = strtoupper((string) $data['reference_allele']);
        $alternate = strtoupper((string) $data['alternate_allele']);
        $sampleId = $this->blankToNull($data['sample_id'] ?? null);
        $now = now()->toDateTimeString();
        $key = hash('sha256', implode('|', [
            $sampleId ?? '',
            $chromosome,
            (string) $data['position'],
            $reference,
            $alternate,
        ]));

        return [
            'genomic_upload_id' => $upload->id,
            'patient_id' => null,
            'sample_id' => $sampleId,
            'mapping_status' => 'unmatched',
            'mapping_message' => null,
            'chromosome' => $chromosome,
            'position' => (int) $data['position'],
            'reference_allele' => substr($reference, 0, 500),
            'alternate_allele' => substr($alternate, 0, 500),
            'genome_build' => $upload->genome_build ?: 'GRCh38',
            'gene_symbol' => $this->blankToNull(isset($data['gene_symbol']) ? strtoupper((string) $data['gene_symbol']) : null),
            'variant' => $this->blankToNull($data['variant'] ?? null),
            'variant_type' => $this->blankToNull($data['variant_type'] ?? null),
            'zygosity' => $this->blankToNull($data['zygosity'] ?? null),
            'allele_frequency' => $this->boundedFrequency($data['allele_frequency'] ?? null),
            'clinical_significance' => $this->blankToNull($data['clinical_significance'] ?? null),
            'hgvs_c' => $this->blankToNull($data['hgvs_c'] ?? null),
            'hgvs_p' => $this->blankToNull($data['hgvs_p'] ?? null),
            'raw_payload' => isset($data['raw_payload']) ? json_encode($data['raw_payload']) : null,
            'variant_key' => $key,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function normalizeFormat(?string $format): string
    {
        return match (strtolower(trim((string) $format))) {
            'vcf' => 'vcf',
            'maf', 'cbio_maf', 'csv', 'tsv' => 'delimited',
            'fhir_genomics' => 'fhir_genomics',
            default => throw new RuntimeException('Unsupported genomic upload format: '.($format ?: 'unknown')),
        };
    }

    private function normalizeChromosome(string $chromosome): string
    {
        return substr(ltrim(trim($chromosome), 'chrCHR'), 0, 20);
    }

    /**
     * @return array<string, string>
     */
    private function parseInfo(string $infoString): array
    {
        $info = [];
        foreach (explode(';', $infoString) as $part) {
            if ($part === '') {
                continue;
            }
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $info[trim($key)] = rawurldecode(trim($value));
            } else {
                $info[trim($part)] = 'true';
            }
        }

        return $info;
    }

    /**
     * @param  array<int, string>  $formatKeys
     * @return array<string, string>
     */
    private function parseFormatSample(array $formatKeys, string $sampleValue): array
    {
        $values = explode(':', $sampleValue);
        $sample = [];
        foreach ($formatKeys as $index => $key) {
            $sample[$key] = $values[$index] ?? '';
        }

        return $sample;
    }

    private function genotypeContainsAlt(?string $genotype, int $altIndex): bool
    {
        if ($genotype === null || $genotype === '') {
            return true;
        }

        $genotype = explode(':', $genotype, 2)[0];
        if ($genotype === './.' || $genotype === '.|.') {
            return false;
        }

        return in_array((string) $altIndex, preg_split('/[\/|]/', $genotype) ?: [], true);
    }

    private function zygosityFromGt(?string $genotype): ?string
    {
        if ($genotype === null || $genotype === '') {
            return null;
        }

        $alleles = array_values(array_filter(preg_split('/[\/|]/', explode(':', $genotype, 2)[0]) ?: [], fn ($allele) => $allele !== '.'));
        if ($alleles === []) {
            return null;
        }

        return count(array_unique($alleles)) === 1 ? 'homozygous' : 'heterozygous';
    }

    /**
     * @param  array<string, string>  $info
     * @param  array<string, string>  $sampleData
     */
    private function alleleFrequency(array $info, array $sampleData, int $altIndex): ?float
    {
        foreach (['AF', 'VAF', 'VF', 'FA'] as $key) {
            if (isset($sampleData[$key])) {
                return $this->numericListValue($sampleData[$key], $altIndex);
            }
        }

        if (isset($sampleData['AD'])) {
            $depths = array_map('trim', explode(',', $sampleData['AD']));
            $refDepth = isset($depths[0]) ? (float) $depths[0] : null;
            $altDepth = isset($depths[$altIndex + 1]) ? (float) $depths[$altIndex + 1] : null;
            if ($refDepth !== null && $altDepth !== null && ($refDepth + $altDepth) > 0) {
                return $altDepth / ($refDepth + $altDepth);
            }
        }

        if (isset($info['AF'])) {
            return $this->numericListValue($info['AF'], $altIndex);
        }

        return null;
    }

    private function numericListValue(?string $value, int $index): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $value));
        $candidate = $parts[$index] ?? $parts[0] ?? null;

        if ($candidate === null || ! is_numeric($candidate)) {
            return null;
        }

        return (float) $candidate;
    }

    /**
     * @param  array<string, string>  $info
     */
    private function geneFromInfo(array $info): ?string
    {
        if (! empty($info['GENEINFO'])) {
            $first = explode('|', $info['GENEINFO'])[0];

            return explode(':', $first)[0] ?: null;
        }

        foreach (['GENE', 'SYMBOL', 'Gene.refGene'] as $key) {
            if (! empty($info[$key])) {
                return explode(',', $info[$key])[0];
            }
        }

        if (! empty($info['ANN'])) {
            $annotation = explode(',', $info['ANN'])[0];
            $parts = explode('|', $annotation);

            return $parts[3] ?? null;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $info
     */
    private function variantLabel(string $id, string $ref, string $alt, array $info): string
    {
        foreach (['HGVSp', 'HGVSp_Short', 'HGVSc', 'CLNHGVS'] as $key) {
            if (! empty($info[$key])) {
                return explode(',', $info[$key])[0];
            }
        }

        return $id !== '.' && $id !== '' ? $id : "{$ref}>{$alt}";
    }

    /**
     * @param  array<string, string>  $info
     */
    private function variantType(string $ref, string $alt, array $info): string
    {
        foreach (['TYPE', 'VT'] as $key) {
            if (! empty($info[$key])) {
                return substr($info[$key], 0, 50);
            }
        }

        return strlen($ref) === 1 && strlen($alt) === 1 ? 'SNV' : 'indel';
    }

    /**
     * @param  array<string, string>  $info
     */
    private function clinicalSignificance(array $info): ?string
    {
        $value = $info['CLNSIG'] ?? $info['clinical_significance'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return str_replace('_', ' ', explode('|', $value)[0]);
    }

    /**
     * @param  array<string, string>  $info
     */
    private function hgvs(array $info, string $kind): ?string
    {
        $keys = $kind === 'p' ? ['HGVSp', 'HGVSp_Short'] : ['HGVSc', 'CLNHGVS'];
        foreach ($keys as $key) {
            if (! empty($info[$key])) {
                $value = explode(',', $info[$key])[0];
                if ($kind === 'c' && str_contains($value, ':p.')) {
                    continue;
                }

                return $value;
            }
        }

        return null;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(ltrim(trim($header), '#'));

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $header), '_');
    }

    /**
     * @param  array<string, string|null>  $record
     * @param  array<int, string>  $names
     */
    private function field(array $record, array $names): ?string
    {
        foreach ($names as $name) {
            $key = $this->normalizeHeader($name);
            if (array_key_exists($key, $record)) {
                return $this->blankToNull($record[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|null>  $record
     */
    private function delimitedAlleleFrequency(array $record): ?float
    {
        $direct = $this->field($record, ['allele_frequency', 'vaf', 'tumor_vaf', 't_vaf']);
        if ($direct !== null && is_numeric($direct)) {
            return (float) $direct;
        }

        $alt = $this->field($record, ['t_alt_count', 'alt_count']);
        $depth = $this->field($record, ['t_depth', 'depth', 'read_depth']);
        if ($alt !== null && $depth !== null && is_numeric($alt) && is_numeric($depth) && (float) $depth > 0) {
            return (float) $alt / (float) $depth;
        }

        return null;
    }

    private function boundedFrequency(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $frequency = (float) $value;
        if ($frequency > 1 && $frequency <= 100) {
            $frequency /= 100;
        }

        return max(0, min(1, $frequency));
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || $value === '.' ? null : $value;
    }

    /**
     * @param  array{errors: array<int, string>}  $result
     */
    private function addError(array &$result, string $message): void
    {
        if (count($result['errors']) < 25) {
            $result['errors'][] = $message;
        }
    }
}
