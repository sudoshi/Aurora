<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseDocument extends Model
{
    use HasFactory;

    protected $table = 'app.case_documents';

    protected $fillable = [
        'case_id',
        'uploaded_by',
        'filename',
        'filepath',
        'mime_type',
        'size',
        'document_type',
        'description',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
