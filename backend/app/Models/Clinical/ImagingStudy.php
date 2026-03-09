<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImagingStudy extends Model
{
    protected $table = 'imaging_studies';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'study_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function series(): HasMany
    {
        return $this->hasMany(ImagingSeries::class, 'imaging_study_id');
    }

    public function imagingMeasurements(): HasMany
    {
        return $this->hasMany(ImagingMeasurement::class, 'imaging_study_id');
    }

    public function segmentations(): HasMany
    {
        return $this->hasMany(ImagingSegmentation::class, 'imaging_study_id');
    }
}
