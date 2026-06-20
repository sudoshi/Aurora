<?php

namespace App\Http\Middleware;

use App\Models\UserAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every access to PHI-bearing endpoints into the user audit trail.
 *
 * Aurora uses an "open clinical workspace" access model: any authenticated
 * clinical user may read patient/genomics/imaging/diagnostic-odyssey records.
 * The agreed compensating control is audit logging of that access. Reads
 * (GET/HEAD) are recorded as action 'phi.access'; mutations are recorded as
 * 'phi.write', so auditors can filter on the action column directly.
 *
 * The audit write is performed after the controller has run and is fully
 * guarded: any failure (including a poisoned Postgres transaction,
 * SQLSTATE[25P02], or the database being unreachable) is swallowed so that
 * auditing can never alter or break the response delivered to the client.
 */
class LogPhiAccess
{
    /** Max stored length for the user agent string. */
    private const USER_AGENT_MAX = 1024;

    public function handle(Request $request, Closure $next, string $feature = 'phi'): Response
    {
        $response = $next($request);

        // Only audit successful access by an authenticated user.
        $user = $request->user();
        if ($user !== null && $response->getStatusCode() < 400) {
            $this->writeAudit($request, $user->id, $feature);
        }

        return $response;
    }

    /**
     * Write a single audit row, swallowing any error.
     *
     * Auditing must never break a clinical request, so every failure mode —
     * a poisoned request transaction (SQLSTATE[25P02]), a downed connection,
     * or any other Throwable — is caught and (best-effort) logged only.
     */
    private function writeAudit(Request $request, mixed $userId, string $feature): void
    {
        try {
            $userAgent = $request->userAgent();
            $route = $request->route();

            // Distinguish reads from mutations so auditors can filter on the
            // action column without parsing the metadata JSON. GET/HEAD are
            // reads; everything else (POST/PUT/PATCH/DELETE) is a write.
            $action = $request->isMethod('GET') || $request->isMethod('HEAD')
                ? 'phi.access'
                : 'phi.write';

            UserAuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'feature' => $feature,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent !== null
                    ? mb_substr($userAgent, 0, self::USER_AGENT_MAX)
                    : null,
                'metadata' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'route_params' => $route !== null ? $route->parameters() : [],
                ],
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            try {
                Log::warning('PHI access audit write failed', [
                    'feature' => $feature,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
                // Logging itself failed; nothing more we can safely do.
            }
        }
    }
}
