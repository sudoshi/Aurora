<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhenotypeFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hpo_id' => [
                'required', 'string', 'regex:/^HP:\d{7}$/',
                Rule::unique('app.phenotype_features', 'hpo_id')
                    ->where('odyssey_id', $this->route('odyssey')),
            ],
            'hpo_label' => 'required|string|max:255',
            'excluded' => 'sometimes|boolean',
            'onset_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'severity_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'frequency_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'evidence' => 'nullable|string|max:255',
        ];
    }
}
