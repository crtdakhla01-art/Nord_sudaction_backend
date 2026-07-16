<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmaraDiscoveryRegistration extends Model
{
    use HasFactory;

    public const AGE_GROUPS = [
        'under_25',
        '25_34',
        '35_44',
        '45_54',
        '55_plus',
    ];

    public const INTEREST_LEVELS = [
        'certainly',
        'probably',
        'maybe',
    ];

    public const PARTICIPANTS_COUNTS = [
        '1',
        '2',
        '3_or_more',
    ];

    public const PREFERRED_DURATIONS = [
        'weekend',
        '3_days',
        '4_days_plus',
    ];

    public const ACTIVITIES = [
        'astrotourism',
        'bivouac',
        'hiking',
        'archaeological_sites',
        'hassani_culture',
        'wildlife_observation',
        'quad_outing',
        'photography',
    ];

    protected $fillable = [
        'full_name',
        'city',
        'phone',
        'email',
        'age_group',
        'has_visited_es_smara',
        'interest_level',
        'participants_count',
        'preferred_duration',
        'preferred_activities',
        'notify_first_date',
    ];

    protected function casts(): array
    {
        return [
            'has_visited_es_smara' => 'boolean',
            'notify_first_date' => 'boolean',
            'preferred_activities' => 'array',
        ];
    }
}
