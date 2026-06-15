<?php

namespace App\Services\Auth\Oidc;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OidcHandshakeStore
{
    private const STATE_TTL = 300;

    private const CODE_TTL = 60;

    private const STATE_PREFIX = 'oidc:state:';

    private const CODE_PREFIX = 'oidc:code:';

    /**
     * @param  array{nonce: string, code_verifier: string}  $meta
     */
    public function putState(array $meta): string
    {
        $state = Str::random(48);
        Cache::put(self::STATE_PREFIX.$state, $meta, self::STATE_TTL);

        return $state;
    }

    /**
     * @return array{nonce: string, code_verifier: string}|null
     */
    public function consumeState(string $state): ?array
    {
        /** @var array{nonce: string, code_verifier: string}|null $meta */
        $meta = Cache::pull(self::STATE_PREFIX.$state);

        return $meta;
    }

    public function putCode(int $userId, string $token): string
    {
        $code = Str::random(48);
        Cache::put(self::CODE_PREFIX.$code, [
            'user_id' => $userId,
            'token' => $token,
        ], self::CODE_TTL);

        return $code;
    }

    /**
     * @return array{user_id: int, token: string}|null
     */
    public function consumeCode(string $code): ?array
    {
        /** @var array{user_id: int, token: string}|null $payload */
        $payload = Cache::pull(self::CODE_PREFIX.$code);

        return $payload;
    }
}
