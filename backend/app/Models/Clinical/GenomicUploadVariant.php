<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenomicUploadVariant extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'clinical.genomic_upload_variants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'patient_id' => 'integer',
            'position' => 'integer',
            'allele_frequency' => 'decimal:6',
            'raw_payload' => 'array',
        ];
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(GenomicUpload::class, 'genomic_upload_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
