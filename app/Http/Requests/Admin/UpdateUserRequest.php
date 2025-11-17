<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
}
