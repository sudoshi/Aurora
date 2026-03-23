<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRecordRef implements ValidationRule
{
    private const VALID_DOMAINS = [
        'condition', 'medication', 'procedure', 'measurement',
        'observation', 'genomic', 'imaging', 'general',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        if (!preg_match('/^[a-z]+:\d+$/', $value)) {
            $fail('The :attribute must be in the format "domain:id" (e.g., "genomic:42").');
            return;
        }

        $domain = explode(':', $value)[0];
        if (!in_array($domain, self::VALID_DOMAINS, true)) {
            $fail('The :attribute domain must be one of: ' . implode(', ', self::VALID_DOMAINS) . '.');
        }
    }
}
