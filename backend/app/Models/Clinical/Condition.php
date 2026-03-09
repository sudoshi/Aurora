<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Condition extends Model
{
    protected $table = 'conditions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'onset_date' => 'date',
            'resolution_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
