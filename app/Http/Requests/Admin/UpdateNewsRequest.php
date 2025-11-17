<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'title'=>'required|string|max:255',
            'body'=>'required|string',
            'published'=>'sometimes|boolean',
            'published_at'=>'nullable|date',
            'image'=>'nullable|image|max:5120'
        ];
    }
}
