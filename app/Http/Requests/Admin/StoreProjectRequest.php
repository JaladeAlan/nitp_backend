<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'title'=>'required|string|max:255',
            'summary'=>'nullable|string',
            'body'=>'nullable|string',
            'cover'=>'nullable|image|max:5120',
            'published'=>'sometimes|boolean'
        ];
    }
}
