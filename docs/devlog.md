# Aurora Devlog

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
