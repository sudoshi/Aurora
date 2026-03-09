<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenomicVariant extends Model
{
    protected $table = 'genomic_variants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allele_frequency' => 'decimal:6',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
