<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.mme_peers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->text('auth_token');               // encrypted at the model layer (text, not jsonb)
            $table->string('direction')->default('both');   // inbound | outbound | both
            $table->boolean('active')->default(true);
            $table->string('contact_email')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.mme_peers');
    }
};
