<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class AcmgGeneSpecification extends Model
{
    protected $table = 'clinical.acmg_gene_specifications';

    protected $fillable = [
        'gene_symbol', 'disease', 'vcep', 'spec_id', 'spec_version', 'criteria_overrides', 'source_url',
    ];

    protected function casts(): array
    {
        return ['criteria_overrides' => 'array'];
    }
}
