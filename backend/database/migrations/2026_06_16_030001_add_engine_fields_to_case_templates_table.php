<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app.case_templates', function (Blueprint $table) {
            $table->string('time_model')->default('episodic'); // episodic | episode_of_care | longitudinal | diagnostic_odyssey
            $table->jsonb('data_schema')->default('[]');
            $table->jsonb('candidacy_rubric')->nullable();
            $table->jsonb('agenda')->default('[]');
            $table->jsonb('state_machine')->nullable();
            $table->boolean('is_active')->default(true);
        });

        DB::statement(
            'ALTER TABLE app.case_templates ADD CONSTRAINT case_templates_time_model_check '.
            "CHECK (time_model IN ('episodic','episode_of_care','longitudinal','diagnostic_odyssey'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE app.case_templates DROP CONSTRAINT IF EXISTS case_templates_time_model_check');
        Schema::table('app.case_templates', function (Blueprint $table) {
            $table->dropColumn(['time_model', 'data_schema', 'candidacy_rubric', 'agenda', 'state_machine', 'is_active']);
        });
    }
};
