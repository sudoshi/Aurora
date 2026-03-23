<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'nullable|integer|exists:app.users,id',
            'domain' => 'nullable|string|in:condition,medication,procedure,measurement,observation,genomic,imaging,general',
            'record_ref' => ['nullable', 'string', new \App\Rules\ValidRecordRef()],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
        ];
    }
}
