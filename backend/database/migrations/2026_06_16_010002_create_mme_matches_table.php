<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.mme_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odyssey_id')->constrained('app.diagnostic_odysseys')->cascadeOnDelete();
            $table->string('direction');                    // outbound | inbound
            $table->foreignId('peer_id')->nullable()->constrained('app.mme_peers')->nullOnDelete();
            $table->decimal('score', 5, 4);
            $table->string('matched_label')->nullable();
            $table->string('matched_contact_name')->nullable();
            $table->string('matched_contact_href')->nullable();
            $table->jsonb('matched_profile');
            $table->string('status')->default('new');       // new | reviewed | contacted | dismissed
            $table->timestamps();
            $table->index(['odyssey_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.mme_matches');
    }
};
