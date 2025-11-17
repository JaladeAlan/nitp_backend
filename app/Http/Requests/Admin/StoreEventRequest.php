<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'title'=>'required|string|max:255',
            'description'=>'nullable|string',
            'start_date'=>'required|date',
            'end_date'=>'nullable|date|after_or_equal:start_date',
            'location'=>'nullable|string|max:255',
            'banner'=>'nullable|image|max:5120'
        ];
    }
}
