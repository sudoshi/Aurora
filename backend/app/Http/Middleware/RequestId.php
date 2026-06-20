<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Assigns a stable correlation id to every request so all log lines emitted
 * during the request carry a shared `request_id`. The id is also exposed on the
 * response (X-Request-Id) for client/operator cross-referencing.
 *
 * Foundation of W3-T04 (structured logging).
 */
class RequestId
{
    /**
     * Maximum accepted length for an upstream-provided request id.
     */
    private const MAX_LENGTH = 64;

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $id = $this->resolveId($request);

        // Make the id available to downstream code/audit via the request itself.
        $request->headers->set('X-Request-Id', $id);

        // Share into the log context so every subsequent log entry includes it.
        // Must never break the request if the logging subsystem misbehaves.
        try {
            Log::shareContext(['request_id' => $id]);
        } catch (\Throwable $e) {
            // Intentionally swallowed: correlation id is best-effort.
        }

        $response = $next($request);

        $response->headers->set('X-Request-Id', $id);

        return $response;
    }

    /**
     * Resolve the request id from a trusted upstream header, or generate one.
     */
    private function resolveId(Request $request): string
    {
        $incoming = $request->headers->get('X-Request-Id');

        if (is_string($incoming) && $incoming !== '') {
            // Restrict to a safe charset to avoid log injection, then cap length.
            $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '', $incoming);
            $sanitized = substr((string) $sanitized, 0, self::MAX_LENGTH);

            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        return (string) Str::uuid();
    }
}
