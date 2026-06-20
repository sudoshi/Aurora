<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Public liveness + readiness probes for load balancers, Docker healthchecks,
 * and uptime monitors. Intentionally unauthenticated and detail-light — it
 * reports per-dependency up/down but no values. The admin SystemHealthController
 * provides the richer operator view.
 */
class HealthController extends Controller
{
    /** Liveness — the process is up and serving. */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'aurora-api',
            'version' => config('app.version', '2.0.0'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness — critical dependencies are reachable. Returns 503 when any
     * hard dependency is down so orchestrators stop routing traffic.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->getPdo() !== null),
            'redis' => $this->check(fn () => (bool) Redis::ping()),
            'cache' => $this->check(function () {
                cache()->put('health:ready', '1', 5);

                return cache()->get('health:ready') === '1';
            }),
        ];

        $ready = ! in_array('down', $checks, true);

        return response()->json([
            'status' => $ready ? 'ready' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $ready ? 200 : 503);
    }

    /**
     * @param  callable(): bool  $probe
     */
    private function check(callable $probe): string
    {
        try {
            return $probe() ? 'up' : 'down';
        } catch (\Throwable) {
            return 'down';
        }
    }
}
