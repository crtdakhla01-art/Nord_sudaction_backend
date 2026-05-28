<?php

namespace App\Http\Requests;

use App\Support\ValidationPatterns;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInscriptionRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:200'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'city' => ['required', 'string', 'max:120'],
            'phone' => ValidationPatterns::phoneRules(true),
            'email' => ValidationPatterns::emailRules(true),
            'profession' => ['required', 'string', 'max:150'],
            'organization' => ['required', 'string', 'max:200'],

            'participant_profiles' => ['required', 'array', 'min:1'],
            'participant_profiles.*' => ['string', Rule::in([
                'investisseur',
                'entrepreneur',
                'porteur_de_projet',
                'chef_d_entreprise',
                'institutionnel',
                'media_presse',
                'autre',
            ])],
            'participant_profile_other' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => in_array('autre', (array) $this->input('participant_profiles', []), true)),
            ],

            'investment_sectors' => ['required', 'array', 'min:1'],
            'investment_sectors.*' => ['string', Rule::in([
                'tourisme',
                'hotellerie_bivouacs',
                'evenementiel',
                'immobilier',
                'artisanat',
                'commerce',
                'services',
                'autre',
            ])],
            'investment_sector_other' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => in_array('autre', (array) $this->input('investment_sectors', []), true)),
            ],

            'confirmed_activities' => ['required', 'array', 'min:1'],
            'confirmed_activities.*' => ['string', Rule::in([
                'conferences_networking',
                'excursion_desert',
                'soiree_bivouac',
                'observation_astronomique',
            ])],

            'payment_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'cin_copy' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'is_payment_confirmed' => ['required', 'accepted'],
            'is_terms_accepted' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.max' => 'api.error_file_too_large',
            'cin_copy.max' => 'api.error_file_too_large',
            'payment_proof.mimes' => 'api.error_invalid_file_type',
            'cin_copy.mimes' => 'api.error_invalid_file_type',
            'cin_copy.image' => 'api.error_image_invalid',
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
