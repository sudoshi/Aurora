<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('app.roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('app.model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('app.permissions')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'model_id', 'model_type']);

            $table->index(['model_id', 'model_type'], 'mhp_model_id_model_type_index');
        });

        Schema::create('app.model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->foreign('role_id')
                ->references('id')
                ->on('app.roles')
                ->onDelete('cascade');

            $table->primary(['role_id', 'model_id', 'model_type']);

            $table->index(['model_id', 'model_type'], 'mhr_model_id_model_type_index');
        });

        Schema::create('app.role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('app.permissions')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('app.roles')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Schema::dropIfExists('app.role_has_permissions');
        Schema::dropIfExists('app.model_has_roles');
        Schema::dropIfExists('app.model_has_permissions');
        Schema::dropIfExists('app.roles');
        Schema::dropIfExists('app.permissions');
    }
};
