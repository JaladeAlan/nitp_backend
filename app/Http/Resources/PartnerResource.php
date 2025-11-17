<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PartnerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'website'=>$this->website,
            'logo'=>$this->logo ? Storage::url($this->logo) : null,
            'created_at'=>$this->created_at,
        ];
    }
}
