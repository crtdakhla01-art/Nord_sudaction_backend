<?php

namespace App\Support;

class ValidationPatterns
{
    public const EMAIL_REGEX = '/^(?!.*\.\.)(?!.*\s)[A-Za-z0-9](?:[A-Za-z0-9._%+\-]{0,62}[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,24}$/';

    public const PHONE_REGEX = '/^(?:\+212|212|0)[5-7]\d{8}$/';

    public static function normalizeEmail(null|string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return $normalized === '' ? null : $normalized;
    }

    public static function normalizePhone(null|string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $normalized) ?? '';

        if (str_starts_with($normalized, '00')) {
            $normalized = '+'.substr($normalized, 2);
        }

        return $normalized === '' ? null : $normalized;
    }

    public static function emailRules(bool $required = true): array
    {
        $rules = ['string', 'max:255', 'email:rfc,dns', 'regex:'.self::EMAIL_REGEX];

        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }

    public static function phoneRules(bool $required = false): array
    {
        $rules = ['string', 'max:20', 'regex:'.self::PHONE_REGEX, 'not_regex:/^0+$/'];

        array_unshift($rules, $required ? 'required' : 'nullable');

        return $rules;
    }
}
