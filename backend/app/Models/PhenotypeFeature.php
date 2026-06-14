<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhenotypeFeature extends Model
{
    use HasFactory;

    protected $table = 'app.phenotype_features';

    protected $fillable = [
        'odyssey_id',
        'hpo_id',
        'hpo_label',
        'excluded',
        'onset_hpo_id',
        'severity_hpo_id',
        'frequency_hpo_id',
        'evidence',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'excluded' => 'boolean',
        ];
    }

    public function odyssey(): BelongsTo
    {
        return $this->belongsTo(DiagnosticOdyssey::class, 'odyssey_id');
    }
}
