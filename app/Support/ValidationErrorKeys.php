<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class ValidationErrorKeys
{
    public static function fromValidationException(ValidationException $exception): array
    {
        return self::fromFailedRules($exception->validator->failed());
    }

    public static function fromFailedRules(array $failedRules): array
    {
        $mapped = [];

        foreach ($failedRules as $field => $rules) {
            $ruleName = strtolower((string) array_key_first($rules));
            $mapped[$field] = self::mapFieldRuleToKey((string) $field, $ruleName);
        }

        return $mapped;
    }

    public static function firstErrorKey(array $mapped): string
    {
        $first = reset($mapped);
        return is_string($first) && $first !== '' ? $first : 'validation.generic';
    }

    private static function mapFieldRuleToKey(string $field, string $rule): string
    {
        if ($field === 'email') {
            return match ($rule) {
                'required' => 'validation.email_required',
                'unique' => 'validation.email_taken',
                default => 'validation.email_invalid',
            };
        }

        if ($field === 'phone') {
            return match ($rule) {
                'required' => 'validation.phone_required',
                default => 'validation.phone_invalid',
            };
        }

        return match ($rule) {
            'required', 'accepted' => 'validation.required',
            'max' => 'validation.too_long',
            'min' => 'validation.too_short',
            'numeric' => 'validation.numeric',
            default => 'validation.generic',
        };
    }
}
