# Aurora OpenAPI Specification

Aurora's HTTP API is documented with an OpenAPI 3.0 specification generated from the
Laravel routes using [Scribe](https://scribe.knuckles.wtf/laravel).

> **Research Use Only.** The Aurora API is not for diagnostic or clinical
> decision-making use.

## Where the spec lives

- **OpenAPI spec (tracked in repo):** `backend/public/docs/openapi.yaml`
- **Browsable HTML docs (generated, not tracked):** `backend/public/docs/index.html`
- **Postman collection (generated, not tracked):** `backend/public/docs/collection.json`

The HTML docs and Postman collection are regenerated artifacts and are
git-ignored (see `backend/.gitignore`). Only `openapi.yaml` is committed so the
spec is always available in-repo. When deployed, the static docs are served at
`/docs`.

The spec currently documents **257 API operations** across the routes in
`backend/routes/api.php`.

## Authentication

The API uses Laravel Sanctum bearer tokens. Authenticate via `POST /api/login`
to obtain a token, then send it as `Authorization: Bearer {token}` on
subsequent requests.

## Regenerating

Run from the `backend/` directory (or inside the `aurora-php` container):

```bash
php artisan scribe:generate
```

Scribe configuration lives in `backend/config/scribe.php`. Notable settings:

- `type => 'static'` — output to `public/docs` without registering extra routes.
- `routes[0].match.prefixes => ['api/*']` — only `api/*` routes are documented.
- `auth.enabled => true`, `auth.in => bearer` — Sanctum bearer auth.
- Response calls are **disabled** (the `ResponseCalls` strategy `only` is empty)
  so generation never executes live endpoints — no DB writes, no side effects,
  deterministic output.
- `openapi.enabled => true` — emits `openapi.yaml` alongside the HTML docs.
