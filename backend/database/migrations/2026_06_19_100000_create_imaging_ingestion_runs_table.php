<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.imaging_ingestion_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_type', 50);
            $table->string('status', 30)->default('queued');
            $table->string('fingerprint', 64);
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->jsonb('parameters')->nullable();
            $table->jsonb('result')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('requested_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('studies_created')->default(0);
            $table->unsignedInteger('studies_updated')->default(0);
            $table->unsignedInteger('series_created')->default(0);
            $table->unsignedInteger('series_updated')->default(0);
            $table->unsignedInteger('studies_skipped')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('requested_by')->references('id')->on('app.users')->nullOnDelete();
            $table->index(['run_type', 'status']);
            $table->index('fingerprint');
        });

        DB::statement(
            "CREATE UNIQUE INDEX imaging_ingestion_runs_active_unique
             ON clinical.imaging_ingestion_runs (run_type, fingerprint)
             WHERE status IN ('queued', 'running')"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS clinical.imaging_ingestion_runs_active_unique');
        Schema::dropIfExists('clinical.imaging_ingestion_runs');
    }
};
