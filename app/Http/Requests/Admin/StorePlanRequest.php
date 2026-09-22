<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) auth()->user()?->type === 1;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'in:free,premium'],
            'title' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'package' => ['required', 'in:free,monthly,yearly'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'stripe_product_id' => ['nullable', 'string'],
            'stripe_price_id' => ['nullable', 'string', 'unique:plans,stripe_price_id'],
        ];
    }
}
