<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class EvidenceUpdate extends Model
{
    public $timestamps = false;
    protected $connection = 'pgsql';
    protected $table = 'clinical.evidence_updates';

    protected $fillable = [
        'source', 'action', 'entity_type', 'entity_id',
        'old_value', 'new_value',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];
}
