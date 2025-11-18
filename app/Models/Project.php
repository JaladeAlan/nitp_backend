<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'body',
        'cover',
        'published',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'published' => 'boolean',
    ];
}
