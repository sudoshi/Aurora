<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingIngestionRun extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'clinical.imaging_ingestion_runs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'result' => 'array',
            'requested_count' => 'integer',
            'processed_count' => 'integer',
            'studies_created' => 'integer',
            'studies_updated' => 'integer',
            'series_created' => 'integer',
            'series_updated' => 'integer',
            'studies_skipped' => 'integer',
            'errors_count' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
