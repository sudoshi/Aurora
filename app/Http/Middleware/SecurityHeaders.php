<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Only add HSTS header if the request is over HTTPS
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Add CSP header with development-friendly settings in local environment
        if (app()->environment('local')) {
            $response->headers->set('Content-Security-Policy', "
                default-src 'self';
                script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173;
                style-src 'self' 'unsafe-inline';
                connect-src 'self' ws://localhost:5173 http://localhost:5173;
                img-src 'self' data: blob:;
                font-src 'self';
                object-src 'none';
                base-uri 'self';
                form-action 'self';
                frame-ancestors 'none';
                block-all-mixed-content;
                require-trusted-types-for 'script';
            ");
        } else {
            $response->headers->set('Content-Security-Policy', "
                default-src 'self';
                script-src 'self' 'unsafe-inline' 'unsafe-eval';
                style-src 'self' 'unsafe-inline';
                img-src 'self' data: blob:;
                font-src 'self';
                object-src 'none';
                base-uri 'self';
                form-action 'self';
                frame-ancestors 'none';
                block-all-mixed-content;
                require-trusted-types-for 'script';
            ");
        }

        return $response;
    }
}
