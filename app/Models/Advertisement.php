<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'image',
        'link',
        'begin_date',
        'end_date',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'begin_date' => 'date',
        'end_date' => 'date',
    ];
}
