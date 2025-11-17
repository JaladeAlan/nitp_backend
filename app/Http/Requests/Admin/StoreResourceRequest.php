<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'title'=>'required|string|max:255',
            'file'=>'required|file|max:10240',
            'description'=>'nullable|string'
        ];
    }
}
