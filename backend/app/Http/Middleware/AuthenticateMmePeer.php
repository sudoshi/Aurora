<?php

namespace App\Http\Middleware;

use App\Models\MmePeer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMmePeer
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-Auth-Token', '');
        $peer = $token === '' ? null : MmePeer::query()->active()->get()
            ->first(fn ($p) => hash_equals((string) $p->auth_token, $token));

        if (! $peer) {
            return response()->json(['message' => 'Unauthorized: invalid or missing X-Auth-Token.'], 401);
        }

        $peer->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('mmePeer', $peer);

        return $next($request);
    }
}
