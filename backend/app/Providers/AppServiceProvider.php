<?php

namespace App\Providers;

use App\Models\Commons\Channel;
use App\Policies\Commons\ChannelPolicy;
use App\Services\Auth\Oidc\OidcDiscoveryService;
use App\Services\Auth\Oidc\OidcHandshakeStore;
use App\Services\Auth\Oidc\OidcProviderConfig;
use App\Services\Auth\Oidc\OidcReconciliationService;
use App\Services\Auth\Oidc\OidcTokenValidator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OidcProviderConfig::class);

        $this->app->bind(OidcDiscoveryService::class, fn ($app) => new OidcDiscoveryService(
            $app->make(OidcProviderConfig::class)->discoveryUrl()
        ));

        $this->app->bind(OidcTokenValidator::class, fn ($app) => new OidcTokenValidator(
            $app->make(OidcDiscoveryService::class),
            $app->make(OidcProviderConfig::class)->clientId()
        ));

        $this->app->bind(OidcReconciliationService::class, fn ($app) => new OidcReconciliationService(
            $app->make(OidcProviderConfig::class)->allowedGroups()
        ));

        $this->app->singleton(OidcHandshakeStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Channel::class, ChannelPolicy::class);
        $this->verifyRequiredSecrets();
    }

    /**
     * Validate that required runtime secrets are present.
     *
     * Per the project philosophy — fail loudly in dev, gracefully in prod:
     * a missing secret throws during local/staging boot but only logs in
     * production so a misconfiguration is surfaced without crashing the site.
     * Skipped on the console (migrations, queue, tinker) and under testing.
     */
    private function verifyRequiredSecrets(): void
    {
        if ($this->app->runningInConsole() || $this->app->environment('testing')) {
            return;
        }

        $missing = [];

        if (empty(config('app.key'))) {
            $missing[] = 'APP_KEY';
        }

        // Temp-password / auth emails depend on Resend in production.
        if ($this->app->environment('production') && empty(config('services.resend.api_key'))) {
            $missing[] = 'RESEND_API_KEY';
        }

        // Realtime broadcasting needs matching app credentials when enabled.
        if (config('broadcasting.default') === 'reverb') {
            $reverb = [
                'REVERB_APP_ID' => config('broadcasting.connections.reverb.app_id'),
                'REVERB_APP_KEY' => config('broadcasting.connections.reverb.key'),
                'REVERB_APP_SECRET' => config('broadcasting.connections.reverb.secret'),
            ];
            foreach ($reverb as $name => $value) {
                if (empty($value)) {
                    $missing[] = $name;
                }
            }
        }

        if ($missing === []) {
            return;
        }

        $message = 'Missing required configuration: '.implode(', ', $missing);
        Log::error('[startup] '.$message);

        if (! $this->app->environment('production')) {
            throw new \RuntimeException($message.' — set these in backend/.env.');
        }
    }
}
