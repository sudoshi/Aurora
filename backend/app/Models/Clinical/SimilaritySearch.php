<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilaritySearch extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'similarity_searches';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'weights_used' => 'array',
            'weights_customized' => 'boolean',
            'result_patient_ids' => 'array',
            'result_scores' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function queryPatient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'query_patient_id');
    }

    public function searcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'searched_by');
    }
}
