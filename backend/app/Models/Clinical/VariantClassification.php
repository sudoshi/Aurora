<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantClassification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clinical.variant_classifications';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\VariantClassificationFactory::new();
    }

    protected $fillable = [
        'genomic_variant_id', 'gene_symbol', 'computed_classification', 'computed_points',
        'final_classification', 'status', 'ruleset_version', 'gene_specification_id',
        'override_reason', 'created_by', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return ['computed_points' => 'integer', 'confirmed_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GenomicVariant::class, 'genomic_variant_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ClassificationCriterion::class, 'classification_id');
    }
}
