<?php

namespace App\Http\Requests;

use App\Services\RareDisease\OdysseyStateMachine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOdysseyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', Rule::in(OdysseyStateMachine::STATES)],
            'note' => 'nullable|string|max:2000',
        ];
    }
}
