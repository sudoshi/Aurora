<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.patient_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('domain')->nullable();
            $table->string('record_ref')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('app.users');
            $table->foreign('assigned_to')->references('id')->on('app.users');
            $table->foreign('completed_by')->references('id')->on('app.users');

            $table->index('patient_id');
            $table->index(['patient_id', 'domain']);
        });

        DB::statement("CREATE INDEX idx_patient_tasks_assigned ON app.patient_tasks(assigned_to) WHERE status IN ('pending', 'in_progress')");
    }

    public function down(): void
    {
        Schema::dropIfExists('app.patient_tasks');
    }
};
