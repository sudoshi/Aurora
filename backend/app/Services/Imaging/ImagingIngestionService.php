<?php

namespace App\Services\Imaging;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\ImagingIngestionRun;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\PatientIdentifier;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class ImagingIngestionService
{
    /** @return array{0: ImagingIngestionRun, 1: bool} */
    public function createOrReuseRun(string $runType, array $parameters, ?int $requestedBy): array
    {
        $normalized = $this->normalizeParameters($runType, $parameters);
        $fingerprint = hash('sha256', $runType.'|'.json_encode($normalized));

        $active = ImagingIngestionRun::where('run_type', $runType)
            ->where('fingerprint', $fingerprint)
            ->whereIn('status', ['queued', 'running'])
            ->first();

        if ($active) {
            return [$active, false];
        }

        $run = ImagingIngestionRun::create([
            'run_type' => $runType,
            'status' => 'queued',
            'fingerprint' => $fingerprint,
            'requested_by' => $requestedBy,
            'parameters' => $normalized,
            'result' => [
                'message' => 'Queued',
            ],
            'queued_at' => now(),
        ]);

        return [$run, true];
    }

    public function processDicomwebIndex(ImagingIngestionRun $run): ImagingIngestionRun
    {
        $this->markStarted($run);

        try {
            $parameters = $run->parameters ?? [];
            $payload = $this->fetchDicomwebStudies($parameters);
            $stats = $this->emptyStats(count($payload));

            foreach ($payload as $entry) {
                $stats['processed']++;
                $study = $this->studyFromDicomwebEntry($entry);

                if (! $study['study_uid']) {
                    $this->skipStudy($stats, 'missing_study_uid', $study);

                    continue;
                }

                if (! $study['patient_id_dicom']) {
                    $this->skipStudy($stats, 'blank_patient_id', $study);

                    continue;
                }

                $resolved = $this->resolvePatient($study['patient_id_dicom'], $study['issuer_of_patient_id']);

                if ($resolved['status'] !== 'matched' || ! $resolved['patient']) {
                    $this->skipStudy($stats, $resolved['status'], $study);

                    continue;
                }

                $localStudy = ImagingStudy::updateOrCreate(
                    ['study_uid' => $study['study_uid']],
                    [
                        'patient_id' => $resolved['patient']->id,
                        'modality' => $study['modality'] ?: 'OT',
                        'study_date' => $study['study_date'],
                        'description' => $study['description'],
                        'body_part' => $study['body_part'],
                        'accession_number' => $study['accession_number'],
                        'num_series' => $study['num_series'],
                        'num_instances' => $study['num_instances'],
                        'dicom_endpoint' => 'orthanc-dicom-web',
                        'source_type' => 'dicomweb',
                        'source_id' => 'dicomweb:'.$study['study_uid'],
                    ],
                );

                $localStudy->wasRecentlyCreated ? $stats['studies_created']++ : $stats['studies_updated']++;

                if (($parameters['index_series'] ?? true) === true) {
                    $seriesStats = $this->indexDicomwebSeries($localStudy, $study['study_uid']);
                    $stats['series_created'] += $seriesStats['created'];
                    $stats['series_updated'] += $seriesStats['updated'];
                    $stats['errors'] += $seriesStats['errors'];
                }
            }

            return $this->markSucceeded($run, $stats);
        } catch (\Throwable $e) {
            return $this->markFailed($run, $e->getMessage());
        }
    }

    public function processLocalImport(ImagingIngestionRun $run): ImagingIngestionRun
    {
        $this->markStarted($run);

        $parameters = $run->parameters ?? [];
        $path = (string) ($parameters['path'] ?? '');
        $command = config('services.imaging.local_import_command');

        if (! $command) {
            return $this->markFailed(
                $run,
                'Local DICOM import command is not configured. Set IMAGING_LOCAL_IMPORT_COMMAND before running queued local imports.'
            );
        }

        try {
            $process = new Process([(string) $command, $path]);
            $process->setTimeout(3600);
            $process->run();

            if (! $process->isSuccessful()) {
                return $this->markFailed($run, trim($process->getErrorOutput()) ?: 'Local DICOM import command failed.');
            }

            return $this->markSucceeded($run, [
                'requested' => 1,
                'processed' => 1,
                'studies_created' => 0,
                'studies_updated' => 0,
                'series_created' => 0,
                'series_updated' => 0,
                'studies_skipped' => 0,
                'errors' => 0,
                'message' => 'Local import command completed. Review command output for imported counts.',
                'command_output' => trim($process->getOutput()),
            ]);
        } catch (\Throwable $e) {
            return $this->markFailed($run, $e->getMessage());
        }
    }

    public function processAutoLink(ImagingIngestionRun $run): ImagingIngestionRun
    {
        $this->markStarted($run);

        return $this->markSucceeded($run, [
            'requested' => 0,
            'processed' => 0,
            'linked' => 0,
            'eligible' => 0,
            'unmatched' => 0,
            'ambiguous' => 0,
            'errors' => 0,
            'message' => 'No quarantined unlinked imaging-study table exists; DICOMweb ingestion resolves deterministic identifiers before insert.',
        ]);
    }

    public function pathIsAllowlisted(string $path): bool
    {
        $realPath = realpath($path);

        if (! $realPath) {
            return false;
        }

        foreach ($this->localImportRoots() as $root) {
            $realRoot = realpath($root);

            if ($realRoot && ($realPath === $realRoot || str_starts_with($realPath, rtrim($realRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function localImportRoots(): array
    {
        return array_values(array_filter((array) config('services.imaging.local_import_roots', [])));
    }

    public function runPayload(ImagingIngestionRun $run): array
    {
        return [
            'run_id' => $run->id,
            'operation' => $run->run_type,
            'status' => $run->status,
            'fingerprint' => $run->fingerprint,
            'parameters' => $run->parameters ?? [],
            'result' => $run->result ?? [],
            'error_message' => $run->error_message,
            'requested_count' => $run->requested_count,
            'processed_count' => $run->processed_count,
            'studies_created' => $run->studies_created,
            'studies_updated' => $run->studies_updated,
            'series_created' => $run->series_created,
            'series_updated' => $run->series_updated,
            'studies_skipped' => $run->studies_skipped,
            'errors_count' => $run->errors_count,
            'queued_at' => $run->queued_at?->toISOString(),
            'started_at' => $run->started_at?->toISOString(),
            'finished_at' => $run->finished_at?->toISOString(),
            'poll_url' => '/api/imaging/ingestion-runs/'.$run->id,
        ];
    }

    private function normalizeParameters(string $runType, array $parameters): array
    {
        return match ($runType) {
            'dicomweb_index' => [
                'limit' => min(max((int) ($parameters['limit'] ?? 100), 1), 500),
                'modality' => $this->blankToNull($parameters['modality'] ?? null),
                'patient_id' => $this->blankToNull($parameters['patient_id'] ?? null),
                'accession_number' => $this->blankToNull($parameters['accession_number'] ?? null),
                'from_date' => $this->normalizeDicomDate($parameters['from_date'] ?? null),
                'to_date' => $this->normalizeDicomDate($parameters['to_date'] ?? null),
                'index_series' => (bool) ($parameters['index_series'] ?? true),
            ],
            'local_import' => [
                'path' => (string) ($parameters['path'] ?? ''),
            ],
            'auto_link' => [
                'mode' => 'deterministic_identifier',
            ],
            default => $parameters,
        };
    }

    private function fetchDicomwebStudies(array $parameters): array
    {
        $query = [
            'limit' => (string) ($parameters['limit'] ?? 100),
            'includefield' => 'all',
        ];

        if (! empty($parameters['modality'])) {
            $query['ModalitiesInStudy'] = $parameters['modality'];
        }

        if (! empty($parameters['patient_id'])) {
            $query['PatientID'] = $parameters['patient_id'];
        }

        if (! empty($parameters['accession_number'])) {
            $query['AccessionNumber'] = $parameters['accession_number'];
        }

        if (! empty($parameters['from_date']) || ! empty($parameters['to_date'])) {
            $from = str_replace('-', '', (string) ($parameters['from_date'] ?? ''));
            $to = str_replace('-', '', (string) ($parameters['to_date'] ?? ''));
            $query['StudyDate'] = $from.'-'.$to;
        }

        $response = $this->orthancRequest()->get('/dicom-web/studies', $query);

        if ($response->failed()) {
            throw new \RuntimeException('DICOMweb study query failed with status '.$response->status());
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException('DICOMweb study query returned an invalid payload.');
        }

        return $payload;
    }

    private function indexDicomwebSeries(ImagingStudy $study, string $studyUid): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
        $response = $this->orthancRequest()->get('/dicom-web/studies/'.rawurlencode($studyUid).'/series', [
            'includefield' => 'all',
        ]);

        if ($response->failed()) {
            $stats['errors']++;

            return $stats;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            $stats['errors']++;

            return $stats;
        }

        foreach ($payload as $entry) {
            if (! is_array($entry)) {
                $stats['errors']++;

                continue;
            }

            $seriesUid = $this->dicomScalar($entry, '0020000E', 'SeriesInstanceUID');

            if (! $seriesUid) {
                $stats['errors']++;

                continue;
            }

            $series = ImagingSeries::updateOrCreate(
                ['series_uid' => $seriesUid],
                [
                    'imaging_study_id' => $study->id,
                    'series_number' => $this->nullableInt($this->dicomScalar($entry, '00200011', 'SeriesNumber')),
                    'modality' => $this->dicomScalar($entry, '00080060', 'Modality'),
                    'description' => $this->dicomScalar($entry, '0008103E', 'SeriesDescription'),
                    'num_instances' => $this->nullableInt($this->dicomScalar($entry, '00201209', 'NumberOfSeriesRelatedInstances')),
                    'source_id' => 'dicomweb:'.$seriesUid,
                    'source_type' => 'dicomweb',
                ],
            );

            $series->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
        }

        return $stats;
    }

    private function studyFromDicomwebEntry(mixed $entry): array
    {
        $entry = is_array($entry) ? $entry : [];
        $modalities = $this->dicomValues($entry, '00080061', 'ModalitiesInStudy');
        $modality = $modalities[0] ?? $this->dicomScalar($entry, '00080060', 'Modality');
        $description = $this->dicomScalar($entry, '00081030', 'StudyDescription');
        $bodyPart = $this->dicomScalar($entry, '00180015', 'BodyPartExamined');

        return [
            'study_uid' => $this->dicomScalar($entry, '0020000D', 'StudyInstanceUID'),
            'patient_id_dicom' => $this->dicomScalar($entry, '00100020', 'PatientID'),
            'issuer_of_patient_id' => $this->dicomScalar($entry, '00100021', 'IssuerOfPatientID'),
            'patient_name' => $this->dicomScalar($entry, '00100010', 'PatientName'),
            'accession_number' => $this->dicomScalar($entry, '00080050', 'AccessionNumber'),
            'study_date' => $this->normalizeDicomDate($this->dicomScalar($entry, '00080020', 'StudyDate')),
            'description' => $description,
            'modality' => $modality,
            'body_part' => $bodyPart ?: $this->inferBodyPart((string) $description),
            'num_series' => $this->nullableInt($this->dicomScalar($entry, '00201206', 'NumberOfStudyRelatedSeries')),
            'num_instances' => $this->nullableInt($this->dicomScalar($entry, '00201208', 'NumberOfStudyRelatedInstances')),
        ];
    }

    /** @return array{status: string, patient: ?ClinicalPatient} */
    private function resolvePatient(?string $dicomPatientId, ?string $issuer): array
    {
        $dicomPatientId = trim((string) $dicomPatientId);

        if ($dicomPatientId === '') {
            return ['status' => 'blank_patient_id', 'patient' => null];
        }

        $identifierQuery = PatientIdentifier::where('identifier_value', $dicomPatientId)
            ->whereIn('identifier_type', [
                'dicom_patient_id',
                'tcia_subject',
                'tcga_barcode',
                'cptac_barcode',
            ]);

        if ($issuer) {
            $issuerMatches = (clone $identifierQuery)
                ->where('source_system', $issuer)
                ->pluck('patient_id')
                ->unique()
                ->values();

            if ($issuerMatches->count() === 1) {
                return ['status' => 'matched', 'patient' => ClinicalPatient::find((int) $issuerMatches[0])];
            }

            if ($issuerMatches->count() > 1) {
                return ['status' => 'ambiguous_patient_identifier', 'patient' => null];
            }
        }

        $identifierMatches = $identifierQuery->pluck('patient_id')->unique()->values();

        if ($identifierMatches->count() === 1) {
            return ['status' => 'matched', 'patient' => ClinicalPatient::find((int) $identifierMatches[0])];
        }

        if ($identifierMatches->count() > 1) {
            return ['status' => 'ambiguous_patient_identifier', 'patient' => null];
        }

        $patient = ClinicalPatient::where('mrn', $dicomPatientId)->first();

        if ($patient) {
            return ['status' => 'matched', 'patient' => $patient];
        }

        return ['status' => 'no_patient_mapping', 'patient' => null];
    }

    private function markStarted(ImagingIngestionRun $run): void
    {
        $run->forceFill([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
        ])->save();
    }

    private function markSucceeded(ImagingIngestionRun $run, array $stats): ImagingIngestionRun
    {
        $run->forceFill([
            'status' => 'succeeded',
            'result' => $stats,
            'requested_count' => (int) ($stats['requested'] ?? 0),
            'processed_count' => (int) ($stats['processed'] ?? 0),
            'studies_created' => (int) ($stats['studies_created'] ?? 0),
            'studies_updated' => (int) ($stats['studies_updated'] ?? 0),
            'series_created' => (int) ($stats['series_created'] ?? 0),
            'series_updated' => (int) ($stats['series_updated'] ?? 0),
            'studies_skipped' => (int) ($stats['studies_skipped'] ?? 0),
            'errors_count' => (int) ($stats['errors'] ?? 0),
            'finished_at' => now(),
        ])->save();

        return $run->refresh();
    }

    private function markFailed(ImagingIngestionRun $run, string $message): ImagingIngestionRun
    {
        $run->forceFill([
            'status' => 'failed',
            'error_message' => $message,
            'result' => array_merge($run->result ?? [], [
                'message' => $message,
            ]),
            'errors_count' => max(1, (int) $run->errors_count),
            'finished_at' => now(),
        ])->save();

        return $run->refresh();
    }

    private function emptyStats(int $requested): array
    {
        return [
            'requested' => $requested,
            'processed' => 0,
            'studies_created' => 0,
            'studies_updated' => 0,
            'series_created' => 0,
            'series_updated' => 0,
            'studies_skipped' => 0,
            'errors' => 0,
            'skipped' => [],
        ];
    }

    private function skipStudy(array &$stats, string $reason, array $study): void
    {
        $stats['studies_skipped']++;
        $stats['skipped'][] = [
            'reason' => $reason,
            'study_uid' => $study['study_uid'] ?? null,
            'patient_id_dicom' => $study['patient_id_dicom'] ?? null,
            'accession_number' => $study['accession_number'] ?? null,
            'description' => $study['description'] ?? null,
        ];
    }

    private function orthancRequest(): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('services.orthanc.base_url'), '/'))
            ->acceptJson()
            ->timeout(60);

        $user = config('services.orthanc.user');
        $password = config('services.orthanc.password');

        if ($user && $password) {
            $request = $request->withBasicAuth((string) $user, (string) $password);
        }

        return $request;
    }

    private function dicomScalar(array $entry, string $tag, string $keyword): ?string
    {
        $values = $this->dicomValues($entry, $tag, $keyword);
        $value = $values[0] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function dicomValues(array $entry, string $tag, string $keyword): array
    {
        $attribute = $entry[$tag] ?? $entry[$keyword] ?? null;

        if (! is_array($attribute)) {
            return is_scalar($attribute) ? [(string) $attribute] : [];
        }

        $values = $attribute['Value'] ?? $attribute['value'] ?? null;

        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(function (mixed $value) {
                if (is_array($value)) {
                    return $value['Alphabetic'] ?? Arr::first($value);
                }

                return $value;
            })
            ->filter(fn (mixed $value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn (mixed $value) => trim((string) $value))
            ->values()
            ->all();
    }

    private function normalizeDicomDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{8}$/', $value) === 1) {
            return substr($value, 0, 4).'-'.substr($value, 4, 2).'-'.substr($value, 6, 2);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function inferBodyPart(string $description): ?string
    {
        $text = strtolower($description);

        return match (true) {
            str_contains($text, 'chest'), str_contains($text, 'lung'), str_contains($text, 'thorax') => 'Chest',
            str_contains($text, 'abdomen'), str_contains($text, 'liver'), str_contains($text, 'kidney'), str_contains($text, 'renal'), str_contains($text, 'pancrea') => 'Abdomen',
            str_contains($text, 'pelvis'), str_contains($text, 'prostate'), str_contains($text, 'bladder') => 'Pelvis',
            str_contains($text, 'brain'), str_contains($text, 'head'), str_contains($text, 'neuro') => 'Brain',
            str_contains($text, 'breast'), str_contains($text, 'mammo') => 'Breast',
            str_contains($text, 'spine'), str_contains($text, 'lumbar'), str_contains($text, 'cervical'), str_contains($text, 'thoracic') => 'Spine',
            default => null,
        };
    }
}
