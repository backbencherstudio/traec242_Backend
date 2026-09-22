<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'exists:users,id'],
            'message' => ['nullable', 'string'],
            'files.*' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
