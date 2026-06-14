<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOdysseyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'referral_reason' => 'nullable|string|max:2000',
            'case_id' => 'nullable|integer|exists:app.cases,id',
        ];
    }
}
