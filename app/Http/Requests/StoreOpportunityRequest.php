<?php

namespace App\Http\Requests;

use App\Support\ValidationPatterns;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
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
            'titre' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'phone' => ValidationPatterns::phoneRules(false),
            'email' => ValidationPatterns::emailRules(true),
            'type_key' => ['required', 'string', 'in:investment,commerce,partnership,calls,other'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
