<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'description'=>$this->description,
            'location'=>$this->location,
            'start_date'=>$this->start_date,
            'end_date'=>$this->end_date,
            'banner'=>$this->banner ? Storage::url($this->banner) : null,
            'created_at'=>$this->created_at,
        ];
    }
}
