<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalPatient extends Model
{
    protected $table = 'patients';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'deceased_at' => 'datetime',
        ];
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PatientIdentifier::class, 'patient_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class, 'patient_id');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'patient_id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class, 'patient_id');
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'patient_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'patient_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'patient_id');
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class, 'patient_id');
    }

    public function imagingStudies(): HasMany
    {
        return $this->hasMany(ImagingStudy::class, 'patient_id');
    }

    public function genomicVariants(): HasMany
    {
        return $this->hasMany(GenomicVariant::class, 'patient_id');
    }

    public function conditionEras(): HasMany
    {
        return $this->hasMany(ConditionEra::class, 'patient_id');
    }

    public function drugEras(): HasMany
    {
        return $this->hasMany(DrugEra::class, 'patient_id');
    }
}
