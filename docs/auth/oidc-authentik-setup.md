# Aurora — Authentik OIDC Setup & Cutover

Status: backend + frontend implemented and merged on `v2/phase-0-scaffold` (commit `6264dca`).
OIDC ships **disabled** (`OIDC_ENABLED=false`); local email/password login is unchanged and remains the break-glass path. This document covers the external steps that cannot be done from the repo: creating the Authentik provider and enabling OIDC in production.

---

## 1. Architecture (as built)

Authentik is the human identity front door; Aurora keeps Sanctum + local RBAC.

```
Browser ──/api/auth/oidc/redirect──▶ Authentik (authorization-code + PKCE)
        ◀──redirect with ?code────── /api/auth/oidc/callback
                                       │  validate id_token (JWKS/iss/aud/exp/nonce)
                                       │  reconcile subject → local user (group-gated, additive)
                                       │  issue Sanctum token, store one-time exchange code (60s)
        ◀──302 /auth/callback?code=── (SPA)
SPA ──POST /api/auth/oidc/exchange──▶ returns { token, access_token, user }  (token never in a URL)
```

Key guarantees enforced in code:
- ID token validated server-side: signature (JWKS), issuer, audience, expiry, nonce.
- PKCE (S256); `state` (5 min) and exchange `code` (60 s) are server-side and single-use.
- JIT provisioning is **group-gated** and **additive-only**; OIDC can grant `admin` at most — **never `super-admin`**.
- The Sanctum token is never placed in the callback URL.

---

## 2. Create the Authentik provider/application

In Authentik admin (`https://auth.acumenus.net`):

1. **Providers → Create → OAuth2/OpenID Provider**
   - Name: `aurora-oidc`
   - Authorization flow: your default `default-authorization-flow` (with consent or implicit per policy)
   - Client type: **Confidential**
   - Redirect URIs (exact match): `https://aurora.acumenus.net/api/auth/oidc/callback`
   - Signing key: your RS256 certificate
   - Scopes: `openid`, `profile`, `email`, **`groups`** (ensure a groups scope mapping exists; without `groups` JIT denies everyone)
   - Subject mode: **Based on the User's UUID** (stable per-user `sub`)
2. **Applications → Create**
   - Name: `Aurora`, Slug: `aurora-oidc`, Provider: `aurora-oidc`
   - Launch URL: `https://aurora.acumenus.net`
3. **Access policy / bindings**: bind the application (or provider) to require the allowed group(s).
4. Record the **Client ID** and **Client Secret** (do not paste the secret into this repo or any log).

### Group convention
- Allowed login group(s): default `Aurora Admins` (set `OIDC_ALLOWED_GROUPS`, comma-separated for multiple).
- A user JIT-provisioned via an allowed group receives **`admin`** only.
- `super-admin` is local break-glass — grant it only via `SuperuserSeeder` or an existing super-admin.

Claim contract expected by Aurora: `sub` (stable), `email`, `name`, `groups` (array).

---

## 3. Production environment

Add to Aurora's production `backend/.env` (values from Authentik; keep the secret out of git/logs):

```env
OIDC_ENABLED=true
OIDC_DISCOVERY_URL=https://auth.acumenus.net/application/o/aurora-oidc/.well-known/openid-configuration
OIDC_CLIENT_ID=<from-authentik>
OIDC_CLIENT_SECRET=<from-authentik>
OIDC_REDIRECT_URI=https://aurora.acumenus.net/api/auth/oidc/callback
OIDC_ALLOWED_GROUPS=Aurora Admins
LOCAL_AUTH_ENABLED=true
```

`APP_URL` must be the user-facing origin (`https://aurora.acumenus.net`) — the callback redirects the browser to `APP_URL/auth/callback`.

Provider settings can also be managed at runtime via the super-admin-only **Admin → Authentication Providers** UI (`/admin/auth-providers`); DB settings override env. Secrets are masked on read.

---

## 4. Deploy & enable (order matters)

```bash
# 1. Migrate (creates auth_provider_settings, user_external_identities, oidc_email_aliases)
docker compose exec php php artisan migrate --force

# 2. Seed the provider rows (idempotent; seeds ldap/oauth2/saml2/oidc disabled)
docker compose exec php php artisan db:seed --class='Database\Seeders\AuthProviderSeeder' --force

# 3. Verify / reseed the local break-glass superuser BEFORE cutover
docker compose exec php php artisan db:seed --class='Database\Seeders\SuperuserSeeder' --force

# 4. Apply env (restart picks up env_file; `up -d`, not `restart`) and clear caches
docker compose up -d php
docker compose exec php php artisan config:clear
docker compose exec php php artisan cache:clear
docker compose exec php php artisan route:clear
```

Keep local auth enabled until: Authentik login works from the public host, at least **two** super-admins are verified, and rollback is confirmed. Keep a documented local break-glass path even after Authentik is primary.

---

## 5. Smoke checks

```bash
curl -k -i  https://aurora.acumenus.net/api/health
curl -k -s  https://aurora.acumenus.net/api/auth/providers      # oidc_enabled: true
curl -k -I  https://aurora.acumenus.net/api/auth/oidc/redirect  # 302 to Authentik authorize URL
```

After a browser login through Authentik:
- App redirects to `/auth/callback?code=…` then to the dashboard; the URL contains **no** Sanctum token.
- `GET /api/auth/user` returns roles + permissions.
- `GET /api/admin/auth-providers` works for super-admin, 403 for plain admin/non-admin.
- Local fallback login still works.
- `GET /api/patients?per_page=5` (authenticated) still returns data — catches "auth works but data path broke" regressions.

---

## 6. Failure modes

- **Redirect URI mismatch** in Authentik → callback/token-exchange fails even though Authentik login looks fine. Must match `OIDC_REDIRECT_URI` exactly.
- **Missing `groups` claim** → JIT denies everyone. Confirm the groups scope mapping is on the provider.
- **Clock skew** → `exp` validation fails; keep host time correct.
- **Cache backend** → `state`/exchange codes use cache `put`/`pull`; ensure the cache store is healthy.
- Never trust `X-authentik-*` headers on a directly-reachable port — only behind Traefik forward-auth.

---

## 7. Acumenus-wide standardization

This Laravel-native shape (provider discovery → OIDC callback → one-time exchange → local Sanctum token → local RBAC; admin-provider config; no token in URL) is the Acumenus app standard.

| App | OIDC slug | Redirect URI | Status |
|-----|-----------|--------------|--------|
| Parthenon | `parthenon-oidc` | `/api/v1/auth/oidc/callback` | source model (done) |
| **Aurora** | `aurora-oidc` | `/api/auth/oidc/callback` | **code done; Authentik provider + prod enable pending (this doc)** |
| Medgnosis | `medgnosis-oidc` | per that app's API prefix | not started — needs the same Laravel adapter |
| Data Room / dev portal | `acumenus-dataroom-oidc` | TBD | not started |

Non-Laravel tools/infra UIs should use Acropolis/Traefik Authentik **forward-auth** (`authentik@docker`) instead of bespoke logins, trusting `X-authentik-*` headers only behind that middleware.

Per-app registry to maintain centrally: OIDC slug, redirect URI, allowed groups, admin route, break-glass owner, smoke-test URL.
