<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionCase extends Model
{
    use HasFactory;

    protected $table = 'app.session_cases';

    protected $fillable = [
        'session_id',
        'case_id',
        'order',
        'presenter_id',
        'time_allotted_minutes',
        'status',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }
}
