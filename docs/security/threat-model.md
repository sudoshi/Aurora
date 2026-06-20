# Aurora — Threat Model & Security Review (W2)

Last updated: 2026-06-20
Status: **living document** — drives the W2 hardening workstream in
`.planning/GA-READINESS-PLAN.md`.

Scope: research/MDT collaboration platform handling clinical + genomic + imaging
data. GA target is "safe, supportable production for research/MDT use," not a
regulated medical device. PHI is in scope.

---

## Trust boundaries

1. **Browser ↔ API** — Sanctum bearer tokens; `auth:sanctum` + `throttle:api`.
2. **API ↔ AI service** (FastAPI) — internal HTTP; forwards user context headers.
3. **API ↔ external peers** — Federation relay, GA4GH Matchmaker (MME), Beacon v2.
4. **API → exports** — FHIR Genomics bundle, Phenopackets (leave the boundary).
5. **nginx → Orthanc** — DICOM proxy (credential now env-injected, W2-T01).

---

## Controls in place (verified 2026-06-20)

- **Security headers** (`SecurityHeaders` middleware, global): CSP (env-specific),
  HSTS, `X-Frame-Options: DENY`, `X-Content-Type-Options`, Referrer-Policy,
  Permissions-Policy. Regression test: `tests/Feature/SecurityHeadersTest.php`.
- **Rate limiting**: auth-keyed `throttle:api` (300/min user, 60/min guest);
  public auth endpoints tight (register 3/min, login 5/min, OIDC 20/min); AI
  30/min; Beacon 60/min. Test: `tests/Unit/ApiRateLimiterTest.php`.
- **Secret-at-boot validation** (`AppServiceProvider::verifyRequiredSecrets`):
  APP_KEY always; RESEND_API_KEY in prod; REVERB_* when broadcasting=reverb.
  Throws in dev, logs in prod.
- **No secrets in source**: gitleaks CI job (`--no-git`); Orthanc credential
  env-injected (W2-T01).
- **Commons authorization**: `ChannelPolicy` (added 2026-06-20) gates Message,
  Pin, Member, and now Reaction controllers (member-or-public view; owner/admin
  update). Notification/Abby scoped to the authenticated user.

---

## Findings

Severity: **C0** GA-blocking · **C1** high · **C2** medium.

### Authorization (W2-T08)

| # | Finding | File | Sev | Status |
|---|---------|------|-----|--------|
| A1 | `ReactionController::toggle` had no channel-membership check (sibling controllers do) | ReactionController.php | C1 | **FIXED** 2026-06-20 (+test) |
| A2 | `CaseController::show/update/destroy/addTeamMember/removeTeamMember` enforce nothing beyond `auth:sanctum`, while `index` scopes by team — any user can read/modify/archive any case | CaseController.php:91-180 | **C0** | **OPEN — decision D1** |
| A3 | `PatientController::profile/stats/notes/index/search` — any authenticated user reads any patient | PatientController.php:19-90 | **C0** | **OPEN — decision D1** |
| A4 | `GenomicsController` uploads/variants/fhirReport — no per-resource authz | GenomicsController.php | **C0** | **OPEN — decision D1** |
| A5 | `ImagingController` studies/timeline — no per-resource authz | ImagingController.php | C1 | **OPEN — decision D1** |
| A6 | `DiagnosticOdysseyController` show/worklist + MME search — no authz | DiagnosticOdysseyController.php | C1 | **OPEN — decision D1** |

> A2 is unambiguous (the inconsistency with `index` shows cases are *meant* to be
> scoped). A3–A6 depend on **decision D1**: is Aurora an *open clinical workspace*
> (any authenticated clinician sees all patients — common for MDT/tumor-board
> tools) or *per-resource/department isolation*? This determines whether these are
> bugs or by-design, and is a product/compliance call.

### PHI / de-identification (W2-T09)

| # | Finding | File | Sev | Status |
|---|---------|------|-----|--------|
| P1 | FHIR Genomics export includes direct identifiers (MRN, name, DOB, sex) with no de-id option | FhirGenomicsReportExporter.php:86-115 | **C0** | OPEN — decision D2 |
| P2 | MME outbound sends odyssey title + phenotypes + rare variants (quasi-identifiers); pseudo-ID only, no k-anonymity gate | MmeProfileSerializer.php | **C0** | OPEN — decision D2 |
| P3 | Beacon variant `count` granularity not k-anonymity-suppressed — count=1 leaks individual existence of a rare variant | BeaconService.php | C1 | OPEN — decision D2 |
| P4 | Phenopacket export embeds internal `patient_id` as subject id | PhenopacketExporter.php | C2 | OPEN — decision D2 |
| P5 | AI proxy forwards `X-User-Name` + `X-User-Roles` to the AI service | AiProxyController.php:26-31 | C2 | OPEN |

> Note: the *draft-decision* AI path is already de-identified (only derived
> age/gender reach Claude) and BioMCP-grounded — P1–P4 concern the standards
> export/federation surfaces. P3 (Beacon k-anonymity) has a clear standard fix
> (suppress counts below a threshold) once D2 sets the threshold.

### Audit logging (W2-T11)

- No audit trail for PHI reads (patient profile, genomics, imaging, odyssey) or
  data exports. Required for "minimum necessary" accountability. **OPEN.**

---

## Decisions required (owner: Dr. Udoshi)

> **Decisions taken (2026-06-20):** D1 = **open clinical workspace + PHI audit
> logging** (cases team-scoped; patient/genomics/imaging/odyssey remain broadly
> visible to authenticated clinical users). D2 = **internal identified, external
> de-identified** (internal FHIR/exports stay identified; MME + Beacon are
> de-identified + k-anonymized). See remediation status below.

**D1 — Clinical data access model.** Pick one:
- (a) **Open clinical workspace** — any authenticated clinical user may access any
  patient/genomics/imaging record; enforce only *case* scoping (A2) + add PHI
  **audit logging** (W2-T11) + RUO labeling. Fastest; matches many MDT tools.
- (b) **Per-resource / department isolation** — add policies scoping patient and
  clinical records to care-team / department membership. Larger effort; stricter.

**D2 — Export & federation de-identification.** For FHIR export, MME, Beacon,
Phenopackets: required de-id level (Safe-Harbor strip vs pseudonymized vs
opt-in identified for internal use), and the Beacon/MME **k-anonymity threshold**
(e.g., suppress counts < 5).

Both are GA-blocking. A2 (case scoping) and P3 (Beacon k-anon) can proceed as
soon as D1/D2 are set; the rest follows the chosen model.

---

## Remediation status

- **Done:** A1 (reaction authz), **A2 (CasePolicy team-scoping + tests)**,
  security headers + test, rate-limit verification, secret-at-boot validation,
  Orthanc credential externalization (W2-T01), **P2 (MME label de-id)**,
  **P3 (Beacon k-anonymity, configurable threshold)**, **P4 (Phenopacket
  pseudonymous subject)**.
- **By design (D1 = open clinical workspace):** A3–A6 (patient/genomics/imaging/
  odyssey broadly visible to authenticated clinical users) are accepted — the
  compensating control is PHI-access **audit logging (W2-T11, still open)**.
- **By design (D2 = internal identified):** P1 (FHIR export identifiers) and P5
  (AI proxy user headers) are internal-only surfaces — accepted as identified.
- **Pending operator:** Orthanc credential **rotation** (W2-T02) + history scrub
  (W2-T03) — the old value is in git history.
- **Still open:** **W2-T11** PHI-access audit logging (the D1 compensating
  control); sub-resource controllers (discussion/annotation/document/decision)
  should also gate on case access as a fast-follow.
