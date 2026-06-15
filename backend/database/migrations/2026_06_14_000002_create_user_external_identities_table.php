<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.user_external_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 32);
            $table->string('provider_subject', 255);
            $table->string('provider_email_at_link', 255)->nullable();
            $table->timestamp('linked_at');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('app.users')
                ->cascadeOnDelete();

            $table->unique(['provider', 'provider_subject']);
            $table->index(['provider', 'provider_email_at_link']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.user_external_identities');
    }
};
