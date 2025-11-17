<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'published_at',
    ];

    protected $hidden = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
