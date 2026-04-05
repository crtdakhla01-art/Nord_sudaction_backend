<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GalleryImage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'filename',
        'disk_path',
        'gallery_categorie_id',
    ];

    protected $appends = [
        'url',
    ];

    /**
     * Public URL for the image (already WebP after processing).
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->disk_path);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GalleryCategory::class, 'gallery_categorie_id');
    }
}
