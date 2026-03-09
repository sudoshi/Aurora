<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionAttachment extends Model
{
    use HasFactory;

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CaseDiscussion::class);
    }
}
