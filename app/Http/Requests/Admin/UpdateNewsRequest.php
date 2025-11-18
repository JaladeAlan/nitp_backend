<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateNewsRequest extends FormRequest
{
    public function authorize() { return true; }
  public function rules()
    {
          \Log::info("UpdateNewsRequest RAW DATA", request()->all());
        return [
            'title'         => 'sometimes|required|string|max:255',
            'content'       => 'sometimes|required|string',
            'is_published' => 'sometimes|in:true,false,1,0,yes,no,on,off',
            'published_at'  => 'sometimes|nullable|date',
            'image'         => 'sometimes|nullable|image|max:5120',
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
