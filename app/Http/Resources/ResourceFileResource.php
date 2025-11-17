<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ResourceFileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'file'=> $this->file ? Storage::url($this->file) : null,
            'description'=>$this->description,
            'created_at'=>$this->created_at,
        ];
    }
}
