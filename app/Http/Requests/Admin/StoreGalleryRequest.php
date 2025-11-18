<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
