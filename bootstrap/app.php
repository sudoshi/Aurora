<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->prefix('api')->group(function () {
                Route::get('/health', function () {
                    return response()->json([
                        'status' => 'ok',
                        'timestamp' => now()->toIso8601String(),
                    ]);
                });
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
