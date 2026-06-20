<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingFeature extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'clinical.imaging_features';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:6',
            'confidence' => 'decimal:4',
            'requires_review' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'imaging_study_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
