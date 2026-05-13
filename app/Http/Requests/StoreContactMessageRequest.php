<?php

namespace App\Http\Requests;

use App\Support\ValidationPatterns;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ValidationPatterns::emailRules(true),
            'phone' => ValidationPatterns::phoneRules(false),
            'object' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => ValidationPatterns::normalizeEmail($this->input('email')),
            'phone' => ValidationPatterns::normalizePhone($this->input('phone')),
        ]);
    }
}
