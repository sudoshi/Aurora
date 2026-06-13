<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class FusionWeightConfig extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'fusion_weight_configs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'genomic_weight' => 'decimal:4',
            'volumetric_weight' => 'decimal:4',
            'clinical_weight' => 'decimal:4',
            'outcome_weights' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePresets($query)
    {
        return $query->where('config_type', 'preset');
    }

    public function getDimensionWeightsAttribute(): array
    {
        return [
            'genomic' => (float) $this->genomic_weight,
            'volumetric' => (float) $this->volumetric_weight,
            'clinical' => (float) $this->clinical_weight,
        ];
    }
}
