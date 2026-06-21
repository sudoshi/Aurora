<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class SystemHealthController extends Controller
{
    /** @var array<string, callable(): array<string, mixed>> */
    private array $checkers;

    public function __construct()
    {
        $this->checkers = [
            'backend' => fn () => $this->checkBackend(),
            'redis' => fn () => $this->checkRedis(),
            'ai' => fn () => $this->checkAiService(),
            'queue' => fn () => $this->checkQueue(),
            'orthanc' => fn () => $this->checkOrthanc(),
            'federation' => fn () => $this->checkFederation(),
            'reverb' => fn () => $this->checkReverb(),
            'oncokb_sync' => fn () => $this->checkOncoKbSync(),
            'clinvar_sync' => fn () => $this->checkClinVarSync(),
            'clingen_sync' => fn () => $this->checkClinGenSync(),
            'dicom_sync' => fn () => $this->checkDicomSync(),
        ];
    }

    public function index(): JsonResponse
    {
        $services = [];
        foreach ($this->checkers as $checker) {
            $services[] = $checker();
        }

        return response()->json([
            'services' => $services,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function show(string $key): JsonResponse
    {
        if (! isset($this->checkers[$key])) {
            return response()->json(['message' => 'Unknown service.'], 404);
        }

        $status = ($this->checkers[$key])();
        $metrics = $this->getMetricsForService($key);

        return response()->json([
            'service' => $status,
            'metrics' => $metrics,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getMetricsForService(string $key): array
    {
        return match ($key) {
            'backend' => $this->getBackendMetrics(),
            'redis' => $this->getRedisMetrics(),
            'ai' => $this->getAiMetrics(),
            'queue' => $this->getQueueMetrics(),
            'orthanc' => $this->getOrthancMetrics(),
            'federation' => [],
            'reverb' => $this->getReverbMetrics(),
            'oncokb_sync' => $this->getSyncMetrics('clinical.gene_drug_interactions', 'oncokb_last_synced_at'),
            'clinvar_sync' => $this->getClinVarSyncMetrics(),
            'clingen_sync' => $this->getSyncMetrics('clinical.clingen_gene_validity', 'last_checked_at'),
            'dicom_sync' => $this->getDicomSyncMetrics(),
            default => [],
        };
    }

    private function checkBackend(): array
    {
        return [
            'name' => 'Backend API',
            'key' => 'backend',
            'status' => 'healthy',
            'message' => 'Laravel is responding normally.',
        ];
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'name' => 'Redis',
                'key' => 'redis',
                'status' => 'healthy',
                'message' => 'Redis is reachable.',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Redis',
                'key' => 'redis',
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkAiService(): array
    {
        $url = rtrim(config('services.ai.url', env('AI_SERVICE_URL', 'http://localhost:8000')), '/');

        try {
            $response = Http::timeout(3)->get("{$url}/health");

            if ($response->successful()) {
                return [
                    'name' => 'AI Service (Abby)',
                    'key' => 'ai',
                    'status' => 'healthy',
                    'message' => 'AI service is reachable.',
                ];
            }

            return [
                'name' => 'AI Service (Abby)',
                'key' => 'ai',
                'status' => 'degraded',
                'message' => "AI service returned HTTP {$response->status()}.",
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'AI Service (Abby)',
                'key' => 'ai',
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            $status = $failed > 0 ? 'degraded' : 'healthy';

            return [
                'name' => 'Job Queue',
                'key' => 'queue',
                'status' => $status,
                'message' => "Pending: {$pending}, Failed: {$failed}",
                'details' => ['pending' => $pending, 'failed' => $failed],
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Job Queue',
                'key' => 'queue',
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getBackendMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRedisMetrics(): array
    {
        try {
            /** @var array<string, mixed> $info */
            $info = Redis::connection()->command('info');

            return [
                'version' => $info['redis_version'] ?? 'unknown',
                'uptime_seconds' => (int) ($info['uptime_in_seconds'] ?? 0),
                'connected_clients' => (int) ($info['connected_clients'] ?? 0),
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'used_memory_peak_human' => $info['used_memory_peak_human'] ?? 'unknown',
                'total_commands_processed' => (int) ($info['total_commands_processed'] ?? 0),
                'keyspace_hits' => (int) ($info['keyspace_hits'] ?? 0),
                'keyspace_misses' => (int) ($info['keyspace_misses'] ?? 0),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getAiMetrics(): array
    {
        $url = rtrim(config('services.ai.url', env('AI_SERVICE_URL', 'http://localhost:8000')), '/');

        try {
            $response = Http::timeout(3)->get("{$url}/health");

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueueMetrics(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return [
                'pending' => $pending,
                'failed' => $failed,
                'driver' => config('queue.default'),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkOrthanc(): array
    {
        $name = 'Orthanc (DICOM)';
        $key = 'orthanc';

        $baseUrl = config('services.orthanc.base_url');
        $user = config('services.orthanc.user');
        $pass = config('services.orthanc.password');

        if (empty($baseUrl) || empty($user) || empty($pass)) {
            return [
                'name' => $name,
                'key' => $key,
                'status' => 'degraded',
                'message' => 'Orthanc is not configured (base_url/credentials missing).',
            ];
        }

        $url = rtrim((string) $baseUrl, '/');

        try {
            $response = Http::timeout(3)->withBasicAuth((string) $user, (string) $pass)->get("{$url}/system");

            if ($response->successful()) {
                return [
                    'name' => $name,
                    'key' => $key,
                    'status' => 'healthy',
                    'message' => 'Orthanc is reachable.',
                ];
            }

            return [
                'name' => $name,
                'key' => $key,
                'status' => 'degraded',
                'message' => "Orthanc returned HTTP {$response->status()}.",
            ];
        } catch (\Throwable $e) {
            return [
                'name' => $name,
                'key' => $key,
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkFederation(): array
    {
        $name = 'Federation Relay';
        $key = 'federation';

        $url = rtrim((string) config('services.federation.url', env('FEDERATION_URL', 'http://host.docker.internal:8200')), '/');

        try {
            $response = Http::timeout(3)->get("{$url}/health");

            if ($response->successful()) {
                return [
                    'name' => $name,
                    'key' => $key,
                    'status' => 'healthy',
                    'message' => 'Federation relay is reachable.',
                ];
            }

            return [
                'name' => $name,
                'key' => $key,
                'status' => 'degraded',
                'message' => "Federation relay returned HTTP {$response->status()}.",
            ];
        } catch (\Throwable $e) {
            return [
                'name' => $name,
                'key' => $key,
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkReverb(): array
    {
        $name = 'Realtime (Reverb)';
        $key = 'reverb';

        if (config('broadcasting.default') !== 'reverb') {
            return [
                'name' => $name,
                'key' => $key,
                'status' => 'degraded',
                'message' => 'Realtime disabled (BROADCAST_CONNECTION not reverb).',
            ];
        }

        $host = config('broadcasting.connections.reverb.options.host')
            ?: env('REVERB_SERVER_HOST', env('REVERB_HOST', 'localhost'));
        $port = (int) (env('REVERB_SERVER_PORT', env('REVERB_PORT', 8080)));

        try {
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen((string) $host, $port, $errno, $errstr, 2);

            if ($socket !== false) {
                fclose($socket);

                return [
                    'name' => $name,
                    'key' => $key,
                    'status' => 'healthy',
                    'message' => "Reverb server reachable at {$host}:{$port}.",
                ];
            }

            return [
                'name' => $name,
                'key' => $key,
                'status' => 'down',
                'message' => "Reverb server unreachable at {$host}:{$port} ({$errstr}).",
            ];
        } catch (\Throwable $e) {
            return [
                'name' => $name,
                'key' => $key,
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrthancMetrics(): array
    {
        $baseUrl = config('services.orthanc.base_url');
        $user = config('services.orthanc.user');
        $pass = config('services.orthanc.password');

        if (empty($baseUrl) || empty($user) || empty($pass)) {
            return [];
        }

        $url = rtrim((string) $baseUrl, '/');

        try {
            $response = Http::timeout(3)->withBasicAuth((string) $user, (string) $pass)->get("{$url}/system");

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getReverbMetrics(): array
    {
        return [
            'driver' => config('broadcasting.default'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme'),
        ];
    }

    // ── Sync-freshness checkers (W3-T03) ─────────────────────────────────
    // Surface admin-visible stale/error states for the knowledge-base and
    // DICOM sync sources. Each derives recency from the best available "last
    // successful sync" signal on the relevant table. Status semantics:
    //   healthy  → latest sync within the configured freshness window
    //   degraded → latest sync older than the window (stale)
    //   unknown  → no sync has ever run (empty table / null timestamp)
    //   down     → an actual exception occurred while querying

    /**
     * OncoKB gene-drug interactions. Recency = MAX(oncokb_last_synced_at),
     * falling back to MAX(updated_at) on clinical.gene_drug_interactions.
     *
     * @return array<string, mixed>
     */
    private function checkOncoKbSync(): array
    {
        return $this->freshnessFromTable(
            name: 'OncoKB Sync',
            key: 'oncokb_sync',
            table: 'clinical.gene_drug_interactions',
            columns: ['oncokb_last_synced_at', 'updated_at'],
            windowDays: (int) config('services.sync_freshness.oncokb_days', 30),
            label: 'gene-drug interactions',
        );
    }

    /**
     * ClinGen Gene-Disease Validity. Recency = MAX(last_checked_at), falling
     * back to MAX(updated_at) on clinical.clingen_gene_validity.
     *
     * @return array<string, mixed>
     */
    private function checkClinGenSync(): array
    {
        return $this->freshnessFromTable(
            name: 'ClinGen Sync',
            key: 'clingen_sync',
            table: 'clinical.clingen_gene_validity',
            columns: ['last_checked_at', 'updated_at'],
            windowDays: (int) config('services.sync_freshness.clingen_days', 30),
            label: 'gene-disease validity curations',
        );
    }

    /**
     * ClinVar. Recency = latest completed sync in clinical.clinvar_sync_log
     * (the same source surfaced by GenomicsController::clinvarStatus).
     *
     * @return array<string, mixed>
     */
    private function checkClinVarSync(): array
    {
        $name = 'ClinVar Sync';
        $key = 'clinvar_sync';
        $windowDays = (int) config('services.sync_freshness.clinvar_days', 30);

        try {
            $latest = DB::table('clinical.clinvar_sync_log')
                ->where('status', 'completed')
                ->whereNotNull('finished_at')
                ->max('finished_at');

            $variants = (int) DB::table('clinical.clinvar_variants')->count();

            return $this->freshnessStatus($name, $key, $latest, $variants, $windowDays, 'variants');
        } catch (\Throwable $e) {
            return $this->downStatus($name, $key, $e);
        }
    }

    /**
     * DICOM sync. Recency = latest finished ingestion run in
     * clinical.imaging_ingestion_runs, falling back to MAX(updated_at) on
     * clinical.imaging_studies. Count = number of indexed imaging studies.
     *
     * @return array<string, mixed>
     */
    private function checkDicomSync(): array
    {
        $name = 'DICOM Sync';
        $key = 'dicom_sync';
        $windowDays = (int) config('services.sync_freshness.dicom_days', 7);

        try {
            $latest = DB::table('clinical.imaging_ingestion_runs')
                ->whereNotNull('finished_at')
                ->max('finished_at');

            if ($latest === null) {
                $latest = DB::table('clinical.imaging_studies')->max('updated_at')
                    ?? DB::table('clinical.imaging_studies')->max('created_at');
            }

            $studies = (int) DB::table('clinical.imaging_studies')->count();

            return $this->freshnessStatus($name, $key, $latest, $studies, $windowDays, 'imaging studies');
        } catch (\Throwable $e) {
            return $this->downStatus($name, $key, $e);
        }
    }

    /**
     * Derive a freshness status from the latest non-null value across one or
     * more timestamp columns on a single table (first non-null wins).
     *
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function freshnessFromTable(
        string $name,
        string $key,
        string $table,
        array $columns,
        int $windowDays,
        string $label,
    ): array {
        try {
            $count = (int) DB::table($table)->count();

            $latest = null;
            foreach ($columns as $column) {
                $latest = DB::table($table)->max($column);
                if ($latest !== null) {
                    break;
                }
            }

            return $this->freshnessStatus($name, $key, $latest, $count, $windowDays, $label);
        } catch (\Throwable $e) {
            return $this->downStatus($name, $key, $e);
        }
    }

    /**
     * Build the status payload from a resolved latest timestamp + row count.
     *
     * @param  \DateTimeInterface|string|null  $latest
     * @return array<string, mixed>
     */
    private function freshnessStatus(
        string $name,
        string $key,
        $latest,
        int $count,
        int $windowDays,
        string $label,
    ): array {
        if ($latest === null || $count === 0) {
            return [
                'name' => $name,
                'key' => $key,
                'status' => 'unknown',
                'message' => "No sync recorded yet ({$count} {$label}).",
                'details' => ['count' => $count, 'last_sync' => null, 'window_days' => $windowDays],
            ];
        }

        $latestAt = $latest instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($latest)
            : \Illuminate\Support\Carbon::parse((string) $latest);

        $ageDays = $latestAt->diffInDays(now());
        $stale = $latestAt->lt(now()->subDays($windowDays));
        $iso = $latestAt->toIso8601String();

        return [
            'name' => $name,
            'key' => $key,
            'status' => $stale ? 'degraded' : 'healthy',
            'message' => $stale
                ? "Stale: last sync {$iso} (~{$ageDays}d ago, window {$windowDays}d), {$count} {$label}."
                : "Last sync {$iso} (~{$ageDays}d ago), {$count} {$label}.",
            'details' => [
                'count' => $count,
                'last_sync' => $iso,
                'age_days' => $ageDays,
                'window_days' => $windowDays,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function downStatus(string $name, string $key, \Throwable $e): array
    {
        return [
            'name' => $name,
            'key' => $key,
            'status' => 'down',
            'message' => $e->getMessage(),
        ];
    }

    /**
     * Latest timestamp + row count metrics for a simple table-backed sync.
     *
     * @param  list<string>|string  $columns
     * @return array<string, mixed>
     */
    private function getSyncMetrics(string $table, array|string $columns): array
    {
        try {
            $count = (int) DB::table($table)->count();

            $latest = null;
            foreach ((array) $columns as $column) {
                $latest = DB::table($table)->max($column);
                if ($latest !== null) {
                    break;
                }
            }

            return [
                'count' => $count,
                'last_sync' => $latest !== null
                    ? \Illuminate\Support\Carbon::parse((string) $latest)->toIso8601String()
                    : null,
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getClinVarSyncMetrics(): array
    {
        try {
            $latest = DB::table('clinical.clinvar_sync_log')
                ->where('status', 'completed')
                ->whereNotNull('finished_at')
                ->max('finished_at');

            return [
                'count' => (int) DB::table('clinical.clinvar_variants')->count(),
                'last_sync' => $latest !== null
                    ? \Illuminate\Support\Carbon::parse((string) $latest)->toIso8601String()
                    : null,
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDicomSyncMetrics(): array
    {
        try {
            $latest = DB::table('clinical.imaging_ingestion_runs')
                ->whereNotNull('finished_at')
                ->max('finished_at')
                ?? DB::table('clinical.imaging_studies')->max('updated_at');

            return [
                'count' => (int) DB::table('clinical.imaging_studies')->count(),
                'last_sync' => $latest !== null
                    ? \Illuminate\Support\Carbon::parse((string) $latest)->toIso8601String()
                    : null,
            ];
        } catch (\Throwable) {
            return [];
        }
    }
}
