<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutcomeTrajectory extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'outcome_trajectories';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tumor_response_score' => 'decimal:4',
            'treatment_tolerance_score' => 'decimal:4',
            'lab_trajectory_score' => 'decimal:4',
            'disease_stability_score' => 'decimal:4',
            'care_intensity_score' => 'decimal:4',
            'composite_score' => 'decimal:4',
            'decision_tags' => 'array',
            'assessed_at' => 'datetime',
            'computed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function getSubScoresAttribute(): array
    {
        return [
            'tumor_response' => $this->tumor_response_score,
            'treatment_tolerance' => $this->treatment_tolerance_score,
            'lab_trajectory' => $this->lab_trajectory_score,
            'disease_stability' => $this->disease_stability_score,
            'care_intensity' => $this->care_intensity_score,
        ];
    }
}
