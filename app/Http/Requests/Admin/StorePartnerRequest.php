<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
