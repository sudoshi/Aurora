<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical.imaging_measurements', function (Blueprint $table) {
            $table->foreignId('imaging_series_id')
                ->nullable()
                ->after('imaging_study_id')
                ->constrained('clinical.imaging_series')
                ->nullOnDelete();
            $table->string('measurement_name')->nullable()->after('measurement_type');
            $table->string('body_site')->nullable()->after('unit');
            $table->string('laterality', 30)->nullable()->after('body_site');
            $table->string('algorithm_name')->nullable()->after('measured_by');
            $table->decimal('confidence', 5, 4)->nullable()->after('algorithm_name');
            $table->unsignedInteger('target_lesion_number')->nullable()->after('target_lesion');

            $table->index('imaging_series_id');
            $table->index('body_site');
        });
    }

    public function down(): void
    {
        Schema::table('clinical.imaging_measurements', function (Blueprint $table) {
            $table->dropForeign(['imaging_series_id']);
            $table->dropIndex(['imaging_series_id']);
            $table->dropIndex(['body_site']);
            $table->dropColumn([
                'imaging_series_id',
                'measurement_name',
                'body_site',
                'laterality',
                'algorithm_name',
                'confidence',
                'target_lesion_number',
            ]);
        });
    }
};
