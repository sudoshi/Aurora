<?php

namespace Database\Seeders;

use App\Models\Auth\AuthProviderSetting;
use Illuminate\Database\Seeder;

class AuthProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'provider_type' => 'ldap',
                'display_name' => 'LDAP / Active Directory',
                'is_enabled' => false,
                'priority' => 10,
                'settings' => [
                    'host' => '',
                    'port' => 389,
                    'base_dn' => '',
                    'bind_dn' => '',
                    'bind_password' => '',
                    'user_search_base' => '',
                    'user_filter' => '(uid={username})',
                    'username_field' => 'uid',
                    'email_field' => 'mail',
                    'name_field' => 'cn',
                    'group_sync' => false,
                    'group_search_base' => '',
                    'group_filter' => '(objectClass=groupOfNames)',
                    'use_ssl' => false,
                    'use_tls' => false,
                    'timeout' => 5,
                ],
            ],
            [
                'provider_type' => 'oauth2',
                'display_name' => 'OAuth 2.0',
                'is_enabled' => false,
                'priority' => 20,
                'settings' => [
                    'driver' => 'custom',
                    'client_id' => '',
                    'client_secret' => '',
                    'redirect_uri' => '/api/auth/oauth2/callback',
                    'scopes' => ['openid', 'profile', 'email'],
                    'auth_url' => '',
                    'token_url' => '',
                    'userinfo_url' => '',
                ],
            ],
            [
                'provider_type' => 'saml2',
                'display_name' => 'SAML 2.0',
                'is_enabled' => false,
                'priority' => 30,
                'settings' => [
                    'idp_entity_id' => '',
                    'idp_sso_url' => '',
                    'idp_slo_url' => '',
                    'idp_certificate' => '',
                    'sp_entity_id' => '',
                    'sp_acs_url' => '/api/auth/saml2/callback',
                    'sp_slo_url' => '/api/auth/saml2/logout',
                    'name_id_format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                    'sign_assertions' => false,
                    'attribute_mapping' => [
                        'email' => 'email',
                        'name' => 'displayName',
                    ],
                ],
            ],
            [
                'provider_type' => 'oidc',
                'display_name' => 'Authentik OpenID Connect',
                'is_enabled' => false,
                'priority' => 40,
                'settings' => [
                    'client_id' => '',
                    'client_secret' => '',
                    'discovery_url' => 'https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration',
                    // Must be ABSOLUTE — Authentik strict redirect-URI matching rejects a
                    // relative path with "Redirect URI Error" (fixed 2026-06-22).
                    'redirect_uri' => 'https://aurora.acumenus.net/api/auth/oidc/callback',
                    'scopes' => ['openid', 'profile', 'email', 'groups'],
                    'allowed_groups' => ['Aurora Admins'],
                    'pkce_enabled' => true,
                ],
            ],
        ];

        foreach ($providers as $data) {
            AuthProviderSetting::query()->firstOrCreate(
                ['provider_type' => $data['provider_type']],
                $data,
            );
        }
    }
}
