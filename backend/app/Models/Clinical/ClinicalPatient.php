<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClinicalPatient extends Model
{
    protected $table = 'patients';

    protected $guarded = [];

    protected $appends = ['category'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'deceased_at' => 'datetime',
        ];
    }

    /**
     * Derive patient category from conditions.
     */
    public function getCategoryAttribute(): string
    {
        $conditions = $this->relationLoaded('conditions')
            ? $this->conditions->pluck('concept_name')->implode(' ')
            : $this->conditions()->pluck('concept_name')->implode(' ');

        $text = strtolower($conditions);

        // Oncology keywords
        $oncoTerms = ['carcinoma', 'adenocarcinoma', 'lymphoma', 'leukemia', 'melanoma',
            'sarcoma', 'myeloma', 'tumor', 'tumour', 'neoplasm', 'metastasis', 'metastases',
            'metastatic', 'cancer', 'oncolog', 'chemo', 'mastectomy', 'lumpectomy'];

        foreach ($oncoTerms as $term) {
            if (str_contains($text, $term)) {
                return 'oncology';
            }
        }

        // Rare disease keywords — specific named conditions, not generic terms like "syndrome"
        $rareTerms = ['hereditary', 'von hippel', 'tuberous sclerosis',
            'erdheim', 'vexas', 'apeced', 'autoimmune polyendocrine', 'amyloidosis',
            'telangiectasia', 'hemangioblastoma', 'myelodysplastic', 'west syndrome',
            'mucocutaneous candidiasis', 'hypoparathyroidism'];

        foreach ($rareTerms as $term) {
            if (str_contains($text, $term)) {
                return 'rare_disease';
            }
        }

        // Surgical keywords
        $surgTerms = ['stenosis', 'bypass', 'cabg', 'stent', 'valve replacement',
            'transplant', 'resection', 'arthroplasty', 'surgical', 'post-op',
            'status post'];

        foreach ($surgTerms as $term) {
            if (str_contains($text, $term)) {
                return 'surgical';
            }
        }

        return 'complex_medical';
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PatientIdentifier::class, 'patient_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class, 'patient_id');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'patient_id');
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class, 'patient_id');
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'patient_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'patient_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'patient_id');
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class, 'patient_id');
    }

    public function imagingStudies(): HasMany
    {
        return $this->hasMany(ImagingStudy::class, 'patient_id');
    }

    public function genomicVariants(): HasMany
    {
        return $this->hasMany(GenomicVariant::class, 'patient_id');
    }

    public function conditionEras(): HasMany
    {
        return $this->hasMany(ConditionEra::class, 'patient_id');
    }

    public function drugEras(): HasMany
    {
        return $this->hasMany(DrugEra::class, 'patient_id');
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(PatientEmbedding::class, 'patient_id');
    }
}
