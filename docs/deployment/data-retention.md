# Data retention & lifecycle policy (W11-T06)

Last updated: 2026-06-20
Status: **policy + operational guidance.** Aurora is Research Use Only; retention
periods below are sensible defaults — confirm against the governing IRB / data
use agreement / institutional HIPAA policy before enforcing automated pruning.

> Principle: **audit and PHI-adjacent records are retained, not silently
> pruned.** Automated deletion of any clinical or audit data must be a
> deliberate, reviewed change with a signed-off retention period — this document
> sets the policy; it intentionally does **not** ship a destructive auto-pruner.

---

## Data classes & retention

| Data | Store | Default retention | Notes |
|------|-------|-------------------|-------|
| **PHI-access audit logs** (`user_audit_logs`: `phi.access`/`phi.write`) | Postgres `app.user_audit_logs` | **6 years** (HIPAA §164.316(b)(2)) | The D1 open-workspace compensating control. Do NOT prune below the regulatory window. Append-only. |
| **Activity / api_access logs** (`RecordUserActivity`) | same table | 1–2 years | Operational telemetry, not the compliance audit trail; safe to prune earlier than PHI-access rows if needed. |
| **Genomic upload files** (raw VCF/MAF/CSV) | `storage/` (FILESYSTEM_DISK) + `genomic_uploads` rows | Retain while the parent study/odyssey is active; archive after | Large blobs. Once variants are ingested (`genomic_upload_variants`), the raw file can be cold-archived; keep the DB row + provenance. |
| **Soft-deleted records** (cases, messages, etc. with `deleted_at`) | Postgres | 90 days recoverable, then hard-delete eligible | Aurora soft-deletes by default. A periodic hard-delete of rows soft-deleted > 90 days ago is acceptable *for non-PHI* tables; PHI tables follow the clinical-record window. |
| **Realtime / broadcast** | ephemeral (Reverb) | none | Not persisted. |
| **Failed jobs** (`failed_jobs`) | Postgres | 30 days | Prune after triage; see deploy/queue runbook. |
| **Request correlation logs** (`request_id` in app log) | log files / Loki | per log-rotation policy | Rotated by the host/Loki retention, not the DB. |
| **DICOM imaging** | Orthanc (external PACS) | governed by the PACS policy | Aurora indexes references; the pixel data lifecycle is Orthanc's. |

---

## Operational guidance

- **Backups** (see `backup-restore.md`) capture all of the above; backup
  retention (14 daily + offsite) is separate from live-data retention.
- **Queue:** set `QUEUE_CONNECTION=redis` in production (W11-T02b) so the worker
  doesn't poll Postgres; failed jobs still land in `failed_jobs` for triage.
- **When automated pruning is approved:** implement it as an explicit, scheduled
  Artisan command with (a) a configurable retention per data class, (b) a
  `--dry-run` default that only reports counts, (c) a hard floor preventing
  deletion of `user_audit_logs` inside the 6-year window, and (d) a devlog entry
  per run. Soft-delete (not hard-delete) wherever a record could still be needed.
- **PHI minimization:** the audit trail records *that* PHI was accessed (user,
  resource, timestamp) — it does not duplicate the PHI itself, so retaining it
  long-term is low-risk and compliance-positive.

## Open follow-ups
- Decide the institutional retention windows (replace the defaults above) — owner
  decision, tied to the IRB/DUA.
- Implement the reviewed pruning command once windows are signed off.
- Wire `failed_jobs` + soft-delete pruning into the ops schedule (low-risk subset
  first: `failed_jobs`, non-PHI soft-deletes).
