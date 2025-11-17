<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'caption'=>$this->caption,
            'image'=> $this->image ? Storage::url($this->image) : null,
            'created_at'=>$this->created_at,
        ];
    }
}
