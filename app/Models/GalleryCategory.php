<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'gallery_categorie_id');
    }
}
