<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProviderRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'phone'      => 'required|string|max:20',
            'address'    => 'required|string|max:255',
            'city'       => 'required|string|max:100',
            'state'      => 'required|string|max:100',
            'zip_code'   => 'required|string|max:20',
            'password'   => 'required|string|min:8|confirmed',
            'category_id' => 'required|array',
            'category_id.*' => 'exists:categories,id',
        ];
    }
}
