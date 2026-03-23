<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAnnotation extends Model
{
    use HasFactory;

    protected $table = 'app.case_annotations';

    protected $fillable = [
        'case_id',
        'user_id',
        'domain',
        'record_ref',
        'content',
        'anchored_to',
        'patient_id',
    ];

    protected function casts(): array
    {
        return [
            'anchored_to' => 'array',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Clinical\ClinicalPatient::class, 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
