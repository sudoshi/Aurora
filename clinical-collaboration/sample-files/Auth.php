<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Case;
use Illuminate\Auth\Access\HandlesAuthorization;

class CasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Users can see cases they have access to
    }

    public function view(User $user, Case $case)
    {
        return $user->teams()
            ->whereHas('cases', function ($query) use ($case) {
                $query->where('cases.id', $case->id);
            })
            ->exists();
    }

    public function create(User $user)
    {
        return $user->hasPermission('create_cases');
    }

    public function update(User $user, Case $case)
    {
        return $user->teams()
            ->whereHas('cases', function ($query) use ($case) {
                $query->where('cases.id', $case->id);
            })
            ->wherePivot('role', 'lead')
            ->exists();
    }

    public function delete(User $user, Case $case)
    {
        return $user->hasRole('admin') || 
            ($user->teams()
                ->whereHas('cases', function ($query) use ($case) {
                    $query->where('cases.id', $case->id);
                })
                ->wherePivot('role', 'lead')
                ->exists());
    }
}

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLog
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Log all significant actions
        if ($this->shouldLogRequest($request)) {
            Log::channel('audit')->info('User Action', [
                'user_id' => auth()->id(),
                'action' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);
        }

        return $response;
    }

    private function shouldLogRequest(Request $request)
    {
        return !$request->isMethod('GET') || 
               $request->is('api/cases/*') ||
               $request->is('api/sessions/*');
    }
}

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $key = sprintf('rate_limit:%s:%s', $request->ip(), time());
        $limit = 100; // requests
        $interval = 60; // seconds

        $count = Cache::get($key, 0);
        
        if ($count >= $limit) {
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $interval - (time() % $interval)
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        Cache::add($key, 0, $interval);
        Cache::increment($key);

        return $next($request);
    }
}

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonResource::withoutWrapping();

        Sanctum::usePersonalAccessTokenModel(
            \App\Models\PersonalAccessToken::class
        );

        // Set secure cookie in production
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
            \Cookie::setDefaultSecure(true);
        }
    }
}

// config/session.php additions
return [
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'same_site' => 'lax',
    'http_only' => true,
];

// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];