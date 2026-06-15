<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        'api_key' => env('RESEND_API_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'base_url' => env('AI_SERVICE_URL', 'http://localhost:8100'),
    ],

    'oncokb' => [
        'token' => env('ONCOKB_API_TOKEN'),
    ],

    'clingen_ar' => [
        'base' => env('CLINGEN_AR_BASE', 'https://reg.clinicalgenome.org'),
        'login' => env('CLINGEN_AR_LOGIN'),
        'password' => env('CLINGEN_AR_PASSWORD'),
    ],

    'anyvar' => [
        'url' => env('ANYVAR_URL'),
    ],

    'oidc' => [
        'enabled' => filter_var(env('OIDC_ENABLED', false), FILTER_VALIDATE_BOOL),
        'discovery_url' => env('OIDC_DISCOVERY_URL', 'https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration'),
        'client_id' => env('OIDC_CLIENT_ID', ''),
        'client_secret' => env('OIDC_CLIENT_SECRET', ''),
        'redirect_uri' => env('OIDC_REDIRECT_URI', 'https://aurora.acumenus.net/api/auth/oidc/callback'),
        'scopes' => ['openid', 'profile', 'email', 'groups'],
        'allowed_groups' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OIDC_ALLOWED_GROUPS', 'Aurora Admins'))
        ))),
    ],

];
