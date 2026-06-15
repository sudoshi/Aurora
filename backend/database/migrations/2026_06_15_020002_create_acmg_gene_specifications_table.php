<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.acmg_gene_specifications', function (Blueprint $table) {
            $table->id();
            $table->string('gene_symbol');
            $table->string('disease')->nullable();
            $table->string('vcep')->nullable();
            $table->string('spec_id');
            $table->string('spec_version');
            $table->jsonb('criteria_overrides');
            $table->string('source_url')->nullable();
            $table->timestamps();

            $table->unique(['gene_symbol', 'spec_id', 'spec_version']);
            $table->index('gene_symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.acmg_gene_specifications');
    }
};
