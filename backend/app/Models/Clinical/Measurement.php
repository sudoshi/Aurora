<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    protected $table = 'measurements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:6',
            'reference_range_low' => 'decimal:6',
            'reference_range_high' => 'decimal:6',
            'measured_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }
}
