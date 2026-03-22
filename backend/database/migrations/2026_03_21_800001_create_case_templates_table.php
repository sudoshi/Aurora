<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.case_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('specialty');
            $table->string('case_type');
            $table->text('description');
            $table->text('clinical_question_prompt');
            $table->jsonb('recommended_tabs');
            $table->jsonb('decision_types');
            $table->jsonb('guideline_sets');
            $table->jsonb('default_team_roles');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.case_templates');
    }
};
