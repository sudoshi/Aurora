<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseTemplate extends Model
{
    protected $table = 'app.case_templates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recommended_tabs' => 'array',
            'decision_types' => 'array',
            'guideline_sets' => 'array',
            'default_team_roles' => 'array',
        ];
    }
}
