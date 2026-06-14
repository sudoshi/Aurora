<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdysseyStatusTransition extends Model
{
    protected $table = 'app.odyssey_status_transitions';

    protected $fillable = [
        'odyssey_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
    ];

    public function odyssey(): BelongsTo
    {
        return $this->belongsTo(DiagnosticOdyssey::class, 'odyssey_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
