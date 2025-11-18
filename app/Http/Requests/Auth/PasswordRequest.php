<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // $action = $this->route()->getName();
        return [
            'email' => 'required|email',
            'reset_code' => 'sometimes|required|string|size:6',
            'password' => [
                'sometimes',
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#]/'
            ],
        ];
    }

    public function messages()
    {
        return [
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
            'password.confirmed' => 'Password confirmation must match password.'
        ];
    }

    /**
     * Force validation errors to return JSON
     */
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
