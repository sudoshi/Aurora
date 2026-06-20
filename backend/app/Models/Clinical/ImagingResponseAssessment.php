<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingResponseAssessment extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'clinical.imaging_response_assessments';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\ImagingResponseAssessmentFactory::new();
    }

    protected $fillable = [
        'patient_id',
        'criteria_type',
        'assessment_date',
        'body_site',
        'baseline_study_id',
        'current_study_id',
        'baseline_value',
        'nadir_value',
        'current_value',
        'percent_change_from_baseline',
        'percent_change_from_nadir',
        'response_category',
        'rationale',
        'assessed_by',
        'is_confirmed',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'baseline_value' => 'decimal:6',
        'nadir_value' => 'decimal:6',
        'current_value' => 'decimal:6',
        'percent_change_from_baseline' => 'decimal:4',
        'percent_change_from_nadir' => 'decimal:4',
        'is_confirmed' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function baselineStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'baseline_study_id');
    }

    public function currentStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class, 'current_study_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
