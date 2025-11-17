<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'image'=>'required|image|max:8192',
            'title'=>'nullable|string|max:255',
            'caption'=>'nullable|string|max:800'
        ];
    }
}
