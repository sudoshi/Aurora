<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFingerprint extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'patient_fingerprints';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'genomic_available' => 'boolean',
            'volumetric_available' => 'boolean',
            'clinical_available' => 'boolean',
            'genomic_confidence' => 'decimal:4',
            'volumetric_confidence' => 'decimal:4',
            'clinical_confidence' => 'decimal:4',
            'genomic_encoded_at' => 'datetime',
            'volumetric_encoded_at' => 'datetime',
            'clinical_encoded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function getDimensionMaskAttribute(): array
    {
        return [$this->genomic_available, $this->volumetric_available, $this->clinical_available];
    }

    public function getAvailableDimensionCountAttribute(): int
    {
        return (int) $this->genomic_available + (int) $this->volumetric_available + (int) $this->clinical_available;
    }
}
