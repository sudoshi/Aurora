<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MmeMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient' => ['required', 'array'],
            'patient.id' => ['required', 'string', 'max:255'],
            'patient.features' => ['nullable', 'array'],
            'patient.features.*.id' => ['required_with:patient.features', 'string', 'regex:/^HP:\d{7}$/'],
            'patient.genomicFeatures' => ['nullable', 'array'],
            'patient.genomicFeatures.*.gene.id' => ['required_with:patient.genomicFeatures', 'string'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $p = $this->input('patient', []);
            if (empty($p['features']) && empty($p['genomicFeatures'])) {
                $v->errors()->add('patient', 'At least one of features or genomicFeatures is required.');
            }
        }];
    }

    // MME errors must be a bare {message} JSON, not Laravel's default validation envelope.
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
        ], 422));
    }
}
