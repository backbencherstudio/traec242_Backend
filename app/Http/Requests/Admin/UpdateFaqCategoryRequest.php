<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) auth()->user()?->type === 1;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255', 'unique:faq_categories,name,'.$id],
            'order_number' => ['nullable', 'integer'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
