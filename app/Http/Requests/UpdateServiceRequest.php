<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'location' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],

            'images' => ['sometimes', 'array'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'pricings' => ['sometimes', 'array'],
            'pricings.*.title' => ['sometimes', 'string', 'max:255'],
            'pricings.*.price' => ['sometimes', 'numeric'],

            'faqs' => ['sometimes', 'array'],
            'faqs.*.question' => ['sometimes', 'string'],
            'faqs.*.answer' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Selected category is invalid.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be jpg, jpeg, png or webp format.',
            'images.*.max' => 'Each image size must not exceed 2MB.',
        ];
    }
}
