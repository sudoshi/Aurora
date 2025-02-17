<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Creating discussion_attachments table');

        Schema::create('dev.discussion_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discussion_id');
            $table->string('filename');
            $table->string('filepath');
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->timestamps();

            $table->foreign('discussion_id')->references('id')->on('dev.case_discussions')->onDelete('cascade');
        });

        Log::info('Discussion_attachments table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping discussion_attachments table');
        Schema::dropIfExists('dev.discussion_attachments');
    }
};
