<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,'.$id],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', 'in:0,1'],
            'role' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}
