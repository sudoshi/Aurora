<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionVote extends Model
{
    use HasFactory;

    protected $table = 'app.decision_votes';

    protected $fillable = [
        'decision_id',
        'user_id',
        'vote',
        'comment',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
