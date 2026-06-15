<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmClassificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'final_classification' => ['required', 'string', Rule::in(['pathogenic', 'likely_pathogenic', 'vus', 'likely_benign', 'benign'])],
            'override_reason' => 'nullable|string|max:2000',
        ];
    }
}
