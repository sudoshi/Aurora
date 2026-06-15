<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\AuthProviderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthProviderController extends Controller
{
    private const TYPES = ['ldap', 'oauth2', 'saml2', 'oidc'];

    /**
     * Sentinel returned in place of stored secret values so cleartext
     * secrets never reach the browser. Submitting it back on update is
     * treated as "unchanged" so the real secret is preserved.
     */
    private const SECRET_MASK = '__stored__';

    private const SECRET_KEY_PATTERN = '/(secret|password|private_key)/i';

    public function index(): JsonResponse
    {
        $providers = AuthProviderSetting::query()
            ->orderBy('priority')
            ->get()
            ->map(fn (AuthProviderSetting $provider) => $this->present($provider))
            ->all();

        return response()->json($providers);
    }

    public function show(string $providerType): JsonResponse
    {
        $this->assertProviderType($providerType);

        return response()->json($this->present(
            AuthProviderSetting::query()->where('provider_type', $providerType)->firstOrFail()
        ));
    }

    public function update(Request $request, string $providerType): JsonResponse
    {
        $this->assertProviderType($providerType);

        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:100',
            'is_enabled' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'settings' => 'sometimes|array',
        ]);

        $provider = AuthProviderSetting::query()->where('provider_type', $providerType)->firstOrFail();

        if (isset($validated['settings'])) {
            $validated['settings'] = array_merge(
                $provider->settings ?? [],
                $this->stripUnchangedSecrets($validated['settings']),
            );
        }

        $provider->fill(array_merge($validated, ['updated_by' => $request->user()->id]));
        $provider->save();

        return response()->json($this->present($provider->fresh()));
    }

    public function enable(Request $request, string $providerType): JsonResponse
    {
        $this->assertProviderType($providerType);

        $provider = AuthProviderSetting::query()->where('provider_type', $providerType)->firstOrFail();
        $provider->update(['is_enabled' => true, 'updated_by' => $request->user()->id]);

        return response()->json($this->present($provider->fresh()));
    }

    public function disable(Request $request, string $providerType): JsonResponse
    {
        $this->assertProviderType($providerType);

        $provider = AuthProviderSetting::query()->where('provider_type', $providerType)->firstOrFail();
        $provider->update(['is_enabled' => false, 'updated_by' => $request->user()->id]);

        return response()->json($this->present($provider->fresh()));
    }

    public function test(string $providerType): JsonResponse
    {
        $this->assertProviderType($providerType);

        $provider = AuthProviderSetting::query()->where('provider_type', $providerType)->firstOrFail();

        $result = match ($providerType) {
            'ldap' => $this->testLdap($provider->settings ?? []),
            'oidc' => $this->testOidc($provider->settings ?? []),
            default => ['success' => false, 'message' => "Connection test not available for {$providerType}."],
        };

        return response()->json($result);
    }

    private function assertProviderType(string $providerType): void
    {
        abort_unless(in_array($providerType, self::TYPES, true), 404);
    }

    /**
     * Serialize a provider for API responses with secret values masked so
     * cleartext credentials never reach the client.
     *
     * @return array<string, mixed>
     */
    private function present(AuthProviderSetting $provider): array
    {
        $data = $provider->toArray();
        $data['settings'] = $this->maskSecrets($provider->settings ?? []);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function maskSecrets(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if ($this->isSecretKey($key) && is_string($value) && $value !== '') {
                $settings[$key] = self::SECRET_MASK;
            }
        }

        return $settings;
    }

    /**
     * Drop secret keys whose submitted value is the mask sentinel (or blank)
     * so a round-tripped masked form never overwrites the stored secret.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function stripUnchangedSecrets(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if ($this->isSecretKey($key) && ($value === self::SECRET_MASK || $value === '' || $value === null)) {
                unset($settings[$key]);
            }
        }

        return $settings;
    }

    private function isSecretKey(int|string $key): bool
    {
        return is_string($key) && preg_match(self::SECRET_KEY_PATTERN, $key) === 1;
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array<string, mixed>
     */
    private function testLdap(array $cfg): array
    {
        if (! function_exists('ldap_connect')) {
            return ['success' => false, 'message' => 'LDAP PHP extension is not installed.'];
        }

        if (empty($cfg['host'])) {
            return ['success' => false, 'message' => 'LDAP host is not configured.'];
        }

        $host = (string) $cfg['host'];
        $port = (int) ($cfg['port'] ?? 389);
        $timeout = (int) ($cfg['timeout'] ?? 5);

        $conn = @ldap_connect("ldap://{$host}:{$port}");
        if (! $conn) {
            return ['success' => false, 'message' => 'Could not create LDAP connection handle.'];
        }

        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, $timeout);
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);

        $bound = @ldap_bind($conn, $cfg['bind_dn'] ?? null, $cfg['bind_password'] ?? null);
        if (! $bound) {
            return ['success' => false, 'message' => 'LDAP bind failed: '.ldap_error($conn)];
        }

        ldap_unbind($conn);

        return ['success' => true, 'message' => "Connected and bound to {$host}:{$port} successfully."];
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array<string, mixed>
     */
    private function testOidc(array $cfg): array
    {
        $discoveryUrl = $cfg['discovery_url'] ?? config('services.oidc.discovery_url');
        if (! is_string($discoveryUrl) || $discoveryUrl === '') {
            return ['success' => false, 'message' => 'Discovery URL is not configured.'];
        }

        try {
            $response = Http::timeout(10)->get($discoveryUrl);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        if ($response->failed()) {
            return ['success' => false, 'message' => "Discovery URL returned HTTP {$response->status()}."];
        }

        $doc = $response->json();

        return [
            'success' => true,
            'message' => 'OIDC discovery document fetched successfully.',
            'details' => [
                'issuer' => $doc['issuer'] ?? null,
                'authorization_endpoint' => $doc['authorization_endpoint'] ?? null,
                'token_endpoint' => $doc['token_endpoint'] ?? null,
            ],
        ];
    }
}
