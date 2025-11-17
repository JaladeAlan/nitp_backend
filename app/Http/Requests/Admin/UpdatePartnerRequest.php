<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name'=>'required|string|max:255',
            'website'=>'nullable|url',
            'logo'=>'nullable|image|max:5120'
        ];
    }
}
