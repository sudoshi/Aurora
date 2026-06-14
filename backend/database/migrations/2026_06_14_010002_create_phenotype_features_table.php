<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.phenotype_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odyssey_id');
            $table->string('hpo_id');            // e.g. "HP:0001250"
            $table->string('hpo_label');
            $table->boolean('excluded')->default(false); // negation: phenotype explicitly absent
            $table->string('onset_hpo_id')->nullable();
            $table->string('severity_hpo_id')->nullable();
            $table->string('frequency_hpo_id')->nullable();
            $table->string('evidence')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('odyssey_id')->references('id')->on('app.diagnostic_odysseys')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('app.users');

            $table->unique(['odyssey_id', 'hpo_id']);
            $table->index('odyssey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.phenotype_features');
    }
};
