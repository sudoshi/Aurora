# DEVLOG — Authentik SSO fleet verification

**Date:** 2026-06-22
**Author:** Sanjay Udoshi (with Claude Code)
**Status:** Verified live — no code change

---

## Summary

During the fleet-wide "Login with Authentik" rollout (which shipped new OIDC to
COPE and MediCosts), Aurora's existing SSO was **verified live in production** and
required **no changes**.

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
