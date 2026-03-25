<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenomicUpload extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'clinical.genomic_uploads';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\GenomicUploadFactory::new();
    }

    protected $fillable = [
        'original_filename',
        'stored_path',
        'file_format',
        'genome_build',
        'sample_id',
        'status',
        'total_variants',
        'mapped_variants',
        'unmapped_variants',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'total_variants' => 'integer',
        'mapped_variants' => 'integer',
        'unmapped_variants' => 'integer',
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
