<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * W11-T02 — additive, reversible indexes on verified hot query paths.
 *
 * Raw `CREATE INDEX IF NOT EXISTS` / `DROP INDEX IF EXISTS` are used so the
 * migration is fully idempotent (safe to re-run) and schema-qualified
 * correctly: genomic_variants lives in `clinical`, user_audit_logs in `app`.
 *
 * Note: on very large tables (genomic_variants can grow into the millions of
 * rows once variant uploads scale), a production DBA may prefer
 * `CREATE INDEX CONCURRENTLY` to avoid an ACCESS EXCLUSIVE lock. That cannot
 * run inside Laravel's migration transaction, so plain indexes are used here;
 * at Aurora's current modest scale the brief lock is acceptable. Revisit if
 * row counts grow substantially before applying to a busy production table.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── clinical.genomic_variants ───────────────────────────────────────
        // Beacon v2 genomic-variant lookup (BeaconService::queryGVariants)
        // filters by chromosome + position + ref_allele + alt_allele. None of
        // these columns are indexed today (only patient_id, gene,
        // clinical_significance are). This composite covers the full Beacon
        // predicate and the leading column also helps chromosome-only scans.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_genomic_variants_locus '
            .'ON clinical.genomic_variants (chromosome, position, ref_allele, alt_allele)'
        );

        // Genomics upload ingestion + ClinVar annotation paths repeatedly run
        // `where('source_type', 'upload')` (GenomicUploadIngestionService,
        // GenomicsController). source_type is unindexed.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_genomic_variants_source_type '
            .'ON clinical.genomic_variants (source_type)'
        );

        // ── app.user_audit_logs ─────────────────────────────────────────────
        // Admin UserAuditController filters by `feature` and the stats endpoint
        // does `whereNotNull('feature')->groupBy('feature')`. Existing indexes
        // cover user_id/action/occurred_at but not feature.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_user_audit_logs_feature '
            .'ON app.user_audit_logs (feature)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS clinical.idx_genomic_variants_locus');
        DB::statement('DROP INDEX IF EXISTS clinical.idx_genomic_variants_source_type');
        DB::statement('DROP INDEX IF EXISTS app.idx_user_audit_logs_feature');
    }
};
