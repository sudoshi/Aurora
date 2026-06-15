<?php

namespace App\Http\Controllers\Auth;

use App\Auth\AuthDriverRegistry;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\Oidc\Exceptions\OidcAccessDeniedException;
use App\Services\Auth\Oidc\Exceptions\OidcException;
use App\Services\Auth\Oidc\Exceptions\OidcTokenInvalidException;
use App\Services\Auth\Oidc\OidcDiscoveryService;
use App\Services\Auth\Oidc\OidcHandshakeStore;
use App\Services\Auth\Oidc\OidcProviderConfig;
use App\Services\Auth\Oidc\OidcTokenValidator;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OidcController extends Controller
{
    public function redirect(
        OidcHandshakeStore $store,
        OidcDiscoveryService $discovery,
        OidcProviderConfig $config,
    ): Response {
        $this->ensureEnabled($config);

        try {
            $authorize = $discovery->authorizationEndpoint();
        } catch (OidcException $e) {
            return $this->oidcError('discovery_failed', $e, 503);
        }

        $nonce = Str::random(32);
        $codeVerifier = $this->generateCodeVerifier();
        $state = $store->putState([
            'nonce' => $nonce,
            'code_verifier' => $codeVerifier,
        ]);

        $params = [
            'response_type' => 'code',
            'client_id' => $config->clientId(),
            'redirect_uri' => $config->redirectUri(),
            'scope' => implode(' ', $config->scopes()),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $this->deriveCodeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ];

        return redirect()->away($authorize.'?'.http_build_query($params));
    }

    public function callback(
        Request $request,
        OidcHandshakeStore $store,
        OidcDiscoveryService $discovery,
        OidcTokenValidator $validator,
        AuthDriverRegistry $registry,
        OidcProviderConfig $config,
    ): RedirectResponse|JsonResponse {
        $this->ensureEnabled($config);

        $state = (string) $request->query('state', '');
        $authCode = (string) $request->query('code', '');

        if ($state === '' || $authCode === '') {
            return $this->oidcError('missing_parameters', null, 400);
        }

        $meta = $store->consumeState($state);
        if ($meta === null) {
            return $this->oidcError('unknown_state', null, 400);
        }

        try {
            $tokenResponse = Http::asForm()->post($discovery->tokenEndpoint(), [
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'redirect_uri' => $config->redirectUri(),
                'client_id' => $config->clientId(),
                'client_secret' => $config->clientSecret(),
                'code_verifier' => $meta['code_verifier'],
            ]);
        } catch (\Throwable $e) {
            return $this->oidcError('token_exchange_failed', $e, 502);
        }

        if ($tokenResponse->failed()) {
            return $this->oidcError('token_exchange_failed', null, 502);
        }

        $idToken = (string) ($tokenResponse->json('id_token') ?? '');
        if ($idToken === '') {
            return $this->oidcError('missing_id_token', null, 502);
        }

        try {
            $claims = $validator->validate($idToken, $meta['nonce']);
        } catch (OidcTokenInvalidException $e) {
            return $this->oidcError($e->reason, $e, 401);
        }

        try {
            $authResult = $registry->driver('authentik-oidc')->authenticate([
                'claims' => $claims,
            ]);
        } catch (OidcAccessDeniedException $e) {
            return $this->oidcError($e->reason, $e, 403);
        }

        $user = $authResult->user;
        $user->tokens()->where('name', 'auth-token')->delete();
        $token = $user->createToken('auth-token')->plainTextToken;
        $user->forceFill(['last_login_at' => now()])->save();

        $code = $store->putCode($user->id, $token);
        $frontend = rtrim((string) config('app.url'), '/');

        return redirect()->away($frontend.'/auth/callback?code='.urlencode($code));
    }

    public function exchange(
        Request $request,
        OidcHandshakeStore $store,
        AuthService $authService,
        OidcProviderConfig $config,
    ): JsonResponse {
        $this->ensureEnabled($config);

        $code = (string) $request->input('code', '');
        if ($code === '') {
            return response()->json(['error' => 'oidc_failed', 'reason' => 'missing_code'], 400);
        }

        $payload = $store->consumeCode($code);
        if ($payload === null) {
            return response()->json(['error' => 'oidc_failed', 'reason' => 'unknown_code'], 400);
        }

        $user = User::query()->with('roles.permissions')->find($payload['user_id']);
        if ($user === null) {
            return response()->json(['error' => 'oidc_failed', 'reason' => 'user_missing'], 400);
        }

        return response()->json([
            'token' => $payload['token'],
            'access_token' => $payload['token'],
            'user' => $authService->formatUser($user),
        ]);
    }

    public function providers(OidcProviderConfig $config): JsonResponse
    {
        $settings = $config->settings();

        return response()->json([
            'oidc_enabled' => $config->isPubliclyAvailable(),
            'oidc_label' => $settings['display_name'],
            'oidc_redirect_path' => '/api/auth/oidc/redirect',
        ]);
    }

    private function ensureEnabled(OidcProviderConfig $config): void
    {
        if (! $config->isPubliclyAvailable()) {
            abort(404);
        }
    }

    private function oidcError(string $reason, ?\Throwable $e, int $status): JsonResponse
    {
        if ($e !== null) {
            Log::warning('OIDC failure', [
                'reason' => $reason,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json(['error' => 'oidc_failed', 'reason' => $reason], $status);
    }

    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    private function deriveCodeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
