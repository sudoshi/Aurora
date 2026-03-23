<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.patient_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('flagged_by');
            $table->string('domain'); // condition, medication, procedure, measurement, observation, genomic, imaging, general
            $table->string('record_ref'); // e.g., "genomic:42"
            $table->string('severity')->default('attention'); // critical, attention, informational
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('flagged_by')->references('id')->on('app.users');
            $table->foreign('resolved_by')->references('id')->on('app.users');

            $table->index('patient_id');
            $table->index(['patient_id', 'domain']);
        });

        // Partial index for unresolved flags
        DB::statement('CREATE INDEX idx_patient_flags_unresolved ON app.patient_flags(patient_id) WHERE resolved_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('app.patient_flags');
    }
};
