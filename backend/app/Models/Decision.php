<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Decision extends Model
{
    use HasFactory;

    protected $table = 'app.decisions';

    protected $fillable = [
        'case_id',
        'session_id',
        'patient_id',
        'proposed_by',
        'decision_type',
        'recommendation',
        'rationale',
        'guideline_reference',
        'status',
        'finalized_at',
        'finalized_by',
        'urgency',
        'record_refs',
    ];

    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
            'record_refs' => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Clinical\ClinicalPatient::class, 'patient_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(DecisionVote::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
