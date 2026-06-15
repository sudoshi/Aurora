<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GenomicVariant extends Model
{
    use HasFactory;

    protected $table = 'genomic_variants';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\GenomicVariantFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allele_frequency' => 'decimal:6',
        ];
    }

    /**
     * HGNC-aligned alias for the underlying `gene` column. ACMG classification
     * (GeneSpecificationResolver, ClinVar same-residue matching) keys on the
     * HGNC symbol, so expose `gene_symbol` without duplicating the column.
     */
    protected function geneSymbol(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes['gene'] ?? null,
            set: fn (?string $value): array => ['gene' => $value],
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function canonicalId(): HasOne
    {
        return $this->hasOne(VariantCanonicalId::class, 'genomic_variant_id');
    }
}
