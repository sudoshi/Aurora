<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificationCriterion extends Model
{
    use HasFactory;

    protected $table = 'clinical.classification_criteria';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\ClassificationCriterionFactory::new();
    }

    protected $fillable = [
        'classification_id', 'code', 'applied_strength', 'points',
        'data_source', 'evidence_value', 'rationale', 'set_by', 'set_by_user_id',
    ];

    protected function casts(): array
    {
        return ['points' => 'integer'];
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VariantClassification::class, 'classification_id');
    }
}
