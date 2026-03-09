<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingSegmentation extends Model
{
    protected $table = 'imaging_segmentations';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'volume_mm3' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'imaging_study_id');
    }
}
