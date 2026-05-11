<?php

namespace App\Http\Requests;

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
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
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

            'is_information_confirmed' => ['required', 'accepted'],
            'is_terms_accepted' => ['required', 'accepted'],
        ];
    }
}
