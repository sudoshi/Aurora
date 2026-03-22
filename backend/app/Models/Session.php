<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app.clinical_sessions';

    protected $fillable = [
        'title',
        'description',
        'scheduled_at',
        'duration_minutes',
        'status',
        'session_type',
        'created_by',
        'institution_id',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(ClinicalCase::class, 'app.session_cases', 'session_id', 'case_id')
            ->withPivot('order', 'presenter_id', 'time_allotted_minutes', 'status')
            ->withTimestamps();
    }

    public function sessionCases(): HasMany
    {
        return $this->hasMany(SessionCase::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('status', 'completed')
            ->orderByDesc('ended_at');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'live');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('session_type', $type);
    }
}
