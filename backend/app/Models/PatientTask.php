<?php

namespace App\Models;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientTask extends Model
{
    use HasFactory;

    protected $table = 'app.patient_tasks';

    protected $fillable = [
        'patient_id',
        'created_by',
        'assigned_to',
        'domain',
        'record_ref',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }
}
