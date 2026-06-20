<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.imaging_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('criteria_type', 50);
            $table->jsonb('criteria_definition');
            $table->text('description')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('app.users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.imaging_criteria');
    }
};
