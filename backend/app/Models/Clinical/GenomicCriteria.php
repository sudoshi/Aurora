<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenomicCriteria extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'clinical.genomic_criteria';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\GenomicCriteriaFactory::new();
    }

    protected $fillable = [
        'name',
        'criteria_type',
        'criteria_definition',
        'description',
        'is_shared',
        'created_by',
    ];

    protected $casts = [
        'criteria_definition' => 'array',
        'is_shared' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
