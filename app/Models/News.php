<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use HasFactory, SoftDeletes;

    /*************************
     * Mass Assignment
     *************************/
    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'is_published',
        'published_at',
    ];

    /*************************
     * Type Casting
     *************************/
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /*************************
     * Scopes
     *************************/
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /*************************
     * Accessors
     *************************/
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
