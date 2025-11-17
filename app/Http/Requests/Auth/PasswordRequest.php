<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PasswordRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        $action = $this->route()->getName(); // optional
        // caller will set appropriate fields; we validate common combos
        return [
            'email'=>'required|email',
            'reset_code'=>'sometimes|required|string|size:6',
            'password'=>'sometimes|required|string|confirmed|min:8|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/|regex:/[@$!%*?&#]/'
        ];
    }
}
