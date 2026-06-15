<?php

namespace App\Http\Requests;

use App\Services\Genomics\Acmg\AcmgCriteriaCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassificationCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', Rule::in(array_keys(AcmgCriteriaCatalog::all()))],
            'applied_strength' => ['required', 'string', Rule::in(['very_strong', 'strong', 'moderate', 'supporting'])],
            'rationale' => 'nullable|string|max:2000',
        ];
    }
}
