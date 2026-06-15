<?php

use App\Auth\Drivers\AuthentikOidcAuthDriver;
use App\Auth\Drivers\LocalCredentialsAuthDriver;

return [
    'local' => [
        'enabled' => filter_var(env('LOCAL_AUTH_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    'drivers' => [
        'local' => LocalCredentialsAuthDriver::class,
        'authentik-oidc' => AuthentikOidcAuthDriver::class,
    ],
];
