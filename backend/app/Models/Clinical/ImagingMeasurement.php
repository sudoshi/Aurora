<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingMeasurement extends Model
{
    protected $table = 'imaging_measurements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:6',
            'target_lesion' => 'boolean',
            'confidence' => 'decimal:4',
            'measured_at' => 'datetime',
        ];
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'imaging_study_id');
    }

    public function imagingSeries(): BelongsTo
    {
        return $this->belongsTo(ImagingSeries::class, 'imaging_series_id');
    }
}
