<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'parsed_at',
        'matched_at',
        'imported_at',
        'clinvar_annotated_at',
        'error_message',
        'last_result',
    ];

    protected $casts = [
        'total_variants' => 'integer',
        'mapped_variants' => 'integer',
        'unmapped_variants' => 'integer',
        'file_size' => 'integer',
        'parsed_at' => 'datetime',
        'matched_at' => 'datetime',
        'imported_at' => 'datetime',
        'clinvar_annotated_at' => 'datetime',
        'last_result' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function stagedVariants(): HasMany
    {
        return $this->hasMany(GenomicUploadVariant::class, 'genomic_upload_id');
    }
}
