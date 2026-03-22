<?php

namespace App\Models;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app.cases';

    protected $fillable = [
        'title',
        'specialty',
        'urgency',
        'status',
        'patient_id',
        'case_type',
        'clinical_question',
        'summary',
        'created_by',
        'institution_id',
        'scheduled_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(CaseTeamMember::class, 'case_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(CaseAnnotation::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(CaseDiscussion::class, 'case_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class, 'case_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeBySpecialty(Builder $query, string $specialty): Builder
    {
        return $query->where('specialty', $specialty);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId)
            ->orWhereHas('teamMembers', function (Builder $q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }
}
