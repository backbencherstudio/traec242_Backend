<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'service_pricing_id' => ['required', 'exists:service_pricings,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'event_name' => ['required', 'string', 'max:255'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'event_duration' => ['nullable', 'string'],
            'event_description' => ['nullable', 'string'],
            'event_start_date' => ['required', 'date', 'after_or_equal:today'],
            'event_end_date' => ['required', 'date', 'after_or_equal:event_start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'question_one' => ['nullable', 'string'],
            'question_two' => ['nullable', 'string'],
            'question_three' => ['nullable', 'string'],
            'question_four' => ['nullable', 'string'],
            'question_five' => ['nullable', 'string'],
            'question_six' => ['nullable', 'string'],
            'include_order_ids' => ['nullable', 'array'],
            'include_order_ids.*' => ['integer', 'exists:include_orders,id'],
            'agree_terms' => ['required', 'boolean'],
            'payment_method' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
        ];
    }
}
