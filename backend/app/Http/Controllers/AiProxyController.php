<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Proxies requests from the frontend to the AI (FastAPI) service.
 *
 * The frontend calls /api/ai/* which Laravel forwards to the FastAPI
 * service running on the configured AI_SERVICE_URL.
 */
class AiProxyController extends Controller
{
    /**
     * Proxy a POST request to the AI service.
     */
    public function proxy(Request $request, string $path)
    {
        $aiBaseUrl = config('services.ai.base_url', 'http://localhost:8100');
        $url = rtrim($aiBaseUrl, '/').'/api/ai/'.ltrim($path, '/');

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-User-Id' => (string) $request->user()?->id,
                    'X-User-Name' => $request->user()?->name ?? '',
                    'X-User-Roles' => implode(',', $request->user()?->roles?->pluck('name')->toArray() ?? []),
                ])
                ->post($url, $request->all());

            return response()->json(
                $response->json(),
                $response->status()
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'error' => 'AI service unavailable',
                'message' => 'The AI service is not responding. Please try again later.',
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI service error',
                'message' => 'An error occurred while communicating with the AI service.',
            ], 500);
        }
    }

    /**
     * Proxy a GET request to the AI service.
     */
    public function proxyGet(Request $request, string $path)
    {
        $aiBaseUrl = config('services.ai.base_url', 'http://localhost:8100');
        $url = rtrim($aiBaseUrl, '/').'/api/ai/'.ltrim($path, '/');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-User-Id' => (string) $request->user()?->id,
                ])
                ->get($url, $request->query());

            return response()->json(
                $response->json(),
                $response->status()
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI service unavailable',
            ], 503);
        }
    }
}
