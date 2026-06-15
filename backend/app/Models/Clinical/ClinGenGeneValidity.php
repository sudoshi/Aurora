<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinGenGeneValidity extends Model
{
    use HasFactory;

    protected $table = 'clinical.clingen_gene_validity';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\ClinGenGeneValidityFactory::new();
    }

    protected $fillable = [
        'gene_symbol',
        'disease_label',
        'disease_id',
        'moi',
        'classification',
        'baseline_classification',
        'classification_date',
        'report_url',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'classification_date' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }
}
