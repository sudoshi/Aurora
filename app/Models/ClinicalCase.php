<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class ClinicalCase extends Model
{
    use HasFactory;

    public function discussions(): HasMany
    {
        return $this->hasMany(CaseDiscussion::class, 'case_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'case_team_members', 'case_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }
}
