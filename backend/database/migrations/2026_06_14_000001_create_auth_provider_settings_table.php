<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.auth_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type')->unique();
            $table->string('display_name');
            $table->boolean('is_enabled')->default(false);
            $table->integer('priority')->default(0);
            $table->text('settings')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')
                ->references('id')
                ->on('app.users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.auth_provider_settings');
    }
};
