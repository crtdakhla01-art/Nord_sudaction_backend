<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunityImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id',
        'path',
        'sort_order',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}
