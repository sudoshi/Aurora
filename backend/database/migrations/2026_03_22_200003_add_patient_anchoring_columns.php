<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Decisions: add patient_id + record_refs
        Schema::table('app.decisions', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('session_id');
            $table->jsonb('record_refs')->nullable()->after('urgency');
            $table->foreign('patient_id')->references('id')->on('clinical.patients');
            $table->index('patient_id');
        });

        // Backfill decisions.patient_id from cases
        DB::statement('
            UPDATE app.decisions d
            SET patient_id = c.patient_id
            FROM app.cases c
            WHERE d.case_id = c.id AND c.patient_id IS NOT NULL
        ');

        // 2. Case discussions: add domain, record_ref, patient_id
        Schema::table('app.case_discussions', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('content');
            $table->string('record_ref')->nullable()->after('domain');
            $table->unsignedBigInteger('patient_id')->nullable()->after('record_ref');
            $table->foreign('patient_id')->references('id')->on('clinical.patients');
            $table->index(['patient_id', 'domain']);
        });

        DB::statement('
            UPDATE app.case_discussions d
            SET patient_id = c.patient_id
            FROM app.cases c
            WHERE d.case_id = c.id AND c.patient_id IS NOT NULL
        ');

        // 3. Case annotations: add patient_id (domain and record_ref already exist)
        Schema::table('app.case_annotations', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('anchored_to');
            $table->foreign('patient_id')->references('id')->on('clinical.patients');
            $table->index('patient_id');
        });

        DB::statement('
            UPDATE app.case_annotations a
            SET patient_id = c.patient_id
            FROM app.cases c
            WHERE a.case_id = c.id AND c.patient_id IS NOT NULL
        ');

        // 4. Follow-ups: add patient_id
        Schema::table('app.follow_ups', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('decision_id');
            $table->foreign('patient_id')->references('id')->on('clinical.patients');
        });

        DB::statement("CREATE INDEX idx_follow_ups_patient_pending ON app.follow_ups(patient_id) WHERE status IN ('pending', 'in_progress')");

        DB::statement('
            UPDATE app.follow_ups f
            SET patient_id = c.patient_id
            FROM app.decisions d
            JOIN app.cases c ON d.case_id = c.id
            WHERE f.decision_id = d.id AND c.patient_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('app.follow_ups', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
        DB::statement('DROP INDEX IF EXISTS app.idx_follow_ups_patient_pending');

        Schema::table('app.case_annotations', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn('patient_id');
        });

        Schema::table('app.case_discussions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id', 'domain']);
            $table->dropColumn(['domain', 'record_ref', 'patient_id']);
        });

        Schema::table('app.decisions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn(['patient_id', 'record_refs']);
        });
    }
};
