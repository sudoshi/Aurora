<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'time' => 'sometimes|date',
            'duration' => 'sometimes|integer',
            'location' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'team_members' => 'nullable|array',
            'team_members.*.user_id' => 'required|exists:dev.users,id',
            'team_members.*.role' => 'nullable|string',
            'patient_ids' => 'nullable|array',
            'patient_ids.*' => 'required|exists:dev.patients,id',
        ];
    }
}
