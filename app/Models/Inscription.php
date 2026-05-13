<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

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
        'is_paid',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'participant_profiles' => 'array',
            'investment_sectors' => 'array',
            'confirmed_activities' => 'array',
            'is_terms_accepted' => 'boolean',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }
}
