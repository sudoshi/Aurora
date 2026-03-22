<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseDiscussion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app.case_discussions';

    protected $fillable = [
        'case_id',
        'user_id',
        'parent_id',
        'content',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CaseDiscussion::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CaseDiscussion::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DiscussionAttachment::class, 'discussion_id');
    }
}
