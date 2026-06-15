<?php

namespace App\Services\Auth\Oidc;

use App\Models\Auth\AuthProviderSetting;

class OidcProviderConfig
{
    /**
     * @return array{
     *     enabled: bool,
     *     display_name: string,
     *     discovery_url: string,
     *     client_id: string,
     *     client_secret: string,
     *     redirect_uri: string,
     *     scopes: list<string>,
     *     allowed_groups: list<string>
     * }
     */
    public function settings(): array
    {
        $provider = $this->provider();
        $stored = $provider?->settings ?? [];

        return [
            'enabled' => $this->enabled($provider),
            'display_name' => $provider?->display_name ?? 'Sign in with Authentik',
            'discovery_url' => $this->stringSetting($stored, 'discovery_url', (string) config('services.oidc.discovery_url', '')),
            'client_id' => $this->stringSetting($stored, 'client_id', (string) config('services.oidc.client_id', '')),
            'client_secret' => $this->stringSetting($stored, 'client_secret', (string) config('services.oidc.client_secret', '')),
            'redirect_uri' => $this->stringSetting($stored, 'redirect_uri', (string) config('services.oidc.redirect_uri', '')),
            'scopes' => $this->listSetting($stored, 'scopes', (array) config('services.oidc.scopes', ['openid', 'profile', 'email'])),
            'allowed_groups' => $this->listSetting($stored, 'allowed_groups', (array) config('services.oidc.allowed_groups', ['Aurora Admins'])),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->settings()['enabled'];
    }

    public function isPubliclyAvailable(): bool
    {
        $settings = $this->settings();

        return $settings['enabled']
            && $settings['discovery_url'] !== ''
            && $settings['client_id'] !== ''
            && $settings['redirect_uri'] !== '';
    }

    public function discoveryUrl(): string
    {
        return $this->settings()['discovery_url'];
    }

    public function clientId(): string
    {
        return $this->settings()['client_id'];
    }

    public function clientSecret(): string
    {
        return $this->settings()['client_secret'];
    }

    public function redirectUri(): string
    {
        return $this->settings()['redirect_uri'];
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->settings()['scopes'];
    }

    /**
     * @return list<string>
     */
    public function allowedGroups(): array
    {
        return $this->settings()['allowed_groups'];
    }

    private function provider(): ?AuthProviderSetting
    {
        try {
            return AuthProviderSetting::query()
                ->where('provider_type', 'oidc')
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }

    private function enabled(?AuthProviderSetting $provider): bool
    {
        return (bool) config('services.oidc.enabled', false)
            || (bool) ($provider?->is_enabled ?? false);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function stringSetting(array $settings, string $key, string $fallback): string
    {
        $value = $settings[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>|array<int, mixed>  $fallback
     * @return list<string>
     */
    private function listSetting(array $settings, string $key, array $fallback): array
    {
        $value = $settings[$key] ?? null;
        $items = is_array($value) ? $value : $fallback;

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_string($item) ? trim($item) : '',
            $items,
        )));
    }
}
