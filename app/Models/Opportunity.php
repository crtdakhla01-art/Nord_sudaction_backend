<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'ville',
        'first_name',
        'last_name',
        'description',
        'budget',
        'phone',
        'email',
        'type_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
        ];
    }

    public function type()
    {
        return $this->belongsTo(TypeOpportunity::class, 'type_id');
    }

    public function images()
    {
        return $this->hasMany(OpportunityImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
