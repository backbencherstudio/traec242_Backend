<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'pricings' => 'required|array|min:3|max:3',
            'pricings.*.service_type' => ['required', new Enum(ServiceType::class)],
            'pricings.*.duration' => 'required|string',
            'pricings.*.price' => 'required|numeric|min:0',
            'pricings.*.description' => 'nullable|string',
            'pricings.*.features' => 'nullable|array',
        ];
    }
}
