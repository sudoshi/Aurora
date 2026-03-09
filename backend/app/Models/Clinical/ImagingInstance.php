<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingInstance extends Model
{
    protected $table = 'imaging_instances';

    protected $guarded = [];

    public function imagingSeries(): BelongsTo
    {
        return $this->belongsTo(ImagingSeries::class, 'imaging_series_id');
    }
}
