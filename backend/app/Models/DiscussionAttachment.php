<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionAttachment extends Model
{
    use HasFactory;

    protected $table = 'app.discussion_attachments';

    protected $fillable = [
        'discussion_id',
        'filename',
        'filepath',
        'mime_type',
        'size',
    ];

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CaseDiscussion::class, 'discussion_id');
    }
}
