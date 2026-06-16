<?php

namespace App\Services;

use App\Models\CaseTemplate;

class BoardTemplateService
{
    public function resolve(string $slug): ?CaseTemplate
    {
        return CaseTemplate::where('slug', $slug)->first();
    }

    /**
     * Soft validation: returns a list of human-readable warnings. Never throws,
     * never rejects — callers persist regardless and surface warnings in meta.
     *
     * @return list<string>
     */
    public function validate(CaseTemplate $template, array $data): array
    {
        $schema = $template->data_schema ?? [];
        $warnings = [];

        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if ($key === null) {
                continue;
            }
            $present = array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '';

            if (! empty($field['required']) && ! $present) {
                $warnings[] = "Missing required field '{$key}'.";

                continue;
            }
            if ($present && ! $this->typeMatches($field['type'] ?? 'string', $data[$key])) {
                $warnings[] = "Field '{$key}' expected type ".($field['type'] ?? 'string').'.';
            }
        }

        return $warnings;
    }

    private function typeMatches(string $type, mixed $value): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            default => true,
        };
    }
}
