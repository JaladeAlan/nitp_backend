<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'summary'=>$this->summary,
            'body'=>$this->body,
            'cover'=>$this->cover ? Storage::url($this->cover) : null,
            'published'=>$this->published ?? false,
            'created_at'=>$this->created_at,
        ];
    }
}
