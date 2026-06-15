<?php

namespace App\Models\Clinical;

use App\Models\PatientTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbChangeAlert extends Model
{
    use HasFactory;

    protected $table = 'clinical.kb_change_alerts';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\KbChangeAlertFactory::new();
    }

    protected $fillable = [
        'genomic_variant_id', 'patient_id', 'source', 'clinvar_variation_id',
        'from_bucket', 'to_bucket', 'from_stars', 'to_stars', 'severity', 'evidence',
        'delta_hash', 'status', 'task_id', 'acknowledged_by', 'acknowledged_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'from_stars' => 'integer', 'to_stars' => 'integer', 'acknowledged_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GenomicVariant::class, 'genomic_variant_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(PatientTask::class, 'task_id');
    }
}
