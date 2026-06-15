<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantCanonicalId extends Model
{
    use HasFactory;

    protected $table = 'clinical.variant_canonical_ids';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\VariantCanonicalIdFactory::new();
    }

    protected $fillable = [
        'genomic_variant_id', 'caid', 'vrs_id', 'clinvar_variation_id', 'dbsnp_rs', 'assembly',
        'baseline_significance', 'baseline_review_status', 'baselined_at', 'canonicalized_at',
    ];

    protected function casts(): array
    {
        return ['baselined_at' => 'datetime', 'canonicalized_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GenomicVariant::class, 'genomic_variant_id');
    }
}
