<?php

namespace App\Models;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosticOdyssey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app.diagnostic_odysseys';

    protected $fillable = [
        'patient_id',
        'case_id',
        'title',
        'status',
        'progress_status',
        'referral_reason',
        'created_by',
        'solved_at',
    ];

    protected function casts(): array
    {
        return [
            'solved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(OdysseyStatusTransition::class, 'odyssey_id');
    }

    public function phenotypeFeatures(): HasMany
    {
        return $this->hasMany(PhenotypeFeature::class, 'odyssey_id');
    }
}
