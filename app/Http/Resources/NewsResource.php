<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class NewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'slug'=>$this->slug ?? null,
            'body'=>$this->body,
            'image'=>$this->image ? Storage::url($this->image) : null,
            'published'=>$this->published,
            'published_at'=>$this->published_at,
            'created_at'=>$this->created_at,
        ];
    }
}
