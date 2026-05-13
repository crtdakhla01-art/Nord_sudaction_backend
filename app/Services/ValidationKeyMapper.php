<?php

namespace App\Services;

/**
 * Maps Laravel validation field errors to API translation keys.
 * Provides a centralized way to handle validation → key mappings.
 */
class ValidationKeyMapper
{
    /**
     * Map common field names to validation error keys
     */
    private static array $fieldKeyMap = [
        'email' => 'api.error_email_invalid',
        'password' => 'api.error_password_invalid',
        'challenge_id' => 'api.error_challenge_id_invalid',
        'code' => 'api.error_code_invalid',
        'phone' => 'api.error_phone_invalid',
        'name' => 'api.error_name_required',
        'title' => 'api.error_title_required',
        'description' => 'api.error_description_required',
        'image' => 'api.error_image_invalid',
        'file' => 'api.error_file_invalid',
        'consent' => 'api.error_consent_required',
    ];

    /**
     * Map validation rule names to error keys
     */
    private static array $ruleKeyMap = [
        'required' => 'api.error_field_required',
        'email' => 'api.error_email_invalid',
        'unique' => 'api.error_already_exists',
        'min' => 'api.error_too_short',
        'max' => 'api.error_too_long',
        'confirmed' => 'api.error_mismatch',
        'numeric' => 'api.error_must_be_numeric',
        'file' => 'api.error_file_invalid',
        'image' => 'api.error_image_invalid',
        'mimes' => 'api.error_invalid_file_type',
        'size' => 'api.error_file_too_large',
    ];

    /**
     * Convert Laravel validation errors to translation keys
     *
     * @param \Illuminate\Validation\ValidationException $e
     * @return array<string, string> Map of field => error_key
     */
    public static function fromValidationException(\Illuminate\Validation\ValidationException $e): array
    {
        $errors = $e->errors();
        $mapped = [];

        foreach ($errors as $field => $messages) {
            $mapped[$field] = self::mapFieldError($field, $messages[0] ?? '');
        }

        return $mapped;
    }

    /**
     * Map a specific field and error message to a translation key
     */
    private static function mapFieldError(string $field, string $message): string
    {
        // Check if field has direct mapping
        if (isset(self::$fieldKeyMap[$field])) {
            return self::$fieldKeyMap[$field];
        }

        // Try to extract validation rule from message
        $rule = self::extractRuleFromMessage($message);
        if ($rule && isset(self::$ruleKeyMap[$rule])) {
            return self::$ruleKeyMap[$rule];
        }

        // Fallback: generic validation error
        return 'api.error_validation_failed';
    }

    /**
     * Extract validation rule name from Laravel validation message
     */
    private static function extractRuleFromMessage(string $message): ?string
    {
        foreach (self::$ruleKeyMap as $rule => $key) {
            if (str_contains(strtolower($message), strtolower($rule))) {
                return $rule;
            }
        }

        return null;
    }
}
