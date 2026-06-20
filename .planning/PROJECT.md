# Aurora — Project Overview & Current Phase

Last updated: 2026-06-20

> This file describes Aurora **as it is now** and the phase it is in. The
> authoritative, agent-executable roadmap is **`.planning/GA-READINESS-PLAN.md`**
> (workstreams W0–W14). The active milestone state is `.planning/STATE.md` and the
> product sequence is `.planning/ROADMAP.md`. The previous "March stabilization"
> framing of this file is retired — that work is complete.

## What Aurora Is

Aurora is a secure, real-time collaboration platform for multidisciplinary
clinical teams (tumor boards, surgical planning, rare-disease diagnostic
odysseys, complex medical reviews) to coordinate patient care. It unifies:

- **Patient intelligence** — demographics, conditions, medications, labs,
  imaging, genomics, notes, and a longitudinal timeline in one workspace.
- **Collaboration** — Commons channels with threaded discussions, presence,
  reactions, and notifications; real-time transport via Laravel Reverb.
- **Decision support (Abby)** — evidence-grounded decision drafting plus
  LLM-advisory trial matching, guideline concordance, drug-interaction and
  variant interpretation, and "Patients Like This" similarity.
- **Structured decision capture** — recommendations, votes, finalization, and
  follow-up tracking with audit trails.

It is open-source, vendor-agnostic, and standards-based: **FHIR R4**, **OMOP
CDM v5.4**, and GA4GH interoperability surfaces (**Matchmaker Exchange**,
**Beacon v2**, **Phenopackets v2**, ACMG/AMP, ClinVar/ClinGen).

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.4+, Sanctum auth, Spatie RBAC |
| Frontend | React 19, TypeScript (strict), Vite 6, Tailwind 4, Zustand, TanStack Query |
| AI service | Python 3.12 (FastAPI), SapBERT, Ollama/MedGemma, Claude API |
| Federation | Python FastAPI relay (mTLS, opt-in) |
| Database | PostgreSQL 16 + pgvector (host PG; `app`/`clinical`/`public` schemas) |
| Real-time | Laravel Reverb (Pusher-protocol) |
| Infra | Docker Compose (dev) / Apache + host php-fpm static serving (prod) |

See `README.md` for the public-facing description and `.claude/CLAUDE.md` for
the developer context.

## Current Phase: GA Readiness

The original stabilization/verification milestone and the v2 feature build are
complete. Aurora is **demo-/UAT-ready (~80–85% feature-complete)** and is now in
a **GA-readiness hardening phase** driven by `.planning/GA-READINESS-PLAN.md`.

"GA" here means **safe, supportable, observable production for research/MDT
use** — explicitly **not** FDA/CE regulated-medical-device clearance. Items that
would be required for device clearance are deferred behind "Research Use Only"
labeling (`[OUT-OF-GA]` in the plan).

### Where things stand

- **Real-time collaboration (W1):** Reverb is wired end-to-end — broadcasting
  events, channel authorization, real `echo.ts`, presence/typing, a polling
  fallback, and a backend broadcast test + a two-context Playwright spec.
  Production *activation* (systemd unit + Apache WS proxy) is authored and
  handed to the operator (requires sudo).
- **CI integrity (W0):** the `continue-on-error` masks on mypy / npm-audit /
  pip-audit are removed, E2E now gates `main` (not PR-only), and a gitleaks
  secret-scan job hard-fails on findings. Backend/frontend/AI coverage floors
  are still being ratcheted in.
- **Security model (W2 + threat model):** implemented per decisions **D1/D2**
  recorded in `docs/security/threat-model.md`:
  - **D1 = open clinical workspace** — cases are team-scoped (CasePolicy);
    patient/genomics/imaging/odyssey records are broadly visible to
    authenticated clinical users, with **PHI-access audit logging** as the
    compensating control (W2-T11, open).
  - **D2 = internal identified, external de-identified** — internal FHIR/exports
    stay identified; MME + Beacon are de-identified and k-anonymized.
  - Security headers, rate limiting, secret-at-boot validation, and Orthanc
    credential externalization are done; credential rotation + git-history
    scrub remain operator tasks.
- **Standards & interoperability:** FHIR/OMOP adapter read projections and the
  first outbound FHIR Genomics report export are shipped; FHIR conformance
  validation and inbound parsing are open (W6).

The honest status: feature-rich but unevenly productized; several **GA blockers**
and **operator items** remain open. Track them in the GA plan, not here.

## Key Constraints

- **Research Use Only.** Mock/advisory surfaces (e.g. mock segmentation,
  LLM-only CDS) must be labeled or hidden; no stub presents as authoritative.
- **Auth system is frozen.** Read `.claude/rules/auth-system.md` before touching
  anything under auth — the temp-password/Resend flow and the forced
  `must_change_password` modal are sacred. Additions only.
- **Non-destructive only.** Soft deletes; no `migrate --force`; scoped `--path=`
  migrations; never modify the read-only `omop` schema.
- **Host uses `sudo-rs`** (no `-A`). Elevated operations are handed to the
  operator — do not attempt interactive sudo.
- **Tooling gates:** Pint (PHP); both `tsc --noEmit` and `vite build` (TS);
  ruff + mypy (Python).

## Authoritative Roadmap

`.planning/GA-READINESS-PLAN.md` — workstreams W0–W14, status markers, decisions
D1/D2, sequencing (GA-0 harness → GA-1 security+realtime → GA-2 operability →
GA-3 honest product surface → GA-4 GA cut), and GA exit criteria.

---
*Supersedes the 2026-03-25 "Stabilization & Verification" PROJECT.md.*
