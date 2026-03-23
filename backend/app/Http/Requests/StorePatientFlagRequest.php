<?php
namespace App\Http\Requests;

use App\Rules\ValidRecordRef;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => 'required|string|in:condition,medication,procedure,measurement,observation,genomic,imaging,general',
            'record_ref' => ['required', 'string', new ValidRecordRef()],
            'severity' => 'sometimes|string|in:critical,attention,informational',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
