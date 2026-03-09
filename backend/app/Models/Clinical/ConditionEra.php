<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionEra extends Model
{
    protected $table = 'condition_eras';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'era_start' => 'date',
            'era_end' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
