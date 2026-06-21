<?php

namespace App\Http\Controllers;

use App\Models\ClinicalCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Prometheus-scrapeable metrics endpoint (W3-T05).
 *
 * Emits the Prometheus text exposition format (version 0.0.4) computed
 * point-in-time on each scrape. Every probe is individually guarded so a downed
 * dependency yields a 0/-1 gauge rather than failing the scrape — the handler
 * must never 500.
 *
 * TODO(W3-T05): request-latency/error-rate histograms need a Redis-backed
 * prometheus client (PHP-FPM workers don't share memory, so latency histograms
 * require cross-request shared storage). Point-in-time gauges only for now.
 */
class MetricsController extends Controller
{
    public function index(Request $request): Response
    {
        // Optional bearer-token gate. Prometheus scrapers send a static token,
        // not a session — so this is NOT behind auth:sanctum. When the token is
        // configured (env METRICS_TOKEN) a matching bearer is required; when
        // unset, the endpoint is open for dev/local convenience. Production
        // should set the token AND restrict scraping by network.
        $token = config('services.metrics.token');
        if (! empty($token) && ! hash_equals((string) $token, (string) $request->bearerToken())) {
            return response("# forbidden\n", 403)
                ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        }

        $lines = [];

        $this->gauge($lines, 'aurora_up', 'Whether the Aurora API responded to the scrape.', 1);

        $version = (string) config('app.version', '2.0.0');
        $lines[] = '# HELP aurora_build_info Build information exposed as a constant gauge with a version label.';
        $lines[] = '# TYPE aurora_build_info gauge';
        $lines[] = 'aurora_build_info{version="'.$this->escapeLabel($version).'"} 1';

        $this->gauge(
            $lines,
            'aurora_queue_pending_jobs',
            'Number of pending jobs in the database queue (queue depth). -1 if unavailable.',
            $this->safeCount(fn () => DB::table((string) config('queue.connections.database.table', 'jobs'))->count()),
        );

        $this->gauge(
            $lines,
            'aurora_failed_jobs',
            'Number of failed jobs recorded in the failed_jobs table. -1 if unavailable.',
            $this->safeCount(fn () => DB::table((string) config('queue.failed.table', 'failed_jobs'))->count()),
        );

        $dependencies = [
            'database' => fn () => DB::connection()->getPdo() !== null,
            'redis' => fn () => (bool) Redis::ping(),
            'cache' => function () {
                cache()->put('metrics:probe', '1', 5);

                return cache()->get('metrics:probe') === '1';
            },
        ];

        $lines[] = '# HELP aurora_dependency_up Whether a hard dependency is reachable (1) or down (0).';
        $lines[] = '# TYPE aurora_dependency_up gauge';
        foreach ($dependencies as $name => $probe) {
            $up = $this->probe($probe) ? 1 : 0;
            $lines[] = 'aurora_dependency_up{dependency="'.$this->escapeLabel($name).'"} '.$up;
        }

        $users = $this->safeCount(fn () => User::query()->count());
        if ($users >= 0) {
            $this->gauge($lines, 'aurora_users_total', 'Total number of registered users.', $users);
        }

        $activeCases = $this->safeCount(fn () => ClinicalCase::query()->where('status', 'active')->count());
        if ($activeCases >= 0) {
            $this->gauge($lines, 'aurora_active_cases', 'Number of clinical cases with status=active.', $activeCases);
        }

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }

    /**
     * Append a single-value gauge (HELP + TYPE + value) to the output.
     *
     * @param  list<string>  $lines
     */
    private function gauge(array &$lines, string $name, string $help, int $value): void
    {
        $lines[] = '# HELP '.$name.' '.$help;
        $lines[] = '# TYPE '.$name.' gauge';
        $lines[] = $name.' '.$value;
    }

    /**
     * Run a count probe, returning -1 on any failure so the scrape never breaks.
     *
     * @param  callable(): int  $probe
     */
    private function safeCount(callable $probe): int
    {
        try {
            return (int) $probe();
        } catch (\Throwable) {
            return -1;
        }
    }

    /**
     * Run a boolean liveness probe, returning false on any failure.
     *
     * @param  callable(): bool  $probe
     */
    private function probe(callable $probe): bool
    {
        try {
            return (bool) $probe();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Escape a Prometheus label value: backslash, double-quote, and newline.
     */
    private function escapeLabel(string $value): string
    {
        return str_replace(
            ['\\', '"', "\n"],
            ['\\\\', '\\"', '\\n'],
            $value,
        );
    }
}
