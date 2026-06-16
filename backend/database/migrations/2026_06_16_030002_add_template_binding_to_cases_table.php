<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** case_type/specialty → template slug */
    private array $map = [
        'tumor_board' => 'oncology-tumor-board',
        'surgical_review' => 'complex-surgical-planning',
        'surgical_planning' => 'complex-surgical-planning',
        'rare_disease' => 'rare-disease-diagnostic-odyssey',
        'diagnostic_odyssey' => 'rare-disease-diagnostic-odyssey',
        'medical_complex' => 'complex-medical-case-review',
        'medical_review' => 'complex-medical-case-review',
    ];

    public function up(): void
    {
        Schema::table('app.cases', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('case_type');
            $table->string('state')->nullable()->after('status');
            $table->jsonb('structured_data')->default('{}');

            $table->foreign('template_id')->references('id')->on('app.case_templates')->onDelete('set null');
            $table->index('template_id');
        });

        $default = DB::table('app.case_templates')->where('slug', 'oncology-tumor-board')->value('id');
        foreach ($this->map as $caseType => $slug) {
            $id = DB::table('app.case_templates')->where('slug', $slug)->value('id');
            if ($id) {
                DB::table('app.cases')->where('case_type', $caseType)->whereNull('template_id')->update(['template_id' => $id]);
            }
        }
        if ($default) {
            DB::table('app.cases')->whereNull('template_id')->update(['template_id' => $default]);
        }
    }

    public function down(): void
    {
        Schema::table('app.cases', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'state', 'structured_data']);
        });
    }
};
