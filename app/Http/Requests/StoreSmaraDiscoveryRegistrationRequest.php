<?php

namespace App\Http\Requests;

use App\Models\SmaraDiscoveryRegistration;
use App\Support\ValidationPatterns;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmaraDiscoveryRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:120'],
            'phone' => ValidationPatterns::phoneRules(true),
            'email' => ValidationPatterns::emailRules(true),
            'age_group' => ['required', 'string', Rule::in(SmaraDiscoveryRegistration::AGE_GROUPS)],
            'has_visited_es_smara' => ['required', 'boolean'],
            'interest_level' => ['required', 'string', Rule::in(SmaraDiscoveryRegistration::INTEREST_LEVELS)],
            'participants_count' => ['required', 'string', Rule::in(SmaraDiscoveryRegistration::PARTICIPANTS_COUNTS)],
            'preferred_duration' => ['required', 'string', Rule::in(SmaraDiscoveryRegistration::PREFERRED_DURATIONS)],
            'preferred_activities' => ['required', 'array', 'min:1'],
            'preferred_activities.*' => ['required', 'string', Rule::in(SmaraDiscoveryRegistration::ACTIVITIES)],
            'notify_first_date' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => ValidationPatterns::normalizeEmail($this->input('email')),
            'phone' => ValidationPatterns::normalizePhone($this->input('phone')),
            'has_visited_es_smara' => $this->toBoolean($this->input('has_visited_es_smara')),
            'notify_first_date' => $this->toBoolean($this->input('notify_first_date')),
        ]);
    }

    private function toBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'oui', 'on' => true,
            '0', 'false', 'no', 'non', 'off' => false,
            default => filter_var($normalized, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        };
    }
}
