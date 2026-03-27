<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'image',
        'type',
        'external_link',
        'status',
        'is_featured',
        'published_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (!$post->isDirty('title') && !empty($post->slug)) {
                return;
            }

            $post->slug = static::generateUniqueSlug($post->title, $post->id);
        });
    }

    public function scopeArticle(Builder $query): Builder
    {
        return $query->where('type', 'article');
    }

    public function scopeCommunique(Builder $query): Builder
    {
        return $query->where('type', 'communique');
    }

    public function scopeMedia(Builder $query): Builder
    {
        return $query->where('type', 'media');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    private static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        if ($baseSlug === '') {
            $baseSlug = 'post';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
