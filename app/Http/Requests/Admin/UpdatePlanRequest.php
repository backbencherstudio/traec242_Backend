<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) auth()->user()?->type === 1;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['nullable', 'in:free,premium'],
            'title' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'package' => ['nullable', 'in:free,monthly,yearly'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'status' => ['nullable', 'in:0,1'],
            'stripe_product_id' => ['nullable', 'string'],
            'stripe_price_id' => ['nullable', 'string', 'unique:plans,stripe_price_id,'.$id],
        ];
    }
}
