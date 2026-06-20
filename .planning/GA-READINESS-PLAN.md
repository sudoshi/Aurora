# Aurora — GA Readiness Plan

Last updated: 2026-06-19
Owner: Aurora core team
Audience: **autonomous coding agents** and human maintainers.

This document is the authoritative, agent-executable plan to bring Aurora from
its current **demo-/UAT-ready** state (~80–85% feature-complete) to **General
Availability (GA)** as a research/MDT clinical-collaboration platform. It was
produced from a four-track independent audit (backend, frontend, AI service,
tests/CI/ops) ground-truthed against
`.planning/comprehensive-app-completion-todo.md`.

> Scope note: GA here means **safe, supportable, observable production for
> research/MDT use** — NOT FDA/CE regulated-medical-device clearance. Items that
> would be required for device clearance are called out as `[OUT-OF-GA]` and
> deferred behind explicit "Research Use Only" labeling.

---

## How agents must use this document

1. **Pick the lowest-numbered unblocked task** in the current milestone (see
   `## Sequencing` at the bottom). Respect `Depends on:` fields.
2. **Read the referenced files first.** Every task lists concrete
   `file:line` anchors. Do not start from memory.
3. **Honor the project guardrails** (these override defaults):
   - Auth system is frozen — read `.claude/rules/auth-system.md` before touching
     anything under auth. Additions only; never weaken the temp-password/Resend
     flow or the forced `must_change_password` modal.
   - Never modify the `omop` schema tables (read-only).
   - Non-destructive only. Soft deletes. No `migrate --force`, no dropping prod
     resources without explicit per-op confirmation. Use `--path=` for scoped
     migrations.
   - This host uses `sudo-rs` (no `-A`). If you need elevated privileges, STOP
     and ask the user to run the command.
   - PHP: run Pint after edits. TS: verify with BOTH `tsc --noEmit` AND
     `vite build` (vite is stricter). Python: ruff + mypy.
4. **Every task has `Acceptance` criteria and a `Verify` command.** A task is
   not done until `Verify` passes and you have pasted the evidence.
5. **One atomic commit per task** using the task ID in the subject, e.g.
   `feat(realtime): wire Reverb broadcaster [W1-T03]`. Conventional-commit types.
6. **Update the checkbox** in this file in the same commit. Add a `docs/devlog.md`
   entry when a whole workstream closes.
7. If a task is already satisfied in the repo, mark it `[x]` with a one-line
   evidence note and move on — do not redo work.

### Status legend

- `[ ]` open · `[~]` in progress · `[x]` done · `[-]` deferred/`[OUT-OF-GA]`
- Severity: **P0** GA-blocking · **P1** GA-blocking quality/security · **P2**
  strongly recommended for GA · **P3** post-GA / nice-to-have.

### GA exit criteria (the definition of done for this whole plan)

- [ ] All **P0** and **P1** tasks complete.
- [ ] CI is green with **zero** `continue-on-error` masks on lint/type/audit jobs.
- [ ] E2E suite gates deploys (not PR-only).
- [ ] No secrets in source control; secret scan clean.
- [ ] Every externally-reachable service has a health probe and an
      admin-visible status.
- [ ] Real-time collaboration works end-to-end (live message + presence) OR is
      explicitly removed from the GA marketing surface with polling fallback.
- [ ] Every user-facing action either performs real work or is hidden — no stub
      successes (regression-tested).
- [ ] Backup/restore runbook validated; rollback path documented.
- [ ] Threat model + security review signed off; PHI handling documented.

---

## Workstream index

| ID | Workstream | Severity | Blocking GA? |
|----|-----------|----------|--------------|
| W0 | Validation, tooling & CI integrity | P0/P1 | Yes |
| W1 | Real-time collaboration (the biggest gap) | P0 | Yes |
| W2 | Security hardening & secret management | P0/P1 | Yes |
| W3 | Observability, health checks & ops runbooks | P1 | Yes |
| W4 | Imaging productization & performance | P1/P2 | Partial |
| W5 | Imaging AI / segmentation | P2/`OUT-OF-GA` | Labeled |
| W6 | Interoperability (FHIR/OMOP) completeness | P2 | Partial |
| W7 | AI decision intelligence & guardrails | P1/P2 | Partial |
| W8 | Federation, Matchmaker & Beacon | P2 | Partial |
| W9 | Rare-disease follow-ons | P2/P3 | No |
| W10 | First non-rare population pack (TAVR) | P3 | No |
| W11 | Performance, scale & data lifecycle | P1/P2 | Yes |
| W12 | Accessibility, i18n & UX polish | P2 | Partial |
| W13 | Documentation, legal & release engineering | P1 | Yes |
| W14 | Maintainability / large-file refactors | P3 | No |

---

## W0 — Validation, tooling & CI integrity (P0/P1)

**Why:** CI currently masks failures, so every other "green" signal is
untrustworthy. Fix the harness first.

Evidence anchors:
- `.github/workflows/ci.yml:218-219` — `mypy ... continue-on-error: true # Enforce once stubs are complete`
- `.github/workflows/ci.yml:267-268` — `npm audit --audit-level=high ... continue-on-error: true`
- `.github/workflows/ci.yml:285-286` — `pip-audit ... continue-on-error: true`
- `.github/workflows/ci.yml:293-294` — e2e job `if: github.event_name == 'pull_request'`
- `.github/workflows/ci.yml:395-396` — deploy `needs: [backend-test, frontend, ai, security]` (E2E excluded)

- [x] **W0-T01 (P1)** Remove the `mypy` mask. Fix the underlying type errors in
      `ai/app/` until `mypy app/ --ignore-missing-imports` exits 0, then delete
      `continue-on-error` at `ci.yml:219`.
  - DONE 2026-06-19: fixed 3 type errors (response_assessment `CRITERIA_ASSESSORS`
    typed + recist dead-key dropped; fingerprint_encoder float coercion;
    plan_engine `tool_map: dict[str, Callable[..., Awaitable[Any]]]`). mypy clean,
    ruff clean, 40 tests pass (81.57%). Mask removed.
- [x] **W0-T02 (P1)** Triage `npm audit --audit-level=high`. Patch/upgrade or
      document an accepted-risk allowlist, then remove `continue-on-error`.
  - DONE 2026-06-19: `npm audit --audit-level=high` → 0 vulnerabilities. Mask removed.
- [x] **W0-T03 (P1)** Same for `pip-audit`. Pin/upgrade vulnerable deps; remove mask.
  - DONE 2026-06-19: bumped python-dotenv→1.2.2, markdown→3.8.1, pytest→9.0.3
    (tests still pass). Residual 10 CVEs are transitive under fastapi/biomcp-python
    pins (starlette/mcp) + diskcache (no upstream fix); replaced blanket mask with
    explicit per-CVE `--ignore-vuln` so NEW vulns fail CI. pip-audit exits 0.
    Coordinated framework bump tracked as **W0-T03b**.
- [ ] **W0-T03b (P1)** Coordinated `fastapi` + `biomcp-python` (+`starlette`/`mcp`)
      major bump to clear the 9 ignored transitive CVEs, validated against the
      BioMCP evidence-retrieval path (PubMed/trials/variants). Remove the matching
      `--ignore-vuln` entries from `ci.yml` as each clears.
- [x] **W0-T04 (P0)** Make E2E gate `main`. Change the e2e job to run on
      `push` as well, and add `e2e` to the deploy job `needs:`.
  - DONE 2026-06-19: removed e2e `if: pull_request` (now runs on PRs + pushes);
    deploy `needs:` now includes `e2e`. Deploy cannot ship while E2E is red.
- [ ] **W0-T05 (P1)** Rebuild `ai/venv` reproducibly. Host Python 3.14 cannot
      install `pydantic-core==2.27.2` (PyO3). Pin the AI toolchain to Python 3.12
      and make the canonical AI test path the Docker image
      (`aurora-ai:dev`); document it in `ai/README.md`.
  - Verify: `docker run --rm aurora-ai:dev python -m pytest -q` passes.
- [ ] **W0-T06 (P1)** Expand AI coverage scope. `ai/pytest.ini` only measures 8
      modules. Add `response_assessment`, `segmentation_service`,
      `volumetric_service`, `biomcp_service`, and the `imaging`/`fingerprint`
      routers to `--cov`, and write tests until `--cov-fail-under` holds at ≥70%
      on the expanded set.
  - Verify: pytest run shows the new modules in the coverage report ≥70%.
- [ ] **W0-T07 (P1)** Enforce a backend coverage floor. Wire Pest/PHPUnit
      `--coverage --min=` (pcov/xdebug in CI) at a realistic starting floor
      (e.g. 60%) and ratchet up. Confirm the historical "continue-on-error masks
      ~22 failures" note is no longer true — backend-test must hard-fail.
  - Verify: introduce a deliberately failing assertion locally; CI job goes red.
- [ ] **W0-T08 (P2)** Add a frontend coverage floor in `vite.config.ts`
      (`coverage.thresholds`) and stop excluding real source from the report
      unless genuinely untestable.
- [x] **W0-T09 (P1)** Add a secret-scan job to CI (gitleaks) that hard-fails on
      findings. (Pairs with W2.)
  - DONE 2026-06-19: added `secret-scan` job (gitleaks v8.30.1, `--no-git`
    working-tree scan). It immediately caught the credential leaking into
    `dicom/sync_orthanc_to_aurora.py:36` (hardcoded default) and 5 lines of
    `1-PLAN.md` — all redacted. `.gitleaks.toml` narrowly allowlists one prose
    false positive. Clean-tree scan exits 0. Full-history scanning to be enabled
    after the W2-T03 history scrub (drop `--no-git`).
- [ ] **W0-T10 (P2)** Add a "no stub-success" guard to CI: a grep/lint step that
      fails if `frontend/src/lib/echo.ts` still contains the `stub` marker once
      W1 lands, and that asserts imaging stub regression tests are present.
  - NOTE: deferred into W1 — the echo `stub` marker is still present until W1
    replaces it; enforcing the guard now would red CI. Land it with W1-T05.

---

## W1 — Real-time collaboration (P0, the biggest functional gap)

**Why:** Aurora's core pitch is *synchronous collaboration*, but nothing is live.
Frontend hooks are written and degrade gracefully on a null Echo; the backend has
no broadcasting at all.

Evidence anchors:
- `frontend/src/lib/echo.ts` — 12-line stub (`getEcho`/`setEcho`, returns null).
- `backend/app/Events/` — **does not exist**.
- No `backend/config/broadcasting.php`; `backend/.env.example:48` `BROADCAST_CONNECTION=log`.
- Consumers already written: `frontend/src/features/commons/hooks/useEcho.ts`,
  `useTypingIndicator.ts`, `usePresence.ts`, notification listener.

- [x] **W1-T01 (P0)** Decide transport. **Laravel Reverb** chosen (first-party,
      ships with Laravel 12, Pusher-protocol compatible). Rationale + ports/TLS
      captured inline in `backend/.env.example` and the prod-wiring task (W1-T07).
- [x] **W1-T02 (P0)** Install + configure Reverb.
  - DONE 2026-06-19: `composer require laravel/reverb` (v1.10); published
    `config/broadcasting.php` + `config/reverb.php`; `BROADCAST_CONNECTION=reverb`
    + REVERB_*/VITE_REVERB_* in `.env.example`; `phpunit.xml` forces the `null`
    broadcaster for tests. Also patched guzzle 7.12.1/psr7 2.12.1 so the unmasked
    `composer audit` stays green.
- [x] **W1-T03 (P0)** Channel authorization in `routes/channels.php`.
  - DONE 2026-06-19: `commons.channel.{id}` (member-or-public), private
    `App.Models.User.{id}` (id match), presence `commons.online`. Wired via
    `bootstrap/app.php` `withBroadcasting(..., ['middleware' => ['auth:sanctum']])`
    — `broadcasting/auth` route confirmed registered under sanctum.
- [x] **W1-T04 (P0)** `ShouldBroadcastNow` events + dispatch.
  - DONE 2026-06-19: `App\Events\Commons\{MessageSent,MessageUpdated,
    ReactionUpdated,NotificationSent}` dispatched from MessageController
    (store/update/destroy) and ReactionController, payloads matched byte-for-byte
    to the `useEcho`/`useNotificationListener` contracts (default class-name for
    message/reaction; `broadcastAs('NotificationSent')` for the dotted listener;
    rich reaction summary with users). Typing/presence need no server event
    (whisper + presence channel only). Also added a real **thread-reply
    notification** producer (backend previously never wrote `commons_notifications`).
  - NOTE: live socket delivery is NOT yet proven — see W1-T07/T08.
- [x] **W1-T05 (P0)** Real `echo.ts` (laravel-echo + pusher-js, reverb broadcaster,
      sanctum-bearer authorizer at `/broadcasting/auth`); `useRealtimeConnection`
      brings it up in `DashboardLayout`; `useNotificationListener` now mounted
      app-wide. Stub marker removed.
  - DONE 2026-06-19: `tsc --noEmit` clean, `vite build` clean, 88 unit tests pass.
    (Also restored `@testing-library/dom` + added `vite-env.d.ts` after the echo
    libs install pruned the peer dep.)
- [x] **W1-T06 (P1)** Graceful polling fallback + indicator.
  - DONE 2026-06-19: `useMessages` polls every 8s whenever realtime status is not
    `connected`; a "Reconnecting… live updates paused" pill shows on degraded.
- [~] **W1-T07 (P1)** Run Reverb in production. Artifacts authored + dev wiring
      done; prod ACTIVATION requires sudo (handed to operator).
  - DONE 2026-06-19: dev `reverb` service (opt-in `realtime` profile) in
    `docker-compose.yml` + nginx `/app` WS proxy in both templates + healthcheck;
    `deploy.sh` `restart_reverb` hook. Prod artifacts: `deploy/aurora-reverb.service`
    (systemd), `deploy/apache-aurora-reverb.conf` (mod_proxy_wstunnel), and the
    runbook `docs/deployment/realtime-reverb.md`. Verified: `docker compose
    --profile realtime config` valid, nginx envsubst intact.
  - PENDING (operator, sudo): `systemctl enable --now aurora-reverb`,
    `a2enmod proxy_wstunnel` + paste the Apache snippet + reload, set prod
    REVERB_*/VITE_REVERB_* in `backend/.env` and rebuild frontend. See runbook.
- [~] **W1-T08 (P1)** Multi-user E2E + backend broadcast test.
  - DONE 2026-06-19: backend `CommonsBroadcastTest` (5 tests) asserts all four
    events broadcast on the correct channels with the frontend-matching payloads
    + the thread-reply notification producer — green (uncovered & fixed the
    missing `ChannelPolicy` that was 403-ing all posting). Two-context Playwright
    spec `e2e/tests/realtime.spec.ts` proves live cross-session delivery within a
    6s window (tighter than the 8s poll ⇒ proves push); parses under the chromium
    project.
  - PENDING: run `npx playwright test realtime` against a stack with Reverb live
    (after W1-T07 activation). Presence/typing/notification-push E2E assertions
    can be layered on once the message-delivery spec is green live.

---

## W2 — Security hardening & secret management (P0/P1)

Evidence anchors:
- `docker/nginx/default.conf:78-79` and `docker/nginx/dev.conf:67-68` —
  hardcoded `Authorization "Basic cGFydGhlbm9uOi..."` (Orthanc creds, base64,
  with an unfixed TODO).
- `.claude/rules/auth-system.md` — frozen auth contract.
- `backend/app/Http/Middleware/SecurityHeaders` — exists; verify coverage.

- [x] **W2-T01 (P0)** Remove the hardcoded Orthanc credential from both nginx
      configs. Use `envsubst` at container start or Docker secrets to inject
      `ORTHANC_PROXY_AUTH`. Add the var to `.env.example` (placeholder only).
  - Acceptance: `grep -r "cGFydGhlbm9u" docker/` returns nothing.
  - DONE 2026-06-19: `default.conf`/`dev.conf` → `*.template` mounted into
    `/etc/nginx/templates/`; compose passes `ORTHANC_PROXY_AUTH` +
    `NGINX_ENVSUBST_FILTER=ORTHANC_PROXY_AUTH` (scopes substitution so nginx
    `$host`/`$uri` are preserved). Root `.env.example` has the placeholder + a
    generator one-liner. Verified: secret grep clean, `docker compose config`
    valid (base + dev profile), envsubst dry-run substitutes only the auth var.
    Live rotation (W2-T02) + history scrub (W2-T03) remain with the operator.
- [ ] **W2-T02 (P0)** **Rotate the exposed Orthanc credential** — it is in git
      history and must be considered compromised. Coordinate with the user
      (Orthanc is shared infra); do not rotate prod creds unilaterally.
  - Acceptance: old credential no longer authenticates; new credential injected
    via secret. Document in devlog (do NOT print the secret).
- [ ] **W2-T03 (P1)** Scrub the secret from git history (BFG/`git filter-repo`)
      OR formally accept-and-rotate. Requires explicit user decision — present
      both options, recommend rotate+scrub.
- [ ] **W2-T04 (P1)** Audit all `.env.example` files: every required secret has a
      placeholder + sensible non-secret default; no real keys committed. Verify
      `RESEND_API_KEY`, `ANTHROPIC_API_KEY`, DB creds, Reverb keys are
      placeholders only.
- [x] **W2-T05 (P1)** Validate required secrets at boot (fail loudly in dev,
      structured error in prod).
  - DONE 2026-06-20: `AppServiceProvider::verifyRequiredSecrets` — APP_KEY always,
    RESEND_API_KEY in prod, REVERB_* when broadcasting=reverb; throws in dev, logs
    in prod, skips console/testing.
- [x] **W2-T06 (P1)** Security headers present.
  - DONE 2026-06-20: verified `SecurityHeaders` covers CSP/HSTS/X-Frame/
    X-Content-Type/Referrer/Permissions-Policy (prod CSP already allows `ws:`/`wss:`
    for Reverb and same-origin OHIF iframe). Added regression test
    `tests/Feature/SecurityHeadersTest.php`.
- [x] **W2-T07 (P1)** Rate limiter verified.
  - DONE 2026-06-20: auth-keyed `throttle:api` (300/60) + tight public auth limits
    (register 3, login 5, OIDC 20), AI 30, Beacon 60; `ApiRateLimiterTest` present.
- [~] **W2-T08 (P1)** Authorization audit — COMPLETE; findings in
      `docs/security/threat-model.md`. A1 (ReactionController channel check) FIXED
      + test. A2 (cases) + A3–A6 (patient/genomics/imaging/odyssey) OPEN pending
      **decision D1** (open clinical workspace vs per-resource isolation).
- [~] **W2-T09 (P1)** PHI/de-id review — COMPLETE; P1–P5 in the threat model.
      FHIR export / MME / Beacon-count / Phenopacket de-id OPEN pending
      **decision D2** (de-id level + k-anonymity threshold). draft-decision path
      already de-identified.
- [~] **W2-T10 (P0)** Security review — threat model produced at
      `docs/security/threat-model.md` with severity-ranked findings + the two
      decisions (D1, D2) that gate the remaining C0/C1 fixes.
- [ ] **W2-T11 (P2)** Add audit logging for sensitive actions (federated queries,
      data export, admin changes, PHI access). Confirm `user_audit_logs` covers
      these; extend if not.

---

## W3 — Observability, health checks & ops runbooks (P1)

Evidence anchors:
- `backend/routes/api.php` `GET /api/health` — static liveness only.
- `DashboardController` `system-health` — DB + cache only; admin-gated.
- No probes for AI, federation, Orthanc, queue, sync freshness.

- [ ] **W3-T01 (P1)** Implement a real readiness endpoint that checks each
      dependency: Postgres, Redis, queue depth, AI service, federation relay,
      Orthanc, and last successful DICOM/OncoKB/ClinVar/ClinGen sync timestamps.
      Return per-dependency status + overall.
  - Acceptance: stopping Redis flips the readiness payload to degraded.
- [ ] **W3-T02 (P1)** Add `healthcheck:` blocks to **every** service in
      `docker-compose.prod.yml` (api, ai, federation, worker, reverb) — currently
      only db/redis have them.
- [ ] **W3-T03 (P1)** Admin-visible status board: extend the admin System Health
      page to show stale/error states for OncoKB, ClinVar, ClinGen, DICOM sync,
      AI, federation, and Reverb (covers TODO §10).
- [ ] **W3-T04 (P1)** Structured logging with correlation/request IDs across
      backend + AI service; ship to the existing Loki stack if available.
- [ ] **W3-T05 (P2)** Metrics export (Prometheus): request latency, queue depth,
      job failure rate, AI/BioMCP call latency + error rate.
- [ ] **W3-T06 (P2)** Alerting thresholds + routing for: queue backlog, failed
      jobs, sync staleness, 5xx rate, Reverb disconnects.
- [ ] **W3-T07 (P1)** Queue worker operability: confirm `worker` restart policy,
      `--tries`/`--timeout` are env-configurable, and failed jobs land in
      `failed_jobs` with a retry runbook.
- [ ] **W3-T08 (P1)** Backup & restore runbook: documented, scheduled Postgres
      backups + a **tested** restore. Add `docs/deployment/backup-restore.md`.
- [ ] **W3-T09 (P1)** Rollback runbook: how to revert a bad deploy (frontend
      assets + migrations). Document migration-down safety; prefer
      expand/contract migrations over destructive ones.
- [ ] **W3-T10 (P2)** Resolve `docker-compose.prod.yml` status: it's unclear if
      it's legacy vs supported. Reconcile with the
      `docker-compose.yml` + `deploy.sh` static-serving path; remove or document.

---

## W4 — Imaging productization & performance (P1/P2)

Evidence anchors:
- `backend/app/Http/Controllers/ImagingController.php` — **1,990 lines**.
- Orthanc/OHIF/DICOMweb ingestion is real; per-study count queries are N+1-prone.

- [ ] **W4-T01 (P2)** Split `ImagingController` into focused controllers/services
      (studies, series, measurements, response-assessment, analytics, ingestion).
      Behavior-preserving; keep route signatures identical. Add tests for each
      extracted unit.
  - Verify: full imaging Feature test suite green before/after.
- [ ] **W4-T02 (P1)** Replace per-study measurement/segmentation **count
      queries** with eager-loaded `withCount(...)` to kill N+1 on study listing.
- [ ] **W4-T03 (P1)** Add a query-count/performance test for the study-listing
      endpoint (assert bounded query count regardless of result size).
- [ ] **W4-T04 (P1)** Confirm the blank-PatientID MR study policy (24 studies) is
      enforced in code (quarantine/manual-review), not just documented in
      `docs/imaging-ingestion-policy.md`. Add a test.
- [ ] **W4-T05 (P2)** Resolve the untracked
      `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-PLAN.md` and
      `dicom/pathology_download_plan.md`: promote to tracked plans or remove.
- [ ] **W4-T06 (P1)** OHIF iframe hardening: lock the embed origin, verify CSP
      `frame-src`, and confirm `wadors_uri=/orthanc/dicom-web` works behind the
      env-injected Orthanc auth from W2-T01.

---

## W5 — Imaging AI / segmentation (P2 / partially `OUT-OF-GA`)

Evidence anchors:
- `ai/app/services/segmentation_service.py:1-3,70-91` — explicitly MOCK
  (hardcoded `BODY_SITE_STRUCTURES` lookup, no model inference).

- [ ] **W5-T01 (P1)** **GA gate decision:** until a real model is wired, the
      segmentation/volumetrics/feature-extraction UI must be labeled "Research
      /experimental" or hidden, and API responses must carry
      `"computed": false`/`"mock": true` so no clinician mistakes mock anatomy for
      a real measurement. This is GA-blocking *as labeling*, not as a model build.
  - Acceptance: no user-facing surface presents mock segmentation as a real
    clinical measurement.
- [ ] **W5-T02 (P2)** Distinguish clinician-entered vs computed measurements in
      both API payloads and UI (a `source` field end-to-end).
- [ ] **W5-T03 `[OUT-OF-GA]`** Integrate a real segmentation model
      (TotalSegmentator or nnU-Net) behind an isolated process/queue boundary.
- [ ] **W5-T04 `[OUT-OF-GA]`** Define sync-vs-queued execution for large DICOM
      studies; persist segmentation/volumetric/feature outputs to Laravel tables
      with job status, retries, error states, and audit metadata.
- [ ] **W5-T05 `[OUT-OF-GA]`** Model/service tests for real image-analysis
      execution boundaries.

---

## W6 — Interoperability (FHIR/OMOP) completeness (P2)

Evidence anchors:
- `FhirAdapter` (499 lines), `OmopAdapter` (314), `ManualAdapter` (246) — all
  real read projections; adapter selection is configurable.
- FHIR Genomics export (`FhirGenomicsReportExporter`, 343 lines) shipped.

- [ ] **W6-T01 (P2)** Validate FHIR R4 conformance of emitted resources against
      the official validator (Patient, Condition, MedicationStatement,
      Procedure, Observation, Encounter, DocumentReference, ImagingStudy,
      DiagnosticReport, variant Observation). Add a CI validation step.
- [ ] **W6-T02 (P2)** Extend adapter contract tests to Abby, decision-drafting,
      and cohort tools (currently only profile/search/notes/imaging/genomics tabs
      are covered) — TODO §3 open item.
- [ ] **W6-T03 (P2)** Confirm adapter selection (FHIR vs OMOP vs Manual) is
      operator-configurable per deployment and documented in `docs/`.
- [ ] **W6-T04 (P3)** Plan a second outbound emit path beyond FHIR Genomics
      (e.g. full patient summary IPS bundle) if roadmap requires.

---

## W7 — AI decision intelligence & guardrails (P1/P2)

Evidence anchors:
- `ai/app/routers/decisions.py` — draft-decision is REAL (Claude + de-id +
  BioMCP grounding), tested in `tests/test_draft_decision.py`.
- `decision_support` router endpoints (trials, guidelines, drug-interactions,
  variant-interpret, rare-disease) are **LLM-advisory** with no backing KB.
- Abby chat session state is an in-memory dict (lost on restart).

- [ ] **W7-T01 (P1)** Label advisory AI clearly. Every LLM-only endpoint
      (trial-match, guidelines, drug-interactions, variant-interpret,
      rare-disease, genomic-briefing) must return a machine-readable
      `evidence_grade: "llm_advisory"` and the UI must show a "not a
      database-verified result — verify independently" affordance.
  - Acceptance: no advisory output renders as if it were authoritative CDS.
- [ ] **W7-T02 (P1)** Persist Abby chat session state (Redis or DB) so context
      survives AI-service restarts; add a retention policy.
- [ ] **W7-T03 (P1)** Resilience: confirm every Ollama-dependent endpoint
      degrades safely AND surfaces a "degraded" status to the caller rather than
      silently returning empty/boilerplate. Add tests for the Ollama-down path.
- [ ] **W7-T04 (P2)** Pin the model IDs / config for Claude and Ollama in config;
      record them in AI attribution fields (`ai_model`) already persisted by
      `DecisionController`.
- [ ] **W7-T05 (P2)** Decision-quality instrumentation (TODO §7): track edit
      distance, review time, accepted/rejected evidence, confidence deltas on
      AI-drafted decisions.
- [ ] **W7-T06 `[OUT-OF-GA]`** Ambient MDT transcript ingestion, diarization,
      transcript→draft-decision conversion, clinician review workflow, and
      audio/transcript privacy+retention controls (decision-intelligence slice 2).

---

## W8 — Federation, Matchmaker & Beacon (P2)

Evidence anchors:
- `federation/relay.py` (~300 lines) — mTLS, Ed25519, k-anonymity,
  de-identification scaffolding present.
- `/federation/respond` returns an empty-result stub (no local similarity exec).
- MME inbound/outbound + Beacon v2 endpoints exist; Beacon filtering returns empty.

- [ ] **W8-T01 (P2)** Implement `/federation/respond`: execute a real local
      similarity query (reuse the FingerprintService path) instead of the empty
      stub; enforce k-anonymity + de-id on the response.
- [ ] **W8-T02 (P2)** Peer registration/configuration workflow + operational docs
      (`docs/federation/`). Add audit logs and institution policy controls for
      federated queries.
- [ ] **W8-T03 (P2)** End-to-end validation with **two** Aurora instances
      (compose a second instance in a test profile); prove a federated similarity
      round-trip.
- [ ] **W8-T04 (P2)** Beacon: expand filtering terms beyond the empty response;
      decide record/count access tiers + k-anonymity policy; document.
- [ ] **W8-T05 (P2)** MME: configure real peers, document consent/privacy
      controls and the data-sharing agreement model.

---

## W9 — Rare-disease follow-ons (P2/P3)

Evidence anchors: Odyssey backend, HPO proxy, Phenopackets, ACMG points engine,
ClinGen Allele Registry + GDV, VRS service (degrades to null) all exist.

- [ ] **W9-T01 (P2)** Provision VRS/SeqRepo/UTA OR formally defer computed VRS IDs
      behind CAID-only identity (document the decision). Currently degrades to null.
- [ ] **W9-T02 (P2)** Enable operational ClinGen GDV scheduled ingestion as a
      second KB-change alert source in production (verify it's actually scheduled).
- [ ] **W9-T03 (P3)** Evaluate ClinVar TSV ingestion upgrade if
      `DateLastEvaluated` becomes required.
- [ ] **W9-T04 (P3)** Add Phen2Gene or Exomiser behind an isolated process
      boundary.
- [ ] **W9-T05 (P2)** Document rare-disease data-sharing consent for MME/Beacon
      participation (ties to W8-T05).

---

## W10 — First non-rare population pack: Cardiac Heart Team / TAVR (P3)

Evidence anchors: board-template engine + template-bound cases + dynamic case
form fields exist; demo cardiac seed content exists.

- [ ] **W10-T01 (P3)** Define the TAVR case template, candidacy rubric, agenda,
      and state machine.
- [ ] **W10-T02 (P3)** Structured decision schema for candidacy, optimization,
      procedural planning.
- [ ] **W10-T03 (P3)** Computable risk-score inputs: RCRI, frailty, pulmonary
      risk, surgical optimization.
- [ ] **W10-T04 (P3)** Reuse imaging/task/decision infra for episode-of-care
      follow-through.
- [ ] **W10-T05 (P3)** Tests + demo walkthrough for the TAVR pack.

---

## W11 — Performance, scale & data lifecycle (P1/P2)

- [ ] **W11-T01 (P1)** Audit N+1 across the app (not just imaging): patient
      profile timeline, commons message lists, cases, genomics variant tables.
      Add eager loading; add query-count tests on the hottest endpoints.
- [ ] **W11-T02 (P1)** Verify DB indexes exist for hot query paths (foreign keys,
      patient/study/variant lookups, channel/message ordering). Add missing
      indexes via scoped migrations.
- [ ] **W11-T02b (P2)** Switch `QUEUE_CONNECTION` from `database`
      (`.env.example:50`) to Redis for production throughput; keep database for
      local dev. Document.
- [ ] **W11-T03 (P2)** Pagination + payload-size limits on all list endpoints;
      confirm frontend uses cursor/page params (no unbounded fetches).
- [ ] **W11-T04 (P2)** Frontend bundle audit: confirm route-level code splitting
      is effective; analyze the largest chunks (commons, patient-profile,
      genomics, imaging are the biggest modules) and lazy-load heavy deps (OHIF).
- [ ] **W11-T05 (P2)** Load test the realtime path (W1) and the imaging study
      listing under representative concurrency; record baselines.
- [ ] **W11-T06 (P2)** Data retention/lifecycle policy: uploads, transcripts (if
      W7-T06), audit logs, soft-deleted records; document and implement pruning.

---

## W12 — Accessibility, i18n & UX polish (P2)

- [ ] **W12-T01 (P2)** WCAG 2.1 AA pass on core flows (login, dashboard, case,
      patient profile, imaging viewer, commons): keyboard nav, focus management,
      ARIA on modals/drawers/data tables, contrast in the dark clinical theme.
      Add axe checks to the e2e suite.
- [ ] **W12-T02 (P2)** Consistent loading/empty/error states across all features
      (audit for raw spinners / missing error UI).
- [ ] **W12-T03 (P3)** i18n scaffolding decision (defer translations, but don't
      hardcode strings in new code if i18n is planned).
- [ ] **W12-T04 (P2)** Verify the forced `ChangePasswordModal` and auth flows are
      accessible and that error toasts never leak sensitive detail.

---

## W13 — Documentation, legal & release engineering (P1)

Evidence anchors: `docs/devlog.md`, `.planning/ROADMAP.md`, `.planning/STATE.md`
are active; `PROJECT.md` and `codebase/CONCERNS.md` are stale (marked historical
but not rewritten).

- [ ] **W13-T01 (P1)** Fully rewrite `.planning/PROJECT.md` (still describes March
      stabilization items as active) and `.planning/codebase/CONCERNS.md`
      (lists already-closed OncoKB/genomics concerns as open).
- [ ] **W13-T02 (P1)** Operator install/runbook: from-zero deployment doc that
      matches `deploy.sh` + the static-serving prod path; environment matrix.
- [ ] **W13-T03 (P1)** "Research Use Only" labeling + disclaimer surfaced in-app
      and in docs (ties to W5-T01, W7-T01). Required for safe GA.
- [ ] **W13-T04 (P1)** API documentation (OpenAPI/Scribe) generated from the 260
      routes; published.
- [ ] **W13-T05 (P2)** LICENSE present and correct (Apache 2.0 per house style);
      CONTRIBUTING, SECURITY.md (vuln disclosure), and a CHANGELOG.
- [ ] **W13-T06 (P1)** Versioning/release process: tag GA (`v2.x.0`), release
      notes, and a documented upgrade path. Add devlog entry per closed workstream.
- [ ] **W13-T07 (P2)** Data Processing / PHI handling documentation (what is
      stored, where it leaves the boundary, de-id guarantees) for institutional
      review.

---

## W14 — Maintainability / large-file refactors (P3, post-GA-safe)

Do these only once behavior is stable and well-tested; they are not GA-blocking.
Add focused unit tests for extracted pure functions/components in each.

- [ ] **W14-T01** Split `backend/app/Http/Controllers/ImagingController.php` (1,990). (= W4-T01)
- [ ] **W14-T02** Split `frontend/src/features/patient-profile/components/PatientTimeline.tsx` (938).
- [ ] **W14-T03** Split `frontend/src/features/commons/api.ts` (835).
- [ ] **W14-T04** Split `frontend/src/features/cases/pages/CaseDetailPage.tsx` (791).
- [ ] **W14-T05** Split `frontend/src/features/imaging/pages/ImagingPage.tsx` (713).
- [ ] **W14-T06** Split `frontend/src/components/layout/AbbyPanel.tsx` (690).
- [ ] **W14-T07** Split `frontend/src/features/genomics/pages/GenomicsPage.tsx` (648).

---

## Sequencing (milestones)

Execute roughly in this order; within a milestone, parallelize independent
workstreams.

**Milestone GA-0 — Trustworthy harness (do first):**
W0 (CI integrity, coverage, secret-scan job). Nothing else can be verified until
CI stops masking failures.

**Milestone GA-1 — Security & the core functional gap:**
W2 (secrets/rotation/security review) ‖ W1 (real-time collaboration).
These are the two hard GA blockers.

**Milestone GA-2 — Operability:**
W3 (health/observability/runbooks) ‖ W11 (perf/scale/indexes/queue).

**Milestone GA-3 — Honest product surface:**
W5-T01/T02 (label mock segmentation) ‖ W7-T01..T04 (label advisory AI, persist
sessions) ‖ W4 (imaging productization/perf) ‖ W6 (FHIR/OMOP conformance) ‖
W12 (a11y) ‖ W13 (docs/legal/release).

**Milestone GA-4 — GA cut:**
Verify all GA exit criteria; tag release; devlog closeout.

**Post-GA:**
W5-T03+ (real segmentation), W7-T06 (ambient MDT), W8 (federation depth),
W9 (rare-disease follow-ons), W10 (TAVR pack), W14 (refactors).

---

## Appendix — quick verification commands

```bash
# Backend (host path used by the project)
APP_ENV=testing DB_CONNECTION=pgsql DB_HOST=localhost DB_DATABASE=aurora_test \
  DB_USERNAME=smudoshi DB_MIGRATIONS_TABLE=public.migrations \
  ./backend/vendor/bin/pest --exclude-group=mockery-alias
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"   # if dockerized

# Frontend (both checks — vite is stricter than tsc)
npm --prefix frontend run typecheck
npm --prefix frontend run build
npm --prefix frontend test

# AI (canonical path is the Docker image; host py3.14 can't build pydantic-core)
docker run --rm aurora-ai:dev python -m pytest -q
docker run --rm aurora-ai:dev mypy app/ --ignore-missing-imports

# E2E
npx playwright test --project=chromium

# Secret scan (add to CI in W0-T09)
gitleaks detect --no-banner

# Confirm the Orthanc secret is gone (W2-T01)
grep -rn "cGFydGhlbm9u" docker/ || echo "clean"
```
