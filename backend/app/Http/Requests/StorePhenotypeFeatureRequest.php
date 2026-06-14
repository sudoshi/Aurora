<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhenotypeFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hpo_id' => ['required', 'string', 'regex:/^HP:\d{7}$/'],
            'hpo_label' => 'required|string|max:255',
            'excluded' => 'sometimes|boolean',
            'onset_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'severity_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'frequency_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'evidence' => 'nullable|string|max:255',
        ];
    }
}
