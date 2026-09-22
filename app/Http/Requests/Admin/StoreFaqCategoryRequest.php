<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) auth()->user()?->type === 1;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:faq_categories,name'],
            'order_number' => ['nullable', 'integer'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
