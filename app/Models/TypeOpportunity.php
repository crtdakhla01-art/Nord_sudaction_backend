<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOpportunity extends Model
{
    use HasFactory;

    protected $table = 'types_opportunities';

    protected $fillable = [
        'name',
    ];

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'type_id');
    }
}
