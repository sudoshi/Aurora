<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmeMatch extends Model
{
    use HasFactory;

    protected $table = 'app.mme_matches';

    protected static function newFactory()
    {
        return \Database\Factories\MmeMatchFactory::new();
    }

    protected $fillable = [
        'odyssey_id',
        'direction',
        'peer_id',
        'score',
        'matched_label',
        'matched_contact_name',
        'matched_contact_href',
        'matched_profile',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'matched_profile' => 'array',
            'score' => 'float',
        ];
    }

    public function odyssey(): BelongsTo
    {
        return $this->belongsTo(DiagnosticOdyssey::class, 'odyssey_id');
    }

    public function peer(): BelongsTo
    {
        return $this->belongsTo(MmePeer::class, 'peer_id');
    }
}
