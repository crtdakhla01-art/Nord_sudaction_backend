<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Inscription extends Model
{
    use HasFactory;

    protected $appends = [
        'payment_proof_url',
    ];

    protected $fillable = [
        'full_name',
        'birth_date',
        'city',
        'phone',
        'email',
        'profession',
        'organization',
        'participant_profiles',
        'participant_profile_other',
        'investment_sectors',
        'investment_sector_other',
        'confirmed_activities',
        'participation_fee',
        'is_terms_accepted',
        'is_payment_confirmed',
        'is_paid',
        'paid_at',
        'payment_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'participant_profiles' => 'array',
            'investment_sectors' => 'array',
            'confirmed_activities' => 'array',
            'is_terms_accepted' => 'boolean',
            'is_payment_confirmed' => 'boolean',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_proof_path);
    }
}
