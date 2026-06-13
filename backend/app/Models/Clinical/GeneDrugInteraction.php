<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneDrugInteraction extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\GeneDrugInteractionFactory::new();
    }

    protected $table = 'clinical.gene_drug_interactions';

    protected $fillable = [
        'gene', 'variant_pattern', 'drug', 'drug_class',
        'relationship', 'evidence_level', 'indication', 'mechanism',
        'source', 'source_url', 'oncokb_last_synced_at', 'last_verified_at',
    ];

    protected $casts = [
        'oncokb_last_synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    /**
     * Match interactions for a gene + optional specific variant.
     * If variant_pattern is '*', matches any pathogenic variant in that gene.
     * Otherwise, matches if the patient variant's hgvs_p contains the pattern (case-insensitive).
     */
    public function scopeForVariant($query, string $gene, ?string $hgvsP = null)
    {
        $query->where('gene', strtoupper($gene));

        if ($hgvsP) {
            $query->where(function ($q) use ($hgvsP) {
                $q->where('variant_pattern', '*')
                    ->orWhereRaw('LOWER(?) LIKE \'%\' || LOWER(variant_pattern) || \'%\'', [$hgvsP]);
            });
        } else {
            $query->where('variant_pattern', '*');
        }
    }
}
