<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical.imaging_measurements', function (Blueprint $table) {
            // Discriminator distinguishing clinician-entered measurements from
            // AI/algorithm-computed ones so the UI never presents a computed
            // value as a clinician's. New rows always set this explicitly;
            // nullable here only to allow the best-effort backfill below to run
            // before any NOT NULL expectation is imposed by application code.
            $table->string('source', 20)->nullable()->after('measured_by');
            $table->index('source');
        });

        // Best-effort backfill for legacy rows: anything carrying an
        // algorithm_name was algorithm-produced (computed); everything else is
        // treated as clinician-entered. This is a heuristic for pre-existing
        // data only — going forward source is set at creation time.
        DB::statement(<<<'SQL'
            UPDATE clinical.imaging_measurements
            SET source = CASE
                WHEN algorithm_name IS NOT NULL THEN 'computed'
                ELSE 'clinician'
            END
            WHERE source IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('clinical.imaging_measurements', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
