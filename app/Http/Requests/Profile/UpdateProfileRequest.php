<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'zip_code' => ['sometimes', 'string', 'max:20'],
            'bio' => ['sometimes', 'string'],
            'languages' => ['sometimes', 'array'],
            'languages.*' => ['string'],
        ];

        if ($user && (int) $user->type === 2) {
            $rules['category_id'] = ['sometimes', 'array'];
            $rules['category_id.*'] = ['integer', 'exists:categories,id'];
        }

        return $rules;
    }
}
