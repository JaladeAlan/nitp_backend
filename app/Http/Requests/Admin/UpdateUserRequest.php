<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        $userId = $this->route('id');
        return [
            'name'=>'sometimes|string|max:255',
            'email'=>"sometimes|email|unique:users,email,{$userId}",
            'password'=>'nullable|string|min:8',
            'is_admin'=>'sometimes|boolean'
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
