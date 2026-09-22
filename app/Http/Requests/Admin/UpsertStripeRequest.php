<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpsertStripeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) auth()->user()?->type === 1;
    }

    public function rules(): array
    {
        return [
            'stripe_mode' => ['required', 'in:test,live'],
            'stripe_secret_key' => ['required', 'string'],
            'stripe_public_key' => ['required', 'string'],
            'stripe_webhook_secret' => ['nullable', 'string'],
        ];
    }
}
