<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.kb_change_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genomic_variant_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('source')->default('clinvar');
            $table->string('clinvar_variation_id')->nullable();
            $table->string('from_bucket');
            $table->string('to_bucket');
            $table->integer('from_stars')->default(0);
            $table->integer('to_stars')->default(0);
            $table->string('severity');
            $table->jsonb('evidence')->nullable();
            $table->string('delta_hash')->unique();
            $table->string('status')->default('new');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->foreign('genomic_variant_id')->references('id')->on('clinical.genomic_variants')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('app.patient_tasks')->nullOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('app.users');
            $table->index(['patient_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.kb_change_alerts');
    }
};
