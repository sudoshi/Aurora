<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genomic_variants', function (Blueprint $table) {
            $table->text('actionability')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('genomic_variants', function (Blueprint $table) {
            $table->string('actionability', 30)->nullable()->change();
        });
    }
};
