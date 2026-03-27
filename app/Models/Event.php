<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'is_it_passed',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_it_passed' => 'boolean',
        ];
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(EventGalerie::class, 'event_id');
    }
}
