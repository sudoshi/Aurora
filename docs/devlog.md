# Aurora Devlog

## 2026-03-24 — Case-Patient Integration + Fully Dockerized Dev Environment

**Branch:** `v2/phase-0-scaffold`

### Case-Patient Profile Integration

Eliminated the context switch between case review and patient data. Clinicians reviewing a case at `/cases/:id` now see the full patient profile directly in the Overview tab — no more navigating away to `/profiles/:personId`.

**What was built:**
- **Collapsible case context header**: clinical question, summary, case details, and activity stats promoted from the Overview tab into a collapsible section in the case header
- **Embedded patient profile**: Overview tab renders all 9 patient view modes (Briefing, Timeline, List, Labs, Visits, Notes, Imaging, Genomics, Similar Patients) using existing profile components — zero duplication
- **No patient fallback**: cases without a linked patient show a "Link Patient" prompt that opens the edit form
- **CaseForm patient_id field**: new optional field to link a patient to a case
- **Shared CSV utility**: extracted `downloadEventsAsCsv` to `patient-profile/utils/csvExport.ts` for reuse
- **Full profile link**: "Full profile" link on the demographics card navigates to the standalone profile page
- Collaboration panel (Cmd/Ctrl+Shift+C) works within the case context

**Files changed:** CaseDetailPage.tsx (major rewrite), CaseForm.tsx, csvExport.ts (new), PatientProfilePage.tsx (import refactor)

### Fully Dockerized Dev Environment

Replaced the broken dual-setup (Apache direct-serve + incomplete Docker) with a single `docker compose up` that serves everything.

**What was built:**
- **docker-compose.yml rewrite**: removed Docker Postgres (use host), fixed PHP volume (`./backend:/var/www/html`), fixed Node volume (`./frontend:/app` + anonymous `node_modules`), activated Vite dev server, added `host.docker.internal` access
- **nginx multi-upstream routing**: `/api/*` → PHP-FPM, `/orthanc/*` → host Orthanc with auth + CORS, `/@vite/*` + `/ws` → Vite HMR, everything else → Vite SPA
- **PHP entrypoint script**: auto-installs composer deps on first run, clears caches, then exec's php-fpm
- **Vite config**: `host: 0.0.0.0`, port 5173, conditional `base` (`/` dev, `/build/` prod), `allowedHosts` for aurora.acumenus.net
- **Apache → reverse proxy**: replaced DirectoryRoot/PHP handler with simple ProxyPass to Docker nginx on :8085, WebSocket support for HMR
- **Host Postgres access**: `pg_hba.conf` allows Docker bridge network, PHP connects via `host.docker.internal`

**Result:** Both `localhost:8085` and `https://aurora.acumenus.net` serve the same Docker stack with Vite HMR, API, and Orthanc proxy working.

**Files changed:** docker-compose.yml, docker/nginx/default.conf, docker/php/Dockerfile, docker/php/entrypoint.sh (new), frontend/vite.config.ts, .env.example (new), Apache vhost

---

## 2026-03-22 — Action-Oriented Patient Experience Redesign

**Branch:** `v2/phase-0-scaffold`

### What was built

Transformed Aurora's patient views from passive data browsers (ported from Parthenon) into an action-oriented clinical collaboration surface. 23 commits across 4 phases.

**Phase 1: Schema + Briefing**
- New tables: `app.patient_flags`, `app.patient_tasks`
- Extended `decisions`, `case_discussions`, `case_annotations`, `follow_ups` with `patient_id` + anchoring fields
- `ValidRecordRef` validation rule for `domain:id` format
- 3 new controllers: PatientFlagController, PatientTaskController, PatientCollaborationController
- 10 new API endpoints for flags, tasks, collaboration aggregate, decisions
- Frontend: collaboration types, API layer, TanStack Query hooks
- **PatientBriefing**: 4-quadrant dashboard (Active Problems, Flagged Findings, Pending Actions, Recent Decisions) — now the default landing view, replacing Timeline

**Phase 2: Inline Actions**
- InlineActionMenu: three-dot context menu with inline flag/task creation forms
- SelectActToolbar: floating batch-action toolbar with framer-motion animation
- Added to all 5 data views: Genomics (with checkbox selection), Labs (with checkbox selection), Notes, Visits, Imaging

**Phase 3: Collaboration Panel**
- CollaborationPanel: 320px slide-out right panel, domain-sensitive filtering
- 4 panel tabs: Discussions, Tasks+FollowUps, Flags, Decisions — wired to live data
- Keyboard shortcut: Cmd/Ctrl+Shift+C
- Main content adjusts width when panel is open

**Phase 4: Session Agenda**
- SessionAgenda: multi-case ordered agenda with reorder, status tracking, patient links
- SessionDecisionLog: per-case decision display with voting tallies
- CaseDetailPage simplified to 3 tabs (Overview, Documents, Team) with "Open Patient" link

### Also committed
- ClinVar integration (sync service, models, API endpoints)
- TCIA demo patient seeder with clinical data
- GenomicsController expanded with ClinVar search/sync
- Various frontend fixes (imports, null guards, timeline improvements)

### API testing results (aurora.acumenus.net)
- All 10 new endpoints verified working (GET, POST, PATCH, DELETE)
- Flag create/resolve cycle tested
- Task create/complete cycle tested
- Collaboration aggregate returns all 5 collections
- Frontend served at 200
