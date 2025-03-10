<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'dev.events';

    protected $fillable = [
        'title',
        'time',
        'duration',
        'location',
        'category',
        'description',
        'team',
        'related_items'
    ];

    protected $casts = [
        'time' => 'datetime',
        'team' => 'array',
        'related_items' => 'array',
    ];


    /**
     * The team members associated with this event
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dev.event_team_members', 'event_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * The patients associated with this event
     */
    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'dev.event_patients', 'event_id', 'patient_id')
                    ->withTimestamps();
    }
}
