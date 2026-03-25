<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.genomic_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 500);
            $table->string('stored_path', 1000);
            $table->string('file_format', 50);
            $table->string('genome_build', 20)->default('GRCh38');
            $table->string('sample_id', 200)->nullable();
            $table->string('status', 50)->default('uploaded');
            $table->unsignedInteger('total_variants')->default(0);
            $table->unsignedInteger('mapped_variants')->default(0);
            $table->unsignedInteger('unmapped_variants')->default(0);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('app.users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.genomic_uploads');
    }
};
