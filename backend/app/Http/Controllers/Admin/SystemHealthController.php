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
}
