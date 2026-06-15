<?php

use App\Services\Auth\Oidc\OidcHandshakeStore;

beforeEach(function () {
    $this->store = new OidcHandshakeStore;
});

describe('OIDC handshake state', function () {
    it('round-trips state and is single-use', function () {
        $state = $this->store->putState(['nonce' => 'n-abc', 'code_verifier' => 'v-xyz']);
        expect($state)->not->toBe('');

        expect($this->store->consumeState($state))
            ->toBe(['nonce' => 'n-abc', 'code_verifier' => 'v-xyz']);

        // Second consume must fail — single use.
        expect($this->store->consumeState($state))->toBeNull();
    });

    it('returns null for an unknown state', function () {
        expect($this->store->consumeState('never-issued'))->toBeNull();
    });
});

describe('OIDC handshake exchange code', function () {
    it('round-trips an exchange code and is single-use', function () {
        $code = $this->store->putCode(42, 'plain-text-sanctum-token');
        expect($code)->not->toBe('');

        expect($this->store->consumeCode($code))
            ->toBe(['user_id' => 42, 'token' => 'plain-text-sanctum-token']);

        expect($this->store->consumeCode($code))->toBeNull();
    });

    it('returns null for an unknown code', function () {
        expect($this->store->consumeCode('never-issued'))->toBeNull();
    });
});
