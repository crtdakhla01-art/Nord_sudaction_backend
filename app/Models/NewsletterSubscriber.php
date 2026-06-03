<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'unsubscribe_token',
        'consent',
        'unsubscribed_at',
        'is_suppressed',
        'suppressed_at',
        'suppression_reason',
    ];

    protected $casts = [
        'consent' => 'boolean',
        'unsubscribed_at' => 'datetime',
        'is_suppressed' => 'boolean',
        'suppressed_at' => 'datetime',
    ];

    public static function generateUnsubscribeToken(): string
    {
        do {
            $token = Str::lower(Str::random(48));
        } while (self::query()->where('unsubscribe_token', $token)->exists());

        return $token;
    }
}
