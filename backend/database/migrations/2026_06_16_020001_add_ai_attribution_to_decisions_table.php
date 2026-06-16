<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app.decisions', function (Blueprint $table) {
            $table->boolean('ai_generated')->default(false);
            $table->string('ai_model')->nullable();
            $table->decimal('ai_confidence', 4, 3)->nullable();
            $table->text('ai_rationale')->nullable();
            $table->jsonb('ai_sources')->nullable();
            $table->timestamp('ai_drafted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app.decisions', function (Blueprint $table) {
            $table->dropColumn([
                'ai_generated',
                'ai_model',
                'ai_confidence',
                'ai_rationale',
                'ai_sources',
                'ai_drafted_at',
            ]);
        });
    }
};
