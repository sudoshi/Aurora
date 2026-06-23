# DEVLOG — Authentik SSO fleet verification

**Date:** 2026-06-22
**Author:** Sanjay Udoshi (with Claude Code)
**Status:** Bug found & fixed — Authentik "Redirect URI Error" resolved

---

## Summary

During the fleet-wide "Login with Authentik" rollout (which shipped new OIDC to
COPE and MediCosts), Aurora's SSO was tested end-to-end and found **broken at the
Authentik step**: the authorize request was rejected with **"Redirect URI Error"**
(HTTP 400). Root-caused and fixed 2026-06-22. The button now completes the handoff
to Authentik (HTTP 302 → login flow).

## The bug

`OidcProviderConfig::redirectUri()` returns the `redirect_uri` stored in the
`app.auth_provider_settings` (oidc) row whenever that row exists — even when the
row is `is_enabled=false` — falling back to env only if the stored value is empty.
`AuthProviderSeeder` had seeded that value as a **relative** path
(`/api/auth/oidc/callback`). So the live authorize request sent
`redirect_uri=/api/auth/oidc/callback`, which does not match the Authentik
provider's strict configured URI `https://aurora.acumenus.net/api/auth/oidc/callback`
→ "Redirect URI Error". (The deployed container's `OIDC_REDIRECT_URI` env was
correct and absolute, but it was overridden by the stored relative value.)

## The fix (two parts)

1. **Data fix (prod, immediate):** updated the live `auth_provider_settings` oidc
   row via `php artisan tinker` (the `settings` column is an `encrypted:array`
   cast) so `redirect_uri = https://aurora.acumenus.net/api/auth/oidc/callback`.
   No restart needed — `redirectUri()` reads the DB per request.
2. **Code fix (durable):** `database/seeders/AuthProviderSeeder.php` now seeds the
   absolute OIDC `redirect_uri`, so a fresh `db:seed` cannot reintroduce the bug.

After the fix: `GET .../api/auth/oidc/redirect` → 302 with the **absolute**
`redirect_uri`, and the Authentik authorize endpoint returns 302 (accepted, no
Redirect URI Error).

## Findings (verified)

- `GET https://aurora.acumenus.net/api/auth/providers` → `{"oidc_enabled":true,"oidc_label":"Authentik OpenID Connect"}`
- Authentik app `aurora-oidc` (provider pk 46) exists with strict redirect
  `https://aurora.acumenus.net/api/auth/oidc/callback`; group **"Aurora Admins"**
  contains exactly the 7 Acumenus admins
  (`sudoshi, ebruno, kpatel, jdawe, dmuraco, gbock, admin`) — the same set as
  "Parthenon Admins". The backend reconciler also enforces this group server-side.

## Findings

- `GET https://aurora.acumenus.net/api/auth/providers` → `{"oidc_enabled":true,"oidc_label":"Authentik OpenID Connect"}`
- `GET .../api/auth/oidc/redirect` → `302` to `auth.acumenus.net/application/o/authorize/`
  with a valid `client_id`, `redirect_uri=https://aurora.acumenus.net/api/auth/oidc/callback`,
  `scope=openid profile email groups`, and `code_challenge_method=S256`.
- Authentik app `aurora-oidc` (provider pk 46) exists; group **"Aurora Admins"**
  contains exactly the 7 Acumenus admins
  (`sudoshi, ebruno, kpatel, jdawe, dmuraco, gbock, admin`) — i.e. the same set as
  "Parthenon Admins". The backend reconciler enforces this group server-side, so
  only those 7 can complete sign-in (as `admin`).

## Note on repo vs. deployed state

The repository `backend/.env.example` defaults `OIDC_ENABLED=false`, which is
misleading: the **deployed** `backend/.env` has `OIDC_ENABLED=true` and the live
endpoints confirm SSO is active. No redeploy was performed (the working tree
carries unrelated in-progress case-detail work that must not be shipped).

See `reference_authentik_sso_fleet` in Claude memory for the full per-app map.
