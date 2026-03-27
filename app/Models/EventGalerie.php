<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventGalerie extends Model
{
    use HasFactory;

    protected $table = 'events_galerie';

    protected $fillable = [
        'event_id',
        'image',
        'vedio',
        'link',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}