<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email'=> 'required|email|max:255|unique:users,email',
            'password'=> [
                'required','string','min:8','confirmed',
                'regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/','regex:/[@$!%*?&#]/'
            ]
        ];
    }

    public function messages()
    {
        return [
            'password.regex' => 'Password must include uppercase, lowercase, number and special character.'
        ];
    }
}
