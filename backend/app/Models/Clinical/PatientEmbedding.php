<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientEmbedding extends Model
{
    protected $table = 'patient_embeddings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'computed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
