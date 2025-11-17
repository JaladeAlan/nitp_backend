<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'link',
        'status', // e.g., 'ongoing', 'completed'
    ];

    protected $hidden = [];

    protected $casts = [];
}
