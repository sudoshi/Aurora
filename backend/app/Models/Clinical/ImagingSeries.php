<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImagingSeries extends Model
{
    protected $table = 'imaging_series';

    protected $guarded = [];

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'imaging_study_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(ImagingInstance::class, 'imaging_series_id');
    }
}
