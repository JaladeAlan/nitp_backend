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
            'content'=>$this->content,
            'image'=>$this->image ? Storage::url($this->image) : null,
            'is_published'  => (bool) $this->is_published,
            'published_at'=>$this->published_at,
            'created_at'=>$this->created_at,
        ];
    }
}
